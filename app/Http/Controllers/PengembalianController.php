<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Support\BarangStatus;
use App\Support\PengajuanStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PengembalianController extends Controller
{
    /**
     * Tampilkan halaman siap-scan NFC untuk memulai pengembalian.
     */
    public function index(): Response
    {
        return Inertia::render('User/Pengembalian', [
            'barang' => $this->barangDipinjamAkunIni(),
        ]);
    }

    /**
     * Buat pengajuan pengembalian dari tag NFC yang dipindai peminjam.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'nfc_uid' => ['required', 'string', 'max:64'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $barang = BarangStatus::dariTagNfc($validated['nfc_uid']);

        if ($barang === null) {
            return back()->with('error', 'Tag NFC tidak dikenali.');
        }

        $pinjam = Peminjaman::sedangDipinjam()
            ->first(fn (Peminjaman $pinjam) => $pinjam->kode_barang === $barang['kode']);

        if ($pinjam === null) {
            return back()->with('error', "Barang {$barang['nama']} tidak sedang dipinjam.");
        }

        if ($pinjam->user_id !== $user->id) {
            return back()->with('error', "Barang {$barang['nama']} bukan pinjaman Anda.");
        }

        if ($this->sudahDiajukan($user->id, (string) $barang['kode'])) {
            return back()->with('error', "Pengajuan pengembalian {$barang['nama']} masih menunggu persetujuan admin.");
        }

        PengajuanStore::tambah(PengajuanStore::JENIS_KEMBALI, [
            'user_id' => $user->id,
            'peminjam' => $user->name,
            'nim' => $user->nim,
            'kode' => $barang['kode'],
            'nama' => $barang['nama'],
            'kategori' => $barang['kategori'] ?? null,
            'tanggal' => now()->format('Y-m-d'),
            'jam' => now()->format('H:i'),
            'catatan' => $validated['catatan'] ?? null,
            'peminjaman_id' => $pinjam->id,
        ]);

        return back()->with('success', "Pengajuan pengembalian {$barang['nama']} menunggu persetujuan admin.");
    }

    /**
     * Barang milik akun ini yang sedang dipinjam dan bisa diajukan pengembalian.
     *
     * @return array<int, array<string, mixed>>
     */
    private function barangDipinjamAkunIni(): array
    {
        $userId = auth()->id();
        $tag = Collection::make(BarangStatus::daftar())->keyBy('kode');

        return Peminjaman::sedangDipinjam()
            ->filter(fn (Peminjaman $pinjam) => $pinjam->user_id === $userId)
            ->map(fn (Peminjaman $pinjam) => [
                'kode' => $pinjam->kode_barang,
                'nama' => $pinjam->nama_barang,
                'kategori' => $pinjam->kategori,
                'nfcUid' => $tag->get($pinjam->kode_barang)['nfcUid'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function sudahDiajukan(int $userId, string $kode): bool
    {
        foreach (PengajuanStore::antrean(PengajuanStore::JENIS_KEMBALI) as $item) {
            if ($item['status'] === PengajuanStore::STATUS_PENDING
                && $item['user_id'] === $userId
                && $item['kode'] === $kode) {
                return true;
            }
        }

        return false;
    }
}
