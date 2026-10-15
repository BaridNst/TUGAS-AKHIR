<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RiwayatPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $pemilik = User::factory()->create(['id' => 1]);
        $lain = User::factory()->create(['id' => 2]);

        Peminjaman::factory()->create([
            'user_id' => $pemilik->id,
            'kode_barang' => 'BRG-009',
            'nama_barang' => 'Kartu RFID MIFARE Classic 1K',
            'tanggal_pinjam' => '2026-09-24',
            'jam_pinjam' => '13:30',
            'status' => Peminjaman::STATUS_SELESAI,
        ]);

        Pengembalian::factory()->create([
            'user_id' => $pemilik->id,
            'kode_barang' => 'BRG-009',
            'nama_barang' => 'Kartu RFID MIFARE Classic 1K',
            'tanggal_kembali' => '2026-09-25',
            'jam_kembali' => '07:55',
        ]);

        Peminjaman::factory()->create([
            'user_id' => $pemilik->id,
            'kode_barang' => 'BRG-004',
            'nama_barang' => 'Raspberry Pi 4B 4GB',
            'tanggal_pinjam' => '2026-09-23',
            'jam_pinjam' => '10:10',
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);

        Peminjaman::factory()->create([
            'user_id' => $lain->id,
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'tanggal_pinjam' => '2026-09-26',
            'jam_pinjam' => '09:00',
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/riwayat')->assertRedirect(route('login'));
    }

    public function test_riwayat_page_lists_only_own_transactions(): void
    {
        $this->actingAs(User::query()->findOrFail(1))
            ->get('/riwayat')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Riwayat')
                ->has('riwayat', 3)
                ->where('riwayat.0.tanggal', '2026-09-25')
                ->where('riwayat.0.aksi', 'Kembali')
                ->where('riwayat.0.kode', 'BRG-009')
                ->where('riwayat.0.status', 'Selesai')
                ->where('riwayat.1.aksi', 'Pinjam')
                ->where('riwayat.1.status', 'Selesai')
                ->where('riwayat.2.kode', 'BRG-004')
                ->where('riwayat.2.status', 'Berjalan')
                ->missing('riwayat.0.user_id'));
    }

    public function test_other_account_sees_only_its_own_transactions(): void
    {
        $this->actingAs(User::query()->findOrFail(2))
            ->get('/riwayat')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Riwayat')
                ->has('riwayat', 1)
                ->where('riwayat.0.kode', 'BRG-002')
                ->where('riwayat.0.status', 'Berjalan')
                ->missing('riwayat.0.user_id'));
    }

    public function test_account_without_transactions_sees_empty_state(): void
    {
        $this->actingAs(User::factory()->create(['id' => 99]))
            ->get('/riwayat')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Riwayat')
                ->has('riwayat', 0));
    }

    public function test_dashboard_includes_own_riwayat_preview(): void
    {
        $this->actingAs(User::query()->findOrFail(1))
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Dashboard')
                ->has('riwayat', 3)
                ->where('riwayat.0.kode', 'BRG-009')
                ->where('riwayat.0.aksi', 'Kembali'));
    }
}
