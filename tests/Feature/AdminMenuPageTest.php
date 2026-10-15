<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminMenuPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * URL menu beserta komponen yang dirender setelah mengikuti alihkan.
     *
     * `data-barang` dan `data-peminjam` adalah URL lama yang dialihkan ke
     * halaman gabungan Data Barang & Peminjaman.
     *
     * @return array<string, string>
     */
    private function menus(): array
    {
        return [
            '/admin/data-barang' => 'Admin/DataBarang',
            '/admin/data-peminjam' => 'Admin/DataBarang',
            '/admin/peminjaman' => 'Admin/Peminjaman',
            '/admin/pengembalian' => 'Admin/Pengembalian',
            '/admin/riwayat' => 'Admin/Riwayat',
            '/admin/prediksi' => 'Admin/Prediksi',
            '/admin/denda' => 'Admin/Denda',
            '/admin/notifikasi' => 'Admin/Notifikasi',
            '/admin/pengguna' => 'Admin/Pengguna',
            '/admin/pengaturan' => 'Admin/Pengaturan',
        ];
    }

    public function test_each_menu_has_its_own_url(): void
    {
        $names = [
            'admin.dataBarang' => '/admin/data-barang',
            'admin.dataPeminjam' => '/admin/data-peminjam',
            'admin.peminjaman' => '/admin/peminjaman',
            'admin.pengembalian' => '/admin/pengembalian',
            'admin.riwayat' => '/admin/riwayat',
            'admin.prediksi' => '/admin/prediksi',
            'admin.denda' => '/admin/denda',
            'admin.notifikasi' => '/admin/notifikasi',
            'admin.pengguna' => '/admin/pengguna',
            'admin.pengaturan' => '/admin/pengaturan',
        ];

        foreach ($names as $name => $url) {
            $this->assertSame($url, parse_url(route($name), PHP_URL_PATH));
        }
    }

    public function test_guest_is_redirected_to_login(): void
    {
        foreach ($this->menus() as $url => $component) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_each_menu_renders_its_own_empty_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ($this->menus() as $url => $component) {
            $response = $this->actingAs($admin)->get($url);

            for ($hop = 0; $response->isRedirect() && $hop < 3; $hop++) {
                $response = $this->get((string) $response->headers->get('Location'));
            }

            $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component($component));
        }
    }
}
