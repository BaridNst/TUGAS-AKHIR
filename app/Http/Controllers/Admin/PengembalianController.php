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

class PengembalianController extends Controller
{
    /**
     * Halaman admin: antrean persetujuan pengembalian.
     */
    public function index(Request $request): Response
    {
        $this->harusAdmin($request);

        return Inertia::render('Admin/Pengembalian', [
            'pengajuan' => PengajuanStore::daftar(PengajuanStore::JENIS_KEMBALI),
        ]);
    }

    /**
     * Setujui pengajuan pengembalian: masuk tabel `pengembalian`, barang
     * kembali tersedia, dan pengajuan keluar dari antrean.
     */
    public function approve(Request $request, string $pengajuan): RedirectResponse
    {
        $this->harusAdmin($request);

        $item = PengajuanStore::cari(PengajuanStore::JENIS_KEMBALI, $pengajuan);

        if ($item === null || $item['status'] !== PengajuanStore::STATUS_PENDING) {
            return back()->with('error', 'Pengajuan pengembalian tidak ditemukan atau sudah diputuskan.');
        }

        if (BarangStatus::tersedia($item['kode'])) {
            return back()->with('error', "Barang {$item['nama']} tidak sedang dipinjam.");
        }

        Persetujuan::pengembalian($item, $request->user()?->id);

        return back()->with('success', "Pengembalian {$item['nama']} disetujui.");
    }

    /**
     * Tolak pengajuan pengembalian: tetap di antrean dengan status ditolak dan
     * tidak menjadi data transaksi.
     */
    public function reject(Request $request, string $pengajuan): RedirectResponse
    {
        $this->harusAdmin($request);

        $validated = $request->validate([
            'alasan' => ['nullable', 'string', 'max:255'],
        ]);

        $item = PengajuanStore::tolak(
            PengajuanStore::JENIS_KEMBALI,
            $pengajuan,
            $validated['alasan'] ?? null
        );

        if ($item === null) {
            return back()->with('error', 'Pengajuan pengembalian tidak ditemukan atau sudah diputuskan.');
        }

        return back()->with('success', "Pengajuan pengembalian {$item['nama']} ditolak.");
    }

    private function harusAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
