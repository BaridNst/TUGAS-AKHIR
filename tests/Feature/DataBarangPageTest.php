<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DataBarangPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));

        $this->get('/data-barang')->assertRedirect(route('login'));
    }

    public function test_dashboard_shows_barang_availability_table(): void
    {
        $this->siapkanBarang();

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Dashboard')
                ->has('barang', 2)
                ->where('barang.0.kode', 'BRG-001')
                ->where('barang.0.status', 'ready')
                ->where('barang.1.kode', 'BRG-002')
                ->where('barang.1.status', 'dipinjam')
                ->where('barang.1.peminjam.nim', '2210512007')
                ->where('barang.1.jamSelesai', '14:30'));
    }

    public function test_data_barang_page_lists_all_barang(): void
    {
        $this->siapkanBarang();

        $this->actingAs(User::factory()->create())
            ->get('/data-barang')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/DataBarang')
                ->has('barang', 2)
                ->where('barang.0.nama', 'Kabel LAN RJ45 5 Meter')
                ->where('barang.0.status', 'ready')
                ->where('barang.1.kode', 'BRG-002')
                ->where('barang.1.status', 'dipinjam'));
    }

    private function siapkanBarang(): void
    {
        Barang::factory()->create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kode_ntag' => '04A3B2C1D0E101',
            'status' => Barang::STATUS_TERSEDIA,
        ]);

        Barang::factory()->create([
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'kode_ntag' => '04A3B2C1D0E102',
            'status' => Barang::STATUS_DIPINJAM,
        ]);

        Peminjaman::factory()->create([
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'peminjam' => 'Dimas Saputra',
            'nim' => '2210512007',
            'jam_selesai' => '14:30',
        ]);
    }
}
