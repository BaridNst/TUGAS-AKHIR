<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use App\Support\BarangStatus;
use App\Support\PengajuanStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminPengembalianApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ($this->barangContoh() as $item) {
            Barang::query()->create($item);
        }

        foreach ($this->pengajuanContoh() as $item) {
            PengajuanStore::tambah(PengajuanStore::JENIS_KEMBALI, $item);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function barangContoh(): array
    {
        return [
            [
                'kode_barang' => 'BRG-001',
                'nama_barang' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'kode_ntag' => '04A3B2C1D0E101',
                'lokasi' => 'Ruang IT',
                'status' => Barang::STATUS_TERSEDIA,
            ],
            [
                'kode_barang' => 'BRG-002',
                'nama_barang' => 'Card Reader NFC ACR122U',
                'kategori' => 'NFC Reader',
                'kode_ntag' => '04A3B2C1D0E102',
                'lokasi' => 'Ruang IT',
                'status' => Barang::STATUS_DIPINJAM,
            ],
            [
                'kode_barang' => 'BRG-003',
                'nama_barang' => 'Modem Telit NL-SW2',
                'kategori' => 'Modem',
                'kode_ntag' => '04A3B2C1D0E103',
                'lokasi' => 'Lab IoT',
                'status' => Barang::STATUS_TERSEDIA,
            ],
        ];
    }

    /**
     * Empat pengajuan pengembalian yang menunggu persetujuan admin.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pengajuanContoh(): array
    {
        return [
            [
                'peminjam' => 'Dimas Saputra',
                'nim' => '2210512007',
                'kode' => 'BRG-002',
                'nama' => 'Card Reader NFC ACR122U',
                'kategori' => 'NFC Reader',
                'tanggal' => '2026-09-27',
                'jam' => '10:00',
                'catatan' => 'Kondisi baik, kabel dan kartu lengkap.',
            ],
            [
                'peminjam' => 'Siti Aisyah',
                'nim' => '2210512002',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '11:00',
                'catatan' => 'Kabel kembali utuh.',
            ],
            [
                'peminjam' => 'Budi Santoso',
                'nim' => '2210512003',
                'kode' => 'BRG-003',
                'nama' => 'Modem Telit NL-SW2',
                'kategori' => 'Modem',
                'tanggal' => '2026-09-27',
                'jam' => '13:00',
                'catatan' => 'Modem berfungsi normal.',
            ],
            [
                'peminjam' => 'Rina Maulida',
                'nim' => '2210512004',
                'kode' => 'BRG-001',
                'nama' => 'Kabel LAN RJ45 5 Meter',
                'kategori' => 'Kabel',
                'tanggal' => '2026-09-27',
                'jam' => '14:00',
                'catatan' => 'Sudah dicuci kembali.',
            ],
        ];
    }

    public function test_admin_page_lists_pengajuan_waiting_for_approval(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->get(route('admin.pengembalian'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Pengembalian')
                ->has('pengajuan', 4)
                ->where('pengajuan.0.id', 'PGB-001')
                ->where('pengajuan.0.status', 'pending')
                ->where('pengajuan.0.peminjam', 'Dimas Saputra'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pengembalian'))->assertRedirect(route('login'));
    }

    public function test_regular_user_cannot_open_or_decide_pengajuan(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.pengembalian'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.pengembalian.approve', 'PGB-001'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.pengembalian.reject', 'PGB-001'))->assertForbidden();

        $this->assertDatabaseCount('pengembalian', 0);
        $this->assertContains('PGB-001', $this->idAntrean());
    }

    public function test_approving_creates_transaction_and_frees_barang(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->assertFalse(BarangStatus::tersedia('BRG-002'));

        $this->actingAs($admin)
            ->post(route('admin.pengembalian.approve', 'PGB-001'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengembalian', [
            'pengajuan_id' => 'PGB-001',
            'kode_barang' => 'BRG-002',
            'peminjam' => 'Dimas Saputra',
            'catatan' => 'Kondisi baik, kabel dan kartu lengkap.',
        ]);

        $this->assertNotContains('PGB-001', $this->idAntrean());
        $this->assertTrue(BarangStatus::tersedia('BRG-002'));

        $this->actingAs($admin)
            ->get(route('barang.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('barang', fn (Collection $barang) => ($barang->firstWhere('kode', 'BRG-002')['status'] ?? null) === 'ready'));
    }

    public function test_approving_return_closes_the_matching_loan(): void
    {
        Storage::fake('local');

        $peminjam = User::factory()->create();
        $loan = Peminjaman::factory()->create([
            'user_id' => $peminjam->id,
            'kode_barang' => 'BRG-003',
            'nama_barang' => 'Modem Telit NL-SW2',
            'kategori' => 'Modem',
        ]);

        $this->assertFalse(BarangStatus::tersedia('BRG-003'));

        $pengajuan = PengajuanStore::tambah(PengajuanStore::JENIS_KEMBALI, [
            'user_id' => $peminjam->id,
            'peminjam' => $peminjam->name,
            'kode' => 'BRG-003',
            'nama' => 'Modem Telit NL-SW2',
            'kategori' => 'Modem',
            'tanggal' => '2026-09-27',
            'jam' => '11:00',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.pengembalian.approve', $pengajuan['id']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengembalian', [
            'pengajuan_id' => $pengajuan['id'],
            'peminjaman_id' => $loan->id,
            'kode_barang' => 'BRG-003',
        ]);

        $this->assertTrue(BarangStatus::tersedia('BRG-003'));
        $this->assertSame(0, Peminjaman::sedangDipinjam()->count());
    }

    public function test_rejecting_keeps_pengajuan_out_of_transaction_data(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.pengembalian.reject', 'PGB-001'), ['alasan' => 'Barang belum dibersihkan'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('pengembalian', 0);

        $item = collect(PengajuanStore::antrean(PengajuanStore::JENIS_KEMBALI))->firstWhere('id', 'PGB-001');

        $this->assertSame('ditolak', $item['status']);
        $this->assertSame('Barang belum dibersihkan', $item['alasan']);
        $this->assertFalse(BarangStatus::tersedia('BRG-002'));

        $this->actingAs($admin)
            ->get(route('admin.pengembalian'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('pengajuan', fn (Collection $pengajuan) => ($pengajuan->firstWhere('id', 'PGB-001')['status'] ?? null) === 'ditolak'));
    }

    public function test_cannot_approve_return_for_a_barang_that_is_not_borrowed(): void
    {
        Storage::fake('local');

        $pengajuan = PengajuanStore::tambah(PengajuanStore::JENIS_KEMBALI, [
            'peminjam' => 'Peminjam Lain',
            'kode' => 'BRG-001',
            'nama' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'tanggal' => '2026-09-27',
            'jam' => '09:00',
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.pengembalian.approve', $pengajuan['id']))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pengembalian', 0);
    }

    /**
     * @return array<int, string>
     */
    private function idAntrean(): array
    {
        return array_column(PengajuanStore::antrean(PengajuanStore::JENIS_KEMBALI), 'id');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
