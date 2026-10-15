<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminMenuHalamanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        foreach (['admin/riwayat', 'admin/prediksi', 'admin/denda', 'admin/notifikasi', 'admin/pengguna', 'admin/pengaturan'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_riwayat_lists_transactions_from_database(): void
    {
        Peminjaman::factory()->create([
            'peminjam' => 'Dimas Saputra',
            'nim' => '2210512007',
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.riwayat'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Riwayat')
                ->has('riwayat', 1)
                ->where('riwayat.0.aksi', 'Pinjam')
                ->where('riwayat.0.peminjam', 'Dimas Saputra')
                ->where('riwayat.0.nim', '2210512007')
                ->where('riwayat.0.kode', 'BRG-001'));
    }

    public function test_pengguna_lists_all_accounts(): void
    {
        $this->actingAs($this->admin());

        User::factory()->create([
            'name' => 'Budi Santoso',
            'peran' => User::PERAN_MAHASISWA,
            'status_akun' => User::AKUN_AKTIF,
        ]);

        $this->get(route('admin.pengguna'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Pengguna')
                ->has('pengguna', 2)
                ->where('pengguna.1.nama', 'Budi Santoso')
                ->where('pengguna.1.peran', User::PERAN_MAHASISWA));
    }

    public function test_denda_starts_empty_without_transactions(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.denda'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Denda')
                ->has('denda.baris', 0)
                ->where('denda.ringkas.belumKembali', 0)
                ->where('denda.ringkas.selesaiTerlambat', 0)
                ->where('denda.ringkas.totalHari', 0));
    }

    public function test_denda_counts_active_loan_past_due(): void
    {
        Peminjaman::factory()->create([
            'peminjam' => 'Siti Aisyah',
            'nim' => '2210512011',
            'batas_kembali' => now()->subDays(3)->toDateString(),
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.denda'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Denda')
                ->has('denda.baris', 1)
                ->where('denda.baris.0.peminjam', 'Siti Aisyah')
                ->where('denda.baris.0.hari', 3)
                ->where('denda.baris.0.jenis', 'Belum Kembali')
                ->where('denda.ringkas.belumKembali', 1)
                ->where('denda.ringkas.totalHari', 3));
    }

    public function test_notifikasi_page_returns_empty_list_without_data(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.notifikasi'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Notifikasi')
                ->has('notifikasi', 0));
    }

    public function test_prediksi_starts_empty_without_active_loans(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.prediksi'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Prediksi')
                ->has('prediksi', 0));
    }

    public function test_prediksi_flags_active_loan_past_due_as_high_risk(): void
    {
        Peminjaman::factory()->create([
            'peminjam' => 'Ahmad Rizki Pratama',
            'nim' => '2210512001',
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'kategori' => 'Elektronik',
            'batas_kembali' => now()->subDays(2)->toDateString(),
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.prediksi'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Prediksi')
                ->has('prediksi', 1)
                ->where('prediksi.0.peminjam', 'Ahmad Rizki Pratama')
                ->where('prediksi.0.nim', '2210512001')
                ->where('prediksi.0.nama', 'Card Reader NFC ACR122U')
                ->where('prediksi.0.sisaHari', -2)
                ->where('prediksi.0.label', 'Terlambat')
                ->where('prediksi.0.probabilitas', 0.74)
                ->where('prediksi.0.aksi', 'Kirim Notifikasi'));
    }

    public function test_prediksi_marks_upcoming_loan_as_on_time(): void
    {
        Peminjaman::factory()->create([
            'peminjam' => 'Dimas Saputra',
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'batas_kembali' => now()->addDays(5)->toDateString(),
            'status' => Peminjaman::STATUS_DIPINJAM,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.prediksi'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Prediksi')
                ->has('prediksi', 1)
                ->where('prediksi.0.sisaHari', 5)
                ->where('prediksi.0.label', 'Tepat Waktu')
                ->where('prediksi.0.probabilitas', 0.24)
                ->where('prediksi.0.aksi', '-'));
    }

    public function test_pengaturan_shows_system_summary(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.pengaturan'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Pengaturan')
                ->has('sistem', 5)
                ->where('sistem.0.label', 'Batas peminjaman')
                ->where('sistem.0.nilai', 'Ditentukan admin')
                ->where('sistem.1.label', 'Metode identifikasi')
                ->where('sistem.1.nilai', 'NFC / NTAG'));
    }

    private function admin(): User
    {
        return User::factory()->create(['name' => 'Admin', 'role' => User::ROLE_ADMIN]);
    }
}
