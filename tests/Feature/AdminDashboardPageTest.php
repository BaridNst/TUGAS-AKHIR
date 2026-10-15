<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use App\Support\PengajuanStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_dashboard_starts_with_zero_stats_and_empty_lists(): void
    {
        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.totalBarang', 0)
                ->where('stats.tersedia', 0)
                ->where('stats.sedangDipinjam', 0)
                ->where('stats.pengajuanPeminjaman', 0)
                ->where('stats.pengajuanPengembalian', 0)
                ->where('stats.terlambat', 0)
                ->has('grafikTransaksi.1 Tahun', 12)
                ->has('grafikTransaksi.6 Bulan Terakhir', 6)
                ->has('grafikTransaksi.3 Bulan Terakhir', 3)
                ->where('grafikTransaksi.6 Bulan Terakhir.0.peminjaman', 0)
                ->where('grafikTransaksi.6 Bulan Terakhir.0.pengembalian', 0)
                ->has('kategoriBarang', 0)
                ->has('barangTerpopuler', 0)
                ->has('pengajuanTerbaru', 0)
                ->has('notifikasi', 0)
                ->has('prediksi', 0));
    }

    public function test_dashboard_counts_are_computed_from_database(): void
    {
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

        $pinjam = Peminjaman::factory()->create([
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'peminjam' => 'Dimas Saputra',
            'nim' => '2210512007',
            'tanggal_pinjam' => now()->toDateString(),
            'batas_kembali' => now()->subDay()->toDateString(),
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);

        PengajuanStore::tambah(PengajuanStore::JENIS_PINJAM, [
            'peminjam' => 'Ahmad Rizki',
            'nim' => '2210512001',
            'kode' => 'BRG-001',
            'nama' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'tanggal' => now()->toDateString(),
            'jam' => '08:00',
            'keperluan' => 'Praktikum jaringan komputer',
        ]);

        PengajuanStore::tambah(PengajuanStore::JENIS_KEMBALI, [
            'peminjam' => 'Dimas Saputra',
            'nim' => '2210512007',
            'kode' => 'BRG-002',
            'nama' => 'Card Reader NFC ACR122U',
            'kategori' => 'NFC Reader',
            'peminjaman_id' => $pinjam->id,
            'tanggal' => now()->toDateString(),
            'jam' => '10:00',
            'catatan' => 'Kondisi baik.',
        ]);

        $this->actingAs($this->admin())
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.totalBarang', 2)
                ->where('stats.tersedia', 1)
                ->where('stats.sedangDipinjam', 1)
                ->where('stats.pengajuanPeminjaman', 1)
                ->where('stats.pengajuanPengembalian', 1)
                ->where('stats.terlambat', 1)
                ->where('grafikTransaksi.6 Bulan Terakhir.5.peminjaman', 1)
                ->has('kategoriBarang', 2)
                ->where('barangTerpopuler.0.nama', 'Card Reader NFC ACR122U')
                ->where('barangTerpopuler.0.jumlah', 1)
                ->where('pengajuanTerbaru.0.jenis', 'Pengembalian')
                ->where('pengajuanTerbaru.0.status', 'Pending')
                ->has('notifikasi', 3)
                ->where('notifikasi.0.judul', 'Pengajuan pengembalian menunggu persetujuan')
                ->where('notifikasi.2.ikon', 'alert')
                ->has('prediksi', 1)
                ->where('prediksi.0.peminjam', 'Dimas Saputra')
                ->where('prediksi.0.label', 'Terlambat')
                ->where('prediksi.0.aksi', 'Kirim Notifikasi'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
