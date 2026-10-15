<?php

namespace App\Http\Controllers;

use App\Support\BarangStatus;
use App\Support\PengajuanStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeminjamanController extends Controller
{
    /**
     * Tampilkan halaman siap-scan NFC untuk memulai peminjaman.
     */
    public function index(): Response
    {
        return Inertia::render('User/Peminjaman', [
            'barang' => $this->barangTersedia(),
        ]);
    }

    /**
     * Buat pengajuan peminjaman dari tag NFC yang dipindai peminjam.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'nfc_uid' => ['required', 'string', 'max:64'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $barang = BarangStatus::dariTagNfc($validated['nfc_uid']);

        if ($barang === null) {
            return back()->with('error', 'Tag NFC tidak dikenali.');
        }

        if (! BarangStatus::tersedia($barang['kode'])) {
            return back()->with('error', "Barang {$barang['nama']} sedang dipinjam peminjam lain.");
        }

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($this->sudahDiajukan($user->id, (string) $barang['kode'])) {
            return back()->with('error', "Pengajuan peminjaman {$barang['nama']} masih menunggu persetujuan admin.");
        }

        PengajuanStore::tambah(PengajuanStore::JENIS_PINJAM, [
            'user_id' => $user->id,
            'peminjam' => $user->name,
            'nim' => $user->nim,
            'kode' => $barang['kode'],
            'nama' => $barang['nama'],
            'kategori' => $barang['kategori'] ?? null,
            'tanggal' => now()->format('Y-m-d'),
            'jam' => now()->format('H:i'),
            'keperluan' => $validated['catatan'] ?? null,
        ]);

        return back()->with('success', "Pengajuan peminjaman {$barang['nama']} menunggu persetujuan admin.");
    }

    /**
     * Barang yang masih bisa diajukan peminjaman oleh akun ini.
     *
     * @return array<int, array<string, mixed>>
     */
    private function barangTersedia(): array
    {
        $items = BarangStatus::daftar();

        return array_values(array_filter(
            $items,
            fn (array $item) => ($item['status'] ?? null) === BarangStatus::READY
        ));
    }

    private function sudahDiajukan(int $userId, string $kode): bool
    {
        foreach (PengajuanStore::antrean(PengajuanStore::JENIS_PINJAM) as $item) {
            if ($item['status'] === PengajuanStore::STATUS_PENDING
                && $item['user_id'] === $userId
                && $item['kode'] === $kode) {
                return true;
            }
        }

        return false;
    }
}
