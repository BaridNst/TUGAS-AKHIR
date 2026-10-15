<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Pengajuan;
use App\Models\User;
use App\Support\PengajuanStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kelola data peminjam (akun pengguna berstatus Mahasiswa/Dosen).
 *
 * Peminjam menambahkan dirinya sendiri lewat pendaftaran akun, sehingga admin
 * hanya boleh memperbarui profil dan menonaktifkan/menghapus akun.
 */
class PeminjamController extends Controller
{
    /**
     * Perbarui profil peminjam yang dipilih admin.
     */
    public function update(Request $request, User $peminjam): RedirectResponse
    {
        $this->harusAdmin($request);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nim' => ['nullable', 'string', 'max:30', Rule::unique('users', 'nim')->ignore($peminjam->id)],
            'prodi' => ['nullable', 'string', 'max:120'],
            'kontak' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', Rule::in([User::PERAN_MAHASISWA, User::PERAN_DOSEN])],
            'status' => ['nullable', Rule::in([User::AKUN_AKTIF, User::AKUN_NONAKTIF])],
        ]);

        $peminjam->update([
            'name' => $validated['nama'],
            'nim' => $validated['nim'] ?? null,
            'program_studi' => $validated['prodi'] ?? null,
            'no_hp' => $validated['kontak'] ?? null,
            'peran' => $validated['role'] ?? $peminjam->peran,
            'status_akun' => $validated['status'] ?? $peminjam->status_akun,
        ]);

        return back()->with('success', "Data peminjam {$peminjam->name} berhasil diperbarui.");
    }

    /**
     * Hapus peminjam yang tidak sedang meminjam dan tidak punya pengajuan.
     */
    public function destroy(Request $request, User $peminjam): RedirectResponse
    {
        $this->harusAdmin($request);

        if ($peminjam->id === $request->user()?->id) {
            return back()->with('error', 'Akun yang sedang login tidak bisa dihapus dari halaman ini.');
        }

        if ($peminjam->isAdmin()) {
            return back()->with('error', 'Akun admin tidak bisa dihapus dari halaman ini.');
        }

        if (Peminjaman::sedangDipinjam()->contains(fn (Peminjaman $pinjam): bool => $pinjam->user_id === $peminjam->id)) {
            return back()->with('error', "Peminjam {$peminjam->name} masih memiliki pinjaman aktif.");
        }

        $adaPengajuan = Pengajuan::query()
            ->where('user_id', $peminjam->id)
            ->where('status', PengajuanStore::STATUS_PENDING)
            ->exists();

        if ($adaPengajuan) {
            return back()->with('error', "Peminjam {$peminjam->name} masih memiliki pengajuan yang menunggu persetujuan.");
        }

        $nama = $peminjam->name;
        $peminjam->delete();

        return back()->with('success', "Data peminjam {$nama} berhasil dihapus.");
    }

    private function harusAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
