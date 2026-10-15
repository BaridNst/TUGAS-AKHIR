<?php

namespace App\Support;

use App\Models\Peminjaman;
use App\Models\Pengembalian;

/**
 * Riwayat transaksi milik satu akun, digabung dari tabel `peminjaman`
 * (aksi Pinjam) dan `pengembalian` (aksi Kembali).
 *
 * Baris hanya memuat data transaksi milik pengguna yang bersangkutan dan tidak
 * pernah menyertakan `user_id`.
 */
class RiwayatTransaksi
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function milikPengguna(int $userId): array
    {
        $pinjam = Peminjaman::query()
            ->where('user_id', $userId)
            ->get()
            ->map(fn (Peminjaman $pinjam): array => [
                'tanggal' => $pinjam->tanggal_pinjam->format('Y-m-d'),
                'jam' => $pinjam->jam_pinjam?->format('H:i') ?? '',
                'aksi' => 'Pinjam',
                'kode' => $pinjam->kode_barang,
                'nama' => $pinjam->nama_barang,
                'kategori' => $pinjam->kategori,
                'status' => $pinjam->status === Peminjaman::STATUS_SELESAI ? 'Selesai' : 'Berjalan',
            ]);

        $kembali = Pengembalian::query()
            ->where('user_id', $userId)
            ->get()
            ->map(fn (Pengembalian $kembali): array => [
                'tanggal' => $kembali->tanggal_kembali->format('Y-m-d'),
                'jam' => $kembali->jam_kembali?->format('H:i') ?? '',
                'aksi' => 'Kembali',
                'kode' => $kembali->kode_barang,
                'nama' => $kembali->nama_barang,
                'kategori' => $kembali->kategori,
                'status' => 'Selesai',
            ]);

        return $pinjam
            ->concat($kembali)
            ->sortByDesc(fn (array $riwayat): string => $riwayat['tanggal'].' '.$riwayat['jam'])
            ->values()
            ->all();
    }

    /**
     * Seluruh transaksi lintas pengguna (untuk riwayat admin), diurutkan dari
     * transaksi terbaru.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function semua(): array
    {
        $pinjam = Peminjaman::query()
            ->get()
            ->map(fn (Peminjaman $pinjam): array => [
                'tanggal' => $pinjam->tanggal_pinjam->format('Y-m-d'),
                'jam' => $pinjam->jam_pinjam?->format('H:i') ?? '',
                'aksi' => 'Pinjam',
                'kode' => $pinjam->kode_barang,
                'nama' => $pinjam->nama_barang,
                'kategori' => $pinjam->kategori,
                'peminjam' => $pinjam->peminjam,
                'nim' => $pinjam->nim,
                'status' => $pinjam->status === Peminjaman::STATUS_SELESAI ? 'Selesai' : 'Berjalan',
            ]);

        $kembali = Pengembalian::query()
            ->get()
            ->map(fn (Pengembalian $kembali): array => [
                'tanggal' => $kembali->tanggal_kembali->format('Y-m-d'),
                'jam' => $kembali->jam_kembali?->format('H:i') ?? '',
                'aksi' => 'Kembali',
                'kode' => $kembali->kode_barang,
                'nama' => $kembali->nama_barang,
                'kategori' => $kembali->kategori,
                'peminjam' => $kembali->peminjam,
                'nim' => $kembali->nim,
                'status' => 'Selesai',
            ]);

        return $pinjam
            ->concat($kembali)
            ->sortByDesc(fn (array $riwayat): string => $riwayat['tanggal'].' '.$riwayat['jam'])
            ->values()
            ->all();
    }
}
