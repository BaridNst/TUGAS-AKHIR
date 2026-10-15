<?php

namespace App\Support;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Persetujuan pengajuan oleh admin.
 *
 * Setiap persetujuan menulis tiga sekaligus agar data tetap sinkron:
 *
 * 1. baris transaksi baru di `peminjaman` / `pengembalian`,
 * 2. pengajuan berpindah ke status disetujui (tidak lagi di antrean),
 * 3. status master barang di `barangs` ikut disesuaikan.
 *
 * Pengajuan yang ditolak tidak menyentuh ketiganya, sehingga pengajuan ditolak
 * tidak pernah menjadi data transaksi.
 */
class Persetujuan
{
    public const STATUS_TEPAT = 'Tepat Waktu';

    public const STATUS_TERLAMBAT = 'Terlambat';

    /**
     * Setujui pengajuan peminjaman dan pindahkan ke tabel `peminjaman`.
     *
     * Jadwal pinjam (tanggal & jam mulai-selesai) ditentukan admin saat
     * menyetujui, bukan durasi tetap.
     *
     * @param  array<string, mixed>  $item  pengajuan dari PengajuanStore
     * @param  array<string, mixed>  $jadwal  tanggal_mulai, tanggal_selesai, jam_mulai, jam_selesai
     */
    public static function peminjaman(array $item, ?int $idPengelola = null, array $jadwal = []): Peminjaman
    {
        return DB::transaction(function () use ($item, $idPengelola, $jadwal): Peminjaman {
            $barang = Barang::query()->where('kode_barang', (string) $item['kode'])->first();
            $tanggalPinjam = Carbon::parse((string) ($jadwal['tanggal_mulai'] ?? $item['tanggal']));
            $batasKembali = Carbon::parse((string) ($jadwal['tanggal_selesai'] ?? $jadwal['tanggal_mulai'] ?? $item['tanggal']));

            $peminjaman = Peminjaman::query()->create([
                'pengajuan_id' => (string) $item['id'],
                'user_id' => self::idOrNull($item['user_id'] ?? null),
                'barang_id' => $barang?->id,
                'peminjam' => (string) $item['peminjam'],
                'nim' => $item['nim'] ?? null,
                'kode_barang' => (string) $item['kode'],
                'nama_barang' => (string) $item['nama'],
                'kategori' => $item['kategori'] ?? null,
                'tanggal_pinjam' => $tanggalPinjam,
                'jam_pinjam' => (string) ($jadwal['jam_mulai'] ?? $item['jam']),
                'jam_selesai' => $jadwal['jam_selesai'] ?? $item['jamSelesai'] ?? null,
                'batas_kembali' => $batasKembali,
                'status' => Peminjaman::STATUS_DIPINJAM,
                'keperluan' => $item['keperluan'] ?? null,
            ]);

            PengajuanStore::setujui(PengajuanStore::JENIS_PINJAM, (string) $item['id'], $idPengelola);

            $barang?->update(['status' => Barang::STATUS_DIPINJAM]);

            return $peminjaman;
        });
    }

    /**
     * Setujui pengajuan pengembalian dan pindahkan ke tabel `pengembalian`.
     *
     * Hasil ketepatan waktu dihitung dari tanggal kembali dibanding tenggat
     * peminjaman yang berjalan.
     *
     * @param  array<string, mixed>  $item  pengajuan dari PengajuanStore
     */
    public static function pengembalian(array $item, ?int $idPengelola = null): Pengembalian
    {
        return DB::transaction(function () use ($item, $idPengelola): Pengembalian {
            $barang = Barang::query()->where('kode_barang', (string) $item['kode'])->first();
            $pinjam = self::peminjamanBerjalan($item);
            $tanggalKembali = Carbon::parse($item['tanggal']);

            $pengembalian = Pengembalian::query()->create([
                'pengajuan_id' => (string) $item['id'],
                'peminjaman_id' => $pinjam?->id,
                'user_id' => self::idOrNull($item['user_id'] ?? null),
                'barang_id' => $barang?->id,
                'peminjam' => (string) $item['peminjam'],
                'nim' => $item['nim'] ?? null,
                'kode_barang' => (string) $item['kode'],
                'nama_barang' => (string) $item['nama'],
                'kategori' => $item['kategori'] ?? null,
                'tanggal_kembali' => $tanggalKembali,
                'jam_kembali' => (string) $item['jam'],
                'catatan' => $item['catatan'] ?? null,
                'status' => self::hasilKetepatan($pinjam, $tanggalKembali),
            ]);

            PengajuanStore::setujui(PengajuanStore::JENIS_KEMBALI, (string) $item['id'], $idPengelola);

            $pinjam?->update(['status' => Peminjaman::STATUS_SELESAI]);
            $barang?->update(['status' => Barang::STATUS_TERSEDIA]);

            return $pengembalian;
        });
    }

    /**
     * Peminjaman yang sedang berjalan untuk barang pada pengajuan pengembalian.
     *
     * @param  array<string, mixed>  $item
     */
    private static function peminjamanBerjalan(array $item): ?Peminjaman
    {
        if (isset($item['peminjaman_id']) && $item['peminjaman_id'] !== null) {
            return Peminjaman::query()->find((int) $item['peminjaman_id']);
        }

        return Peminjaman::sedangDipinjam()
            ->first(fn (Peminjaman $pinjam): bool => $pinjam->kode_barang === (string) $item['kode']);
    }

    private static function hasilKetepatan(?Peminjaman $pinjam, Carbon $tanggalKembali): string
    {
        if ($pinjam === null || $pinjam->batas_kembali === null) {
            return self::STATUS_TEPAT;
        }

        return $tanggalKembali->startOfDay()->greaterThan($pinjam->batas_kembali->startOfDay())
            ? self::STATUS_TERLAMBAT
            : self::STATUS_TEPAT;
    }

    private static function idOrNull(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) $nilai;
    }
}
