<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\WhatsAppGateway;
use App\Support\BarangStatus;
use App\Support\PengajuanStore;
use App\Support\PrediksiKeterlambatan;
use App\Support\RekapKeterlambatan;
use App\Support\RiwayatTransaksi;
use App\Support\StatistikDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMenuController extends Controller
{
    /**
     * Halaman admin: Data Barang & Peminjaman (Utama).
     */
    public function dataBarangPeminjaman(): Response
    {
        return Inertia::render('Admin/DataBarang', [
            'initialTab' => 'barang',
            ...$this->dataTabel(),
        ]);
    }

    /**
     * Halaman admin: Tab Data Barang.
     */
    public function dataBarangTab(): Response
    {
        return Inertia::render('Admin/DataBarang', [
            'initialTab' => 'barang',
            ...$this->dataTabel(),
        ]);
    }

    /**
     * Halaman admin: Tab Data Peminjaman.
     */
    public function dataPeminjamanTab(): Response
    {
        return Inertia::render('Admin/DataBarang', [
            'initialTab' => 'peminjaman',
            ...$this->dataTabel(),
        ]);
    }

    /**
     * Halaman admin: Data Barang (Legacy).
     */
    public function dataBarang(): Response
    {
        return Inertia::render('Admin/DataBarang', [
            'initialTab' => 'barang',
        ]);
    }

    /**
     * Halaman admin: Data Peminjam (Legacy).
     */
    public function dataPeminjam(): Response
    {
        return Inertia::render('Admin/DataBarang', [
            'initialTab' => 'peminjaman',
        ]);
    }

    /**
     * Halaman admin: Riwayat seluruh transaksi lintas pengguna.
     */
    public function riwayat(): Response
    {
        return Inertia::render('Admin/Riwayat', [
            'riwayat' => RiwayatTransaksi::semua(),
        ]);
    }

    /**
     * Halaman admin: Prediksi peminjaman berbasis UID NFC (model ML).
     */
    public function prediksi(): Response
    {
        return Inertia::render('Admin/Prediksi', [
            'prediksi' => PrediksiKeterlambatan::daftarAktif(),
        ]);
    }

    /**
     * Halaman admin: Rekap keterlambatan pengembalian.
     */
    public function denda(): Response
    {
        return Inertia::render('Admin/Denda', [
            'denda' => RekapKeterlambatan::ringkas(),
        ]);
    }

    /**
     * Halaman admin: Notifikasi sistem.
     */
    public function notifikasi(): Response
    {
        return Inertia::render('Admin/Notifikasi', [
            'notifikasi' => StatistikDashboard::notifikasi(12),
        ]);
    }

    /**
     * Halaman admin: Daftar akun pengguna.
     */
    public function pengguna(): Response
    {
        return Inertia::render('Admin/Pengguna', [
            'pengguna' => User::query()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'nama' => $user->name,
                    'email' => $user->email,
                    'nim' => $user->nim,
                    'prodi' => $user->program_studi,
                    'kontak' => $user->no_hp,
                    'status' => $user->status_akun,
                    'peran' => $user->peran,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * Kirim notifikasi WhatsApp untuk transaksi yang dipilih.
     */
    public function kirimWhatsApp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pengajuan_id' => ['required', 'string'],
            'jenis' => ['required', 'string', 'in:terlambat_ringan,terlambat_berat,barang_rusak,barang_hilang,pengingat_batas'],
        ]);

        $pinjam = Peminjaman::where('pengajuan_id', $validated['pengajuan_id'])->first();

        if (! $pinjam) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan',
            ], 404);
        }

        $kontak = $pinjam->user_id
            ? User::find($pinjam->user_id)?->no_hp
            : null;

        if (! $kontak) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor telepon peminjam tidak tersedia',
            ], 400);
        }

        $data = NotifikasiService::siapkanDataTemplate($pinjam);
        $pesan = WhatsAppGateway::render($validated['jenis'], $data);
        $kirim = WhatsAppGateway::send($kontak, $pesan);

        return response()->json([
            'success' => $kirim['success'],
            'message' => $kirim['message'],
            'pesan' => $pesan,
            'nomor' => $kontak,
            'jenis' => $validated['jenis'],
        ]);
    }

    /**
     * Halaman admin: Ringkasan pengaturan sistem.
     */
    public function pengaturan(): Response
    {
        return Inertia::render('Admin/Pengaturan', [
            'sistem' => [
                [
                    'label' => 'Batas peminjaman',
                    'nilai' => 'Ditentukan admin',
                    'keterangan' => 'Tanggal mulai s/d tanggal selesai beserta jam pinjam ditentukan admin saat menyetujui pengajuan.',
                ],
                [
                    'label' => 'Metode identifikasi',
                    'nilai' => 'NFC / NTAG',
                    'keterangan' => 'Peminjaman, pengembalian, dan pendaftaran barang memakai pemindaian stiker NFC.',
                ],
                [
                    'label' => 'Total barang',
                    'nilai' => (string) Barang::query()->count(),
                    'keterangan' => 'Jumlah barang terdaftar beserta stikernya.',
                ],
                [
                    'label' => 'Total akun',
                    'nilai' => (string) User::query()->count(),
                    'keterangan' => 'Seluruh akun pengguna yang terdaftar pada aplikasi.',
                ],
                [
                    'label' => 'Antrean pengajuan',
                    'nilai' => (string) Pengajuan::query()->where('status', PengajuanStore::STATUS_PENDING)->count(),
                    'keterangan' => 'Pengajuan peminjaman & pengembalian yang menunggu persetujuan.',
                ],
            ],
        ]);
    }

    /**
     * Data tabel halaman Data Barang & Peminjaman: daftar barang lengkap
     * dengan status turunan dari transaksi, serta daftar peminjam dari
     * tabel users.
     *
     * @return array{barang: array<int, array<string, mixed>>, peminjam: array<int, array<string, mixed>>}
     */
    private function dataTabel(): array
    {
        return [
            'barang' => collect(BarangStatus::daftar())
                ->map(fn (array $item): array => [
                    'id' => $item['id'],
                    'kode' => $item['kode'],
                    'nama' => $item['nama'],
                    'kategori' => $item['kategori'],
                    'kode_ntag' => $item['nfcUid'],
                    'lokasi' => $item['lokasi'],
                    'kondisi' => $item['kondisi'],
                    'deskripsi' => $item['deskripsi'],
                    'status' => $item['status'] === BarangStatus::DIPINJAM
                        ? Barang::STATUS_DIPINJAM
                        : Barang::STATUS_TERSEDIA,
                    'peminjam' => $item['peminjam'],
                    'jamSelesai' => $item['jamSelesai'],
                ])
                ->values()
                ->all(),
            'peminjam' => User::query()
                ->where('role', '!=', User::ROLE_ADMIN)
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'nama' => $user->name,
                    'nim' => $user->nim,
                    'prodi' => $user->program_studi,
                    'kontak' => $user->no_hp,
                    'status' => $user->status_akun,
                    'role' => $user->peran,
                ])
                ->values()
                ->all(),
        ];
    }
}
