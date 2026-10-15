<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminBarangPeminjamCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_barang_with_system_generated_kode(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.barang.store'), [
                'nama' => 'Proyektor Epson',
                'kategori' => 'Elektronik',
                'kode_ntag' => '04A3B2C1D0E999',
                'lokasi' => 'Ruang Multimedia',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $barang = Barang::query()->where('kode_ntag', '04A3B2C1D0E999')->first();

        $this->assertNotNull($barang);
        $this->assertSame('Proyektor Epson', $barang->nama_barang);
        $this->assertSame(Barang::STATUS_TERSEDIA, $barang->status);
        $this->assertMatchesRegularExpression('/^BRG-\d+$/', $barang->kode_barang);
    }

    public function test_creating_barang_with_duplicate_nfc_tag_is_rejected(): void
    {
        Barang::factory()->create(['kode_ntag' => '04A3B2C1D0E101']);

        $this->actingAs($this->admin())
            ->post(route('admin.barang.store'), [
                'nama' => 'Kabel LAN Duplikat',
                'kode_ntag' => '04A3B2C1D0E101',
            ])
            ->assertSessionHasErrors('kode_ntag');

        $this->assertDatabaseCount('barangs', 1);
    }

    public function test_admin_can_update_barang(): void
    {
        $barang = Barang::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.barang.update', $barang->id), [
                'nama' => 'Kamera Canon EOS',
                'kategori' => 'Elektronik',
                'kode_ntag' => $barang->kode_ntag,
                'lokasi' => 'Ruang Multimedia',
                'status' => Barang::STATUS_DIPINJAM,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barangs', [
            'id' => $barang->id,
            'nama_barang' => 'Kamera Canon EOS',
            'lokasi' => 'Ruang Multimedia',
            'status' => Barang::STATUS_DIPINJAM,
        ]);
    }

    public function test_barang_on_active_loan_cannot_be_deleted(): void
    {
        $barang = Barang::factory()->create(['kode_barang' => 'BRG-001']);
        Peminjaman::factory()->create(['kode_barang' => 'BRG-001']);

        $this->actingAs($this->admin())
            ->delete(route('admin.barang.destroy', $barang->id))
            ->assertSessionHas('error');

        $this->assertNotNull(Barang::find($barang->id));
    }

    public function test_idle_barang_can_be_deleted(): void
    {
        $barang = Barang::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.barang.destroy', $barang->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('barangs', ['id' => $barang->id]);
    }

    public function test_admin_can_update_peminjam_profile(): void
    {
        $peminjam = User::factory()->create([
            'name' => 'Budi Santoso',
            'role' => User::ROLE_USER,
            'nim' => '210810103',
            'peran' => User::PERAN_MAHASISWA,
            'status_akun' => User::AKUN_AKTIF,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.peminjam.update', $peminjam->id), [
                'nama' => 'Budi S.',
                'nim' => '210810103',
                'prodi' => 'Sistem Informasi',
                'kontak' => '0812 0000 1111',
                'role' => User::PERAN_DOSEN,
                'status' => User::AKUN_NONAKTIF,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $peminjam->id,
            'name' => 'Budi S.',
            'program_studi' => 'Sistem Informasi',
            'no_hp' => '0812 0000 1111',
            'peran' => User::PERAN_DOSEN,
            'status_akun' => User::AKUN_NONAKTIF,
        ]);
    }

    public function test_peminjam_with_active_loan_cannot_be_deleted(): void
    {
        $peminjam = User::factory()->create();
        Peminjaman::factory()->create(['user_id' => $peminjam->id, 'kode_barang' => 'BRG-007']);

        $this->actingAs($this->admin())
            ->delete(route('admin.peminjam.destroy', $peminjam->id))
            ->assertSessionHas('error');

        $this->assertNotNull(User::find($peminjam->id));
    }

    public function test_idle_peminjam_can_be_deleted(): void
    {
        $peminjam = User::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.peminjam.destroy', $peminjam->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $peminjam->id]);
    }

    public function test_non_admin_cannot_manage_barang_or_peminjam(): void
    {
        $user = User::factory()->create();
        $barang = Barang::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.barang.store'), ['nama' => 'X', 'kode_ntag' => '04A3B2C1D0E202'])
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.barang.update', $barang->id), [
                'nama' => 'X',
                'kode_ntag' => $barang->kode_ntag,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.barang.destroy', $barang->id))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.peminjam.update', $user->id), ['nama' => 'X'])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.peminjam.destroy', $user->id))
            ->assertForbidden();
    }

    public function test_data_barang_page_provides_barang_and_peminjam_props(): void
    {
        Barang::factory()->create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kode_ntag' => '04A3B2C1D0E101',
            'status' => Barang::STATUS_TERSEDIA,
        ]);

        User::factory()->create(['name' => 'Siti Aisyah', 'role' => User::ROLE_USER]);

        $this->actingAs($this->admin())
            ->get(route('admin.dataBarangPeminjaman'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/DataBarang')
                ->where('initialTab', 'barang')
                ->has('barang')
                ->where('barang.0.kode', 'BRG-001')
                ->where('barang.0.status', 'Tersedia')
                ->has('peminjam')
                ->where('peminjam.0.nama', 'Siti Aisyah'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
