<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\User;
use App\Support\BarangStatus;
use App\Support\PengajuanStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminPeminjamanApprovalTest extends TestCase
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
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'kategori' => 'NFC Reader',
            'kode_ntag' => '04A3B2C1D0E102',
            'lokasi' => 'Ruang IT',
            'status' => Barang::STATUS_DIPINJAM,
        ]);

        foreach ($this->pengajuanContoh() as $item) {
            PengajuanStore::tambah(PengajuanStore::JENIS_PINJAM, $item);
        }
    }

    /**
     * Enam pengajuan peminjaman yang menunggu persetujuan admin.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pengajuanContoh(): array
    {
        return [
            [
                'peminjam' => 'Ahmad Rizki',
                'nim' => '2210512001',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '08:00',
                'keperluan' => 'Praktikum jaringan komputer',
            ],
            [
                'peminjam' => 'Siti Aisyah',
                'nim' => '2210512002',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '09:00',
                'keperluan' => 'Uji koneksi lab',
            ],
            [
                'peminjam' => 'Budi Santoso',
                'nim' => '2210512003',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '10:00',
                'keperluan' => 'Praktikum jaringan lanjutan',
            ],
            [
                'peminjam' => 'Rina Maulida',
                'nim' => '2210512004',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '11:00',
                'keperluan' => 'Persetoran tugas',
            ],
            [
                'peminjam' => 'Fahri Ramadhan',
                'nim' => '2210512005',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '13:00',
                'keperluan' => 'Latihan konfigurasi router',
            ],
            [
                'peminjam' => 'Dewi Lestari',
                'nim' => '2210512006',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '14:00',
                'keperluan' => 'Persiapan seminar',
            ],
        ];
    }

    public function test_admin_page_lists_pengajuan_waiting_for_approval(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->get(route('admin.peminjaman'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Peminjaman')
                ->has('pengajuan', 6)
                ->where('pengajuan.0.id', 'PMJ-001')
                ->where('pengajuan.0.status', 'pending')
                ->where('pengajuan.0.peminjam', 'Ahmad Rizki'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.peminjaman'))->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_open_or_decide_pengajuan(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.peminjaman'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.peminjaman.approve', 'PMJ-001'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.peminjaman.reject', 'PMJ-001'))->assertForbidden();

        $this->assertDatabaseCount('peminjaman', 0);
        $this->assertContains('PMJ-001', $this->idAntrean());
    }

    public function test_approving_creates_transaction_and_updates_barang_status(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->assertTrue(BarangStatus::tersedia('BRG-001'));

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
            'peminjam' => 'Ahmad Rizki',
            'keperluan' => 'Praktikum jaringan komputer',
            'tanggal_pinjam' => '2026-09-27',
            'batas_kembali' => '2026-09-29',
            'jam_pinjam' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);

        $this->assertNotContains('PMJ-001', $this->idAntrean());
        $this->assertFalse(BarangStatus::tersedia('BRG-001'));

        $this->actingAs($admin)
            ->get(route('barang.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/DataBarang')
                ->where('barang', fn (Collection $barang) => ($barang->firstWhere('kode', 'BRG-001')['status'] ?? null) === 'dipinjam'));
    }

    public function test_rejecting_keeps_pengajuan_out_of_transaction_data(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.peminjaman.reject', 'PMJ-001'), ['alasan' => 'Barang sedang diperbaiki'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('peminjaman', 0);
        $this->assertTrue(BarangStatus::tersedia('BRG-001'));

        $item = collect(PengajuanStore::antrean(PengajuanStore::JENIS_PINJAM))->firstWhere('id', 'PMJ-001');

        $this->assertSame('ditolak', $item['status']);
        $this->assertSame('Barang sedang diperbaiki', $item['alasan']);

        $this->actingAs($admin)
            ->get(route('admin.peminjaman'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pengajuan', fn (Collection $pengajuan) => ($pengajuan->firstWhere('id', 'PMJ-001')['status'] ?? null) === 'ditolak'));
    }

    public function test_decided_pengajuan_cannot_be_decided_again(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $jadwal = [
            'tanggal_mulai' => '2026-09-27',
            'tanggal_selesai' => '2026-09-28',
            'jam_mulai' => '08:00',
            'jam_selesai' => '16:00',
        ];

        $this->actingAs($admin)->post(route('admin.peminjaman.approve', 'PMJ-001'), $jadwal);
        $this->actingAs($admin)->post(route('admin.peminjaman.approve', 'PMJ-001'), $jadwal)->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.peminjaman.reject', 'PMJ-001'))->assertSessionHas('error');

        $this->assertDatabaseCount('peminjaman', 1);
    }

    public function test_cannot_approve_pengajuan_for_a_borrowed_barang(): void
    {
        Storage::fake('local');

        $pengajuan = PengajuanStore::tambah(PengajuanStore::JENIS_PINJAM, [
            'peminjam' => 'Peminjam Lain',
            'kode' => 'BRG-002',
            'nama' => 'Card Reader NFC ACR122U',
            'kategori' => 'NFC Reader',
            'tanggal' => '2026-09-27',
            'jam' => '09:00',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.peminjaman.approve', $pengajuan['id']), [
                'tanggal_mulai' => '2026-09-27',
                'tanggal_selesai' => '2026-09-28',
                'jam_mulai' => '09:00',
                'jam_selesai' => '16:00',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('peminjaman', 0);
        $this->assertContains($pengajuan['id'], $this->idAntrean());
    }

    public function test_approving_without_jadwal_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->post(route('admin.peminjaman.approve', 'PMJ-001'))
            ->assertSessionHasErrors(['tanggal_mulai', 'tanggal_selesai', 'jam_mulai', 'jam_selesai']);

        $this->assertDatabaseCount('peminjaman', 0);
        $this->assertContains('PMJ-001', $this->idAntrean());
    }

    public function test_approving_with_invalid_range_is_rejected(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.peminjaman.approve', 'PMJ-001'), [
                'tanggal_mulai' => '2026-09-29',
                'tanggal_selesai' => '2026-09-27',
                'jam_mulai' => '08:00',
                'jam_selesai' => '16:00',
            ])
            ->assertSessionHasErrors('tanggal_selesai');

        $this->actingAs($admin)
            ->post(route('admin.peminjaman.approve', 'PMJ-001'), [
                'tanggal_mulai' => '2026-09-27',
                'tanggal_selesai' => '2026-09-27',
                'jam_mulai' => '16:00',
                'jam_selesai' => '08:00',
            ])
            ->assertSessionHasErrors('jam_selesai');

        $this->assertDatabaseCount('peminjaman', 0);
        $this->assertContains('PMJ-001', $this->idAntrean());
    }

    /**
     * @return array<int, string>
     */
    private function idAntrean(): array
    {
        return array_column(PengajuanStore::antrean(PengajuanStore::JENIS_PINJAM), 'id');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
