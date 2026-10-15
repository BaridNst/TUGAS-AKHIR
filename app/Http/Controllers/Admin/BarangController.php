<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Peminjaman;
use App\Support\NfcUid;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BarangController extends Controller
{
    /**
     * Tambah barang inventaris baru; kode barang dibuat sistem (BRG-001, ...).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->harusAdmin($request);

        $this->normalisasiNtag($request);

        $validated = $this->validasi($request);

        $barang = Barang::query()->create([
            'kode_barang' => Barang::kodeBaru(),
            'nama_barang' => $validated['nama'],
            'kategori' => $validated['kategori'] ?? null,
            'kode_ntag' => $validated['kode_ntag'],
            'lokasi' => $validated['lokasi'] ?? null,
            'kondisi' => 'Baik',
            'status' => $validated['status'] ?? Barang::STATUS_TERSEDIA,
            'deskripsi' => null,
        ]);

        return back()->with('success', "Barang {$barang->nama_barang} berhasil ditambahkan.");
    }

    /**
     * Perbarui data barang yang dipilih admin.
     */
    public function update(Request $request, Barang $barang): RedirectResponse
    {
        $this->harusAdmin($request);

        $this->normalisasiNtag($request);

        $validated = $this->validasi($request, $barang);

        $barang->update([
            'nama_barang' => $validated['nama'],
            'kategori' => $validated['kategori'] ?? null,
            'kode_ntag' => $validated['kode_ntag'],
            'lokasi' => $validated['lokasi'] ?? null,
            'status' => $validated['status'] ?? $barang->status,
        ]);

        return back()->with('success', "Barang {$barang->nama_barang} berhasil diperbarui.");
    }

    /**
     * Hapus barang yang tidak sedang dipinjam.
     */
    public function destroy(Request $request, Barang $barang): RedirectResponse
    {
        $this->harusAdmin($request);

        $sedangDipinjam = Peminjaman::sedangDipinjam()
            ->contains(fn (Peminjaman $pinjam): bool => $pinjam->kode_barang === $barang->kode_barang);

        if ($sedangDipinjam) {
            return back()->with('error', "Barang {$barang->nama_barang} masih dipinjam dan belum bisa dihapus.");
        }

        $nama = $barang->nama_barang;
        $barang->delete();

        return back()->with('success', "Barang {$nama} berhasil dihapus.");
    }

    /**
     * Samakan format UID stiker dengan bentuk kanonik sebelum validasi,
     * sehingga aturan unik menolak stiker yang sama dengan format berbeda.
     */
    private function normalisasiNtag(Request $request): void
    {
        $nfcUid = $request->input('kode_ntag');

        if (is_string($nfcUid)) {
            $request->merge(['kode_ntag' => NfcUid::normalisasi($nfcUid)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request, ?Barang $barang = null): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:80'],
            'kode_ntag' => ['required', 'string', 'max:40', 'regex:/^[0-9A-F]{4,40}$/', Rule::unique('barangs', 'kode_ntag')->ignore($barang)],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Barang::STATUS_TERSEDIA, Barang::STATUS_DIPINJAM])],
        ], [
            'kode_ntag.regex' => 'Kode NTAG harus berupa UID heksadesimal 4-40 karakter, contoh 04A3B2C1D0E101.',
        ]);
    }

    private function harusAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
