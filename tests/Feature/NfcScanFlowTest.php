<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use App\Support\PengajuanStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur NFC: stiker NTAG dipindai pembaca di HP, UID dikirim ke backend, lalu
 * dipakai untuk mendaftarkan barang, mengajukan peminjaman, dan pengembalian.
 */
class NfcScanFlowTest extends TestCase
{
    use RefreshDatabase;

    private const UID_CANONIK = '04A3B2C1D0E101';

    protected function setUp(): void
    {
        parent::setUp();

        Barang::query()->create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'kode_ntag' => self::UID_CANONIK,
            'lokasi' => 'Ruang IT',
            'status' => Barang::STATUS_TERSEDIA,
        ]);
    }

    public function test_uid_from_phone_reader_with_colons_lowercase_and_spaces_is_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('peminjaman.store'), [
                'nfc_uid' => ' 04:a3:b2:c1:d0:e1:01 ',
                'catatan' => 'Praktikum jaringan komputer',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuans', [
            'jenis_pengajuan' => PengajuanStore::JENIS_PINJAM,
            'kode' => 'BRG-001',
            'nama' => 'Kabel LAN RJ45 5 Meter',
            'user_id' => $user->id,
            'status' => PengajuanStore::STATUS_PENDING,
        ]);
    }

    public function test_return_request_with_dashed_uid_is_accepted(): void
    {
        $user = User::factory()->create();

        Peminjaman::factory()->create([
            'user_id' => $user->id,
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
        ]);

        $this->actingAs($user)
            ->post(route('pengembalian.store'), [
                'nfc_uid' => '04-A3-B2-C1-D0-E1-01',
                'catatan' => 'Kabel kembali utuh',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pengajuans', [
            'jenis_pengajuan' => PengajuanStore::JENIS_KEMBALI,
            'kode' => 'BRG-001',
            'user_id' => $user->id,
            'catatan' => 'Kabel kembali utuh',
            'status' => PengajuanStore::STATUS_PENDING,
        ]);
    }

    public function test_unknown_uid_is_rejected_for_borrow_and_return(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('peminjaman.store'), ['nfc_uid' => 'DEADBEEF000001'])
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->post(route('pengembalian.store'), ['nfc_uid' => 'DEADBEEF000001'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pengajuans', 0);
    }

    public function test_admin_add_barang_stores_canonical_uid(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.barang.store'), [
                'nama' => 'Card Reader NFC ACR122U',
                'kategori' => 'NFC Reader',
                'kode_ntag' => '04:a3:b2:c1:d0:e1:02',
                'lokasi' => 'Ruang IT',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barangs', [
            'nama_barang' => 'Card Reader NFC ACR122U',
            'kode_ntag' => '04A3B2C1D0E102',
        ]);
    }

    public function test_admin_rejects_same_stiker_with_different_format(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.barang.store'), [
                'nama' => 'Kabel Duplikat',
                'kode_ntag' => '04-A3-B2-C1-D0-E1-01',
            ])
            ->assertSessionHasErrors('kode_ntag');

        $this->assertDatabaseCount('barangs', 1);
    }

    public function test_admin_rejects_non_hex_uid(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.barang.store'), [
                'nama' => 'Barang Tanpa UID',
                'kode_ntag' => 'bukan-uid-nyata',
            ])
            ->assertSessionHasErrors('kode_ntag');

        $this->assertDatabaseCount('barangs', 1);
    }

    public function test_full_lifecycle_from_scanning_new_stiker_until_return(): void
    {
        $admin = $this->admin();
        $peminjam = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.barang.store'), [
                'nama' => 'Modem Telit NL-SW2',
                'kategori' => 'Modem',
                'kode_ntag' => '04A3B2C1D0E103',
                'lokasi' => 'Lab IoT',
            ])
            ->assertSessionHas('success');

        $this->actingAs($peminjam)
            ->post(route('peminjaman.store'), [
                'nfc_uid' => '04:A3:B2:C1:D0:E1:03',
                'catatan' => 'Praktikum IoT',
            ])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.peminjaman.approve', 'PMJ-001'), [
                'tanggal_mulai' => now()->format('Y-m-d'),
                'tanggal_selesai' => now()->addDay()->format('Y-m-d'),
                'jam_mulai' => '08:00',
                'jam_selesai' => '16:00',
            ])
            ->assertSessionHas('success');

        $this->assertNotNull(
            Peminjaman::query()->where('kode_barang', 'BRG-002')->where('user_id', $peminjam->id)->first()
        );

        $this->actingAs($peminjam)
            ->post(route('pengembalian.store'), [
                'nfc_uid' => '04-A3-B2-C1-D0-E1-03',
            ])
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.pengembalian.approve', 'PGB-001'))
            ->assertSessionHas('success');

        $this->assertNotNull(
            Pengembalian::query()->where('kode_barang', 'BRG-002')->where('user_id', $peminjam->id)->first()
        );

        $this->assertDatabaseHas('barangs', [
            'kode_barang' => 'BRG-002',
            'status' => Barang::STATUS_TERSEDIA,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
