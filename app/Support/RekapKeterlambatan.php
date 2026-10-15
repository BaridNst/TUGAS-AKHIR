<?php

namespace App\Support;

use App\Models\Peminjaman;
use App\Models\Pengembalian;

/**
 * Rekap keterlambatan pengembalian untuk halaman admin.
 *
 * Dua sumber data diambil:
 *
 * 1. peminjaman yang masih berjalan tetapi sudah melewati tenggat, dan
 * 2. pengembalian yang sudah disetujui dengan status Terlambat.
 *
 * Hari terlambat dihitung dari tenggat `batas_kembali` sampai hari ini
 * (untuk yang berjalan) atau tanggal kembali (untuk yang selesai).
 */
class RekapKeterlambatan
{
    /**
     * @return array{baris: array<int, array<string, mixed>>, ringkas: array<string, int>}
     */
    public static function ringkas(): array
    {
        $hariIni = now()->startOfDay();

        $berjalan = Peminjaman::sedangDipinjam()
            ->filter(fn (Peminjaman $pinjam): bool => $pinjam->batas_kembali !== null && $pinjam->batas_kembali->lt($hariIni))
            ->map(fn (Peminjaman $pinjam): array => [
                'peminjam' => $pinjam->peminjam,
                'nim' => $pinjam->nim,
                'kode' => $pinjam->kode_barang,
                'nama' => $pinjam->nama_barang,
                'batas' => $pinjam->batas_kembali->format('Y-m-d'),
                'hari' => (int) $pinjam->batas_kembali->startOfDay()->diffInDays($hariIni),
                'jenis' => 'Belum Kembali',
            ])
            ->values();

        $selesai = Pengembalian::query()
            ->with('peminjaman')
            ->where('status', Persetujuan::STATUS_TERLAMBAT)
            ->get()
            ->map(fn (Pengembalian $kembali): array => [
                'peminjam' => $kembali->peminjam,
                'nim' => $kembali->nim,
                'kode' => $kembali->kode_barang,
                'nama' => $kembali->nama_barang,
                'batas' => $kembali->peminjaman?->batas_kembali?->format('Y-m-d'),
                'hari' => self::hariTerlambat($kembali),
                'jenis' => 'Selesai Terlambat',
            ])
            ->values();

        $baris = $berjalan
            ->concat($selesai)
            ->sortByDesc(fn (array $baris): string => str_pad((string) $baris['hari'], 6, '0', STR_PAD_LEFT))
            ->values()
            ->all();

        return [
            'baris' => $baris,
            'ringkas' => [
                'belumKembali' => count($berjalan),
                'selesaiTerlambat' => count($selesai),
                'totalHari' => array_sum(array_column($baris, 'hari')),
            ],
        ];
    }

    private static function hariTerlambat(Pengembalian $kembali): int
    {
        $batas = $kembali->peminjaman?->batas_kembali;

        if ($batas === null) {
            return 0;
        }

        $selisih = $batas->copy()->startOfDay()->diffInDays($kembali->tanggal_kembali->copy()->startOfDay());

        return max(0, (int) $selisih);
    }
}
