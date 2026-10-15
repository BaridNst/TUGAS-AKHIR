<?php

namespace App\Support;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Support\Collection;

/**
 * Penyesuaian status barang dengan peminjaman yang sudah disetujui.
 *
 * Daftar barang diambil dari tabel `barangs` (diisi lewat halaman Data Barang
 * admin), sedangkan status turunannya dihitung dari transaksi di
 * database:
 *
 * - ada peminjaman disetujui yang belum ditutup pengembalian → `dipinjam`,
 * - sudah ada pengembalian disetujui → `ready`,
 * - belum ada transaksi → status bawaan dari kolom `barangs.status`.
 */
class BarangStatus
{
    public const READY = 'ready';

    public const DIPINJAM = 'dipinjam';

    /**
     * Seluruh barang lengkap dengan status turunan dari transaksi.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftar(): array
    {
        $items = Barang::query()
            ->orderBy('kode_barang')
            ->get()
            ->map(fn (Barang $barang): array => self::payload($barang))
            ->all();

        return self::terapkan($items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function terapkan(array $items): array
    {
        $sedangDipinjam = Peminjaman::sedangDipinjam()->keyBy('kode_barang');
        $sudahDikembalikan = Pengembalian::query()->pluck('kode_barang')->unique()->all();

        return Collection::make($items)->map(function (array $item) use ($sedangDipinjam, $sudahDikembalikan) {
            $kode = $item['kode'] ?? null;
            $pinjam = is_string($kode) ? $sedangDipinjam->get($kode) : null;

            if ($pinjam !== null) {
                $item['status'] = self::DIPINJAM;
                $item['peminjam'] = ['nama' => $pinjam->peminjam, 'nim' => $pinjam->nim];
                $item['jamSelesai'] = $pinjam->jam_selesai?->format('H:i');

                return $item;
            }

            if (is_string($kode) && in_array($kode, $sudahDikembalikan, true)) {
                $item['status'] = self::READY;
                $item['peminjam'] = null;
                $item['jamSelesai'] = null;
            }

            return $item;
        })->all();
    }

    /**
     * Apakah barang masih bisa dipinjam?
     */
    public static function tersedia(string $kode): bool
    {
        $item = Collection::make(self::daftar())
            ->first(fn (array $item): bool => $item['kode'] === $kode);

        if ($item === null) {
            return true;
        }

        return ($item['status'] ?? self::READY) === self::READY;
    }

    /**
     * Cari barang berdasarkan uid tag NFC.
     *
     * @return array<string, mixed>|null
     */
    public static function dariTagNfc(string $nfcUid): ?array
    {
        if (trim($nfcUid) === '') {
            return null;
        }

        $barang = Barang::dariNfcUid($nfcUid);

        if ($barang === null) {
            return null;
        }

        $item = self::payload($barang);

        return self::terapkan([$item])[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function payload(Barang $barang): array
    {
        return [
            'id' => $barang->id,
            'kode' => $barang->kode_barang,
            'nama' => $barang->nama_barang,
            'kategori' => $barang->kategori,
            'nfcUid' => $barang->kode_ntag,
            'lokasi' => $barang->lokasi,
            'kondisi' => $barang->kondisi,
            'deskripsi' => $barang->deskripsi,
            'status' => $barang->status === Barang::STATUS_DIPINJAM ? self::DIPINJAM : self::READY,
            'peminjam' => null,
            'jamSelesai' => null,
        ];
    }
}
