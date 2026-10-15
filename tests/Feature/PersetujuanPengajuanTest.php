<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use App\Support\PengajuanStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersetujuanPengajuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Barang::query()->create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'kode_ntag' => '04A3B2C1D0E101',
            'lokasi' => 'Ruang IT',
            'status' => Barang::STATUS_TERSEDIA,
        ]);

        Barang::query()->create([
            'kode_barang' => 'BRG-005',
            'nama_barang' => 'Modem Telit NL-SW2',
            'kategori' => 'Modem',
            'kode_ntag' => '04A3B2C1D0E105',
            'lokasi' => 'Lab IoT',
            'status' => Barang::STATUS_TERSEDIA,
        ]);

        PengajuanStore::tambah(PengajuanStore::JENIS_PINJAM, [
            'peminjam' => 'Ahmad Rizki',
            'nim' => '2210512001',
            'kode' => 'BRG-001',
            'nama' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'tanggal' => '2026-09-27',
            'jam' => '08:00',
            'keperluan' => 'Praktikum jaringan komputer',
        ]);
    }

    public function test_approving_loan_sets_due_date_marks_pengajuan_and_syncs_barang(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.peminjaman.approve', 'PMJ-001'), [
                'tanggal_mulai' => '2026-09-27',
                'tanggal_selesai' => '2026-09-29',
                'jam_mulai' => '08:00',
                'jam_selesai' => '16:00',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('peminjaman', [
            'pengajuan_id' => 'PMJ-001',
            'kode_barang' => 'BRG-001',
            'tanggal_pinjam' => '2026-09-27',
            'batas_kembali' => '2026-09-29',
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);

        $this->assertDatabaseHas('pengajuans', [
            'pengajuan_id' => 'PMJ-001',
            'status' => PengajuanStore::STATUS_DISETUJUI,
            'id_pengelola' => $admin->id,
        ]);

        $this->assertDatabaseHas('barangs', [
            'kode_barang' => 'BRG-001',
            'status' => 'Dipinjam',
        ]);
    }

    public function test_approving_return_marks_loan_finished_and_barang_available(): void
    {
        $loan = Peminjaman::factory()->create([
            'kode_barang' => 'BRG-005',
            'tanggal_pinjam' => '2026-09-26',
            'batas_kembali' => '2026-09-29',
        ]);

        $pengajuan = $this->ajukanPengembalian($loan, '2026-09-29');

        $this->actingAs($this->admin())
            ->post(route('admin.pengembalian.approve', $pengajuan['id']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengembalian', [
            'pengajuan_id' => $pengajuan['id'],
            'peminjaman_id' => $loan->id,
            'kode_barang' => 'BRG-005',
            'status' => 'Tepat Waktu',
        ]);

        $this->assertDatabaseHas('peminjaman', [
            'id' => $loan->id,
            'status' => Peminjaman::STATUS_SELESAI,
        ]);

        $this->assertDatabaseHas('pengajuans', [
            'pengajuan_id' => $pengajuan['id'],
            'status' => PengajuanStore::STATUS_DISETUJUI,
        ]);

        $this->assertDatabaseHas('barangs', [
            'kode_barang' => 'BRG-005',
            'status' => 'Tersedia',
        ]);
    }

    public function test_return_after_due_date_is_flagged_as_late(): void
    {
        $loan = Peminjaman::factory()->create([
            'kode_barang' => 'BRG-005',
            'tanggal_pinjam' => '2026-09-24',
            'batas_kembali' => '2026-09-26',
        ]);

        $pengajuan = $this->ajukanPengembalian($loan, '2026-09-27');

        $this->actingAs($this->admin())
            ->post(route('admin.pengembalian.approve', $pengajuan['id']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengembalian', [
            'pengajuan_id' => $pengajuan['id'],
            'peminjaman_id' => $loan->id,
            'status' => 'Terlambat',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function ajukanPengembalian(Peminjaman $loan, string $tanggal): array
    {
        return PengajuanStore::tambah(PengajuanStore::JENIS_KEMBALI, [
            'user_id' => $loan->user_id,
            'peminjam' => $loan->peminjam,
            'nim' => $loan->nim,
            'kode' => $loan->kode_barang,
            'nama' => $loan->nama_barang,
            'kategori' => $loan->kategori,
            'tanggal' => $tanggal,
            'jam' => '10:00',
            'catatan' => 'Kondisi baik.',
            'peminjaman_id' => $loan->id,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
