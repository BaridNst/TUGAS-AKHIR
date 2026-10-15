<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BarangStatus;
use App\Support\PengajuanStore;
use App\Support\Persetujuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeminjamanController extends Controller
{
    /**
     * Halaman admin: antrean persetujuan peminjaman.
     */
    public function index(Request $request): Response
    {
        $this->harusAdmin($request);

        return Inertia::render('Admin/Peminjaman', [
            'pengajuan' => PengajuanStore::daftar(PengajuanStore::JENIS_PINJAM),
        ]);
    }

    /**
     * Setujui pengajuan peminjaman: jadwal (tanggal & jam mulai-selesai)
     * ditentukan admin lewat popup, lalu masuk tabel `peminjaman`, barang
     * berubah menjadi sedang dipinjam, dan pengajuan keluar dari antrean.
     */
    public function approve(Request $request, string $pengajuan): RedirectResponse
    {
        $this->harusAdmin($request);

        $jadwal = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
        ]);

        if ($jadwal['tanggal_mulai'] === $jadwal['tanggal_selesai'] && $jadwal['jam_selesai'] <= $jadwal['jam_mulai']) {
            return back()->withErrors(['jam_selesai' => 'Jam selesai harus setelah jam mulai pada tanggal yang sama.']);
        }

        $item = PengajuanStore::cari(PengajuanStore::JENIS_PINJAM, $pengajuan);

        if ($item === null || $item['status'] !== PengajuanStore::STATUS_PENDING) {
            return back()->with('error', 'Pengajuan peminjaman tidak ditemukan atau sudah diputuskan.');
        }

        if (! BarangStatus::tersedia($item['kode'])) {
            return back()->with('error', "Barang {$item['nama']} sedang dipinjam peminjam lain.");
        }

        Persetujuan::peminjaman($item, $request->user()?->id, $jadwal);

        return back()->with('success', "Peminjaman {$item['nama']} disetujui.");
    }

    /**
     * Tolak pengajuan peminjaman: tetap di antrean dengan status ditolak dan
     * tidak menjadi data transaksi.
     */
    public function reject(Request $request, string $pengajuan): RedirectResponse
    {
        $this->harusAdmin($request);

        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $item = PengajuanStore::tolak(
            PengajuanStore::JENIS_PINJAM,
            $pengajuan,
            $validated['alasan'] ?? null
        );

        if ($item === null) {
            return back()->with('error', 'Pengajuan peminjaman tidak ditemukan atau sudah diputuskan.');
        }

        return back()->with('success', "Pengajuan peminjaman {$item['nama']} ditolak.");
    }

    private function harusAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
