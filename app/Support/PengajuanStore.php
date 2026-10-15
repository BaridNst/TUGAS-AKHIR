<?php

namespace App\Support;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengajuan;
use App\Models\Pengembalian;

/**
 * Antrean pengajuan peminjaman & pengembalian.
 *
 * Penyimpanan pengajuan dipisah menjadi dua lapis agar pengajuan yang ditolak
 * tidak pernah menjadi data transaksi:
 *
 * 1. Tabel `pengajuans` — semua pengajuan dari peminjam beserta putusan admin
 *    (pending, ditolak, maupun disetujui).
 * 2. Tabel `peminjaman` / `pengembalian` — hanya pengajuan yang disetujui,
 *    sehingga seluruh isinya adalah data transaksi.
 *
 * @phpstan-type Pengajuan array{
 *     id: string,
 *     user_id: int|null,
 *     peminjam: string,
 *     nim: string|null,
 *     kode: string,
 *     nama: string,
 *     kategori: string|null,
 *     tanggal: string,
 *     tanggal_selesai: string|null,
 *     jam: string,
 *     jamSelesai: string|null,
 *     keperluan: string|null,
 *     catatan: string|null,
 *     peminjaman_id: int|null,
 *     status: string,
 *     alasan: string|null,
 *     ditolak_pada: string|null,
 *     disetujui_pada: string|null
 * }
 */
class PengajuanStore
{
    public const JENIS_PINJAM = 'peminjaman';

    public const JENIS_KEMBALI = 'pengembalian';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    /**
     * Semua pengajuan: yang masih di antrean (pending/ditolak) dan yang sudah
     * disetujui (diambil dari tabel transaksi).
     *
     * @return array<int, Pengajuan>
     */
    public static function daftar(string $jenis): array
    {
        return self::urut([
            ...self::antrean($jenis),
            ...self::disetujui($jenis),
        ]);
    }

    /**
     * Pengajuan yang belum dipindahkan ke tabel transaksi.
     *
     * @return array<int, Pengajuan>
     */
    public static function antrean(string $jenis): array
    {

        $items = Pengajuan::query()
            ->where('jenis_pengajuan', $jenis)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_DITOLAK])
            ->orderBy('id')
            ->get()
            ->map(fn (Pengajuan $pengajuan): array => self::dariModel($pengajuan))
            ->all();

        return self::urut($items);
    }

    /**
     * Pengajuan pada antrean berdasarkan id, atau null bila belum pernah ada
     * maupun sudah diputuskan menjadi disetujui.
     *
     * @return Pengajuan|null
     */
    public static function cari(string $jenis, string $id): ?array
    {

        $pengajuan = Pengajuan::query()
            ->where('jenis_pengajuan', $jenis)
            ->where('pengajuan_id', $id)
            ->first();

        if ($pengajuan === null || ! in_array($pengajuan->status, [self::STATUS_PENDING, self::STATUS_DITOLAK], true)) {
            return null;
        }

        return self::dariModel($pengajuan);
    }

    /**
     * Tambahkan pengajuan baru dari peminjam ke antrean.
     *
     * @param  Pengajuan  $item
     * @return Pengajuan
     */
    public static function tambah(string $jenis, array $item): array
    {

        $kode = (string) ($item['kode'] ?? '');

        $pengajuan = Pengajuan::query()->create([
            'pengajuan_id' => self::idBaru($jenis),
            'jenis_pengajuan' => $jenis,
            'user_id' => self::angkaOrNull($item['user_id'] ?? null),
            'barang_id' => Barang::query()->where('kode_barang', $kode)->value('id'),
            'peminjaman_id' => self::angkaOrNull($item['peminjaman_id'] ?? null),
            'peminjam' => (string) ($item['peminjam'] ?? ''),
            'nim' => self::teksOrNull($item['nim'] ?? null),
            'kode' => $kode,
            'nama' => (string) ($item['nama'] ?? ''),
            'kategori' => self::teksOrNull($item['kategori'] ?? null),
            'jumlah' => isset($item['jumlah']) ? max(1, (int) $item['jumlah']) : 1,
            'tanggal_pengajuan' => (string) ($item['tanggal'] ?? now()->toDateString()),
            'jam' => self::teksOrNull($item['jam'] ?? null) ?? now()->format('H:i'),
            'jam_selesai' => self::teksOrNull($item['jamSelesai'] ?? $item['jam_selesai'] ?? null),
            'keperluan' => self::teksOrNull($item['keperluan'] ?? null),
            'catatan' => self::teksOrNull($item['catatan'] ?? null),
            'status' => self::STATUS_PENDING,
        ]);

        return self::dariModel($pengajuan);
    }

    /**
     * Tolak pengajuan: tetap di tabel pengajuan dengan status ditolak, tidak
     * pernah masuk ke tabel transaksi.
     *
     * @return Pengajuan|null
     */
    public static function tolak(string $jenis, string $id, ?string $alasan = null): ?array
    {
        $pengajuan = self::pending($jenis, $id);

        if ($pengajuan === null) {
            return null;
        }

        $pengajuan->forceFill([
            'status' => self::STATUS_DITOLAK,
            'alasan' => self::teksOrNull($alasan),
            'ditolak_pada' => now(),
        ])->save();

        return self::dariModel($pengajuan);
    }

    /**
     * Setujui pengajuan: pengajuan dipindahkan ke status disetujui sehingga
     * tidak lagi muncul di antrean, sedangkan baris transaksinya dibuat oleh
     * pemanggil.
     *
     * @return Pengajuan|null
     */
    public static function setujui(string $jenis, string $id, ?int $idPengelola = null): ?array
    {
        $pengajuan = self::pending($jenis, $id);

        if ($pengajuan === null) {
            return null;
        }

        $pengajuan->forceFill([
            'status' => self::STATUS_DISETUJUI,
            'alasan' => null,
            'id_pengelola' => $idPengelola,
            'tanggal_persetujuan' => now(),
        ])->save();

        return self::dariModel($pengajuan);
    }

    /**
     * Buat id pengajuan baru yang belum dipakai (PMJ-001, PMJ-002, ...).
     */
    public static function idBaru(string $jenis): string
    {
        $prefix = $jenis === self::JENIS_PINJAM ? 'PMJ' : 'PGB';

        $terpakai = Pengajuan::query()
            ->where('jenis_pengajuan', $jenis)
            ->pluck('pengajuan_id')
            ->merge($jenis === self::JENIS_PINJAM
                ? Peminjaman::query()->pluck('pengajuan_id')
                : Pengembalian::query()->pluck('pengajuan_id'))
            ->all();

        $urut = 0;

        foreach ($terpakai as $id) {
            if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/', (string) $id, $cocok) === 1) {
                $urut = max($urut, (int) $cocok[1]);
            }
        }

        do {
            $urut++;
            $id = sprintf('%s-%03d', $prefix, $urut);
        } while (in_array($id, $terpakai, true));

        return $id;
    }

    /**
     * Pengajuan yang sudah disetujui, dipetakan ke bentuk antrean.
     *
     * @return array<int, Pengajuan>
     */
    public static function disetujui(string $jenis): array
    {
        $rows = $jenis === self::JENIS_PINJAM
            ? Peminjaman::query()->orderBy('id')->get()
            : Pengembalian::query()->orderBy('id')->get();

        return $rows->map(fn ($row) => $jenis === self::JENIS_PINJAM
            ? self::dariPeminjaman($row)
            : self::dariPengembalian($row))->all();
    }

    /**
     * Pengajuan yang masih menunggu putusan admin.
     */
    private static function pending(string $jenis, string $id): ?Pengajuan
    {

        $pengajuan = Pengajuan::query()
            ->where('jenis_pengajuan', $jenis)
            ->where('pengajuan_id', $id)
            ->where('status', self::STATUS_PENDING)
            ->first();

        return $pengajuan;
    }

    /**
     * @return Pengajuan
     */
    private static function dariPeminjaman(Peminjaman $row): array
    {
        return self::normalisasi([
            'id' => $row->pengajuan_id,
            'user_id' => $row->user_id,
            'peminjam' => $row->peminjam,
            'nim' => $row->nim,
            'kode' => $row->kode_barang,
            'nama' => $row->nama_barang,
            'kategori' => $row->kategori,
            'tanggal' => $row->tanggal_pinjam->format('Y-m-d'),
            'tanggal_selesai' => $row->batas_kembali?->format('Y-m-d'),
            'jam' => $row->jam_pinjam->format('H:i'),
            'jamSelesai' => $row->jam_selesai?->format('H:i'),
            'keperluan' => $row->keperluan,
            'status' => self::STATUS_DISETUJUI,
            'disetujui_pada' => $row->created_at?->format('Y-m-d H:i'),
        ]);
    }

    /**
     * @return Pengajuan
     */
    private static function dariPengembalian(Pengembalian $row): array
    {
        return self::normalisasi([
            'id' => $row->pengajuan_id,
            'user_id' => $row->user_id,
            'peminjam' => $row->peminjam,
            'nim' => $row->nim,
            'kode' => $row->kode_barang,
            'nama' => $row->nama_barang,
            'kategori' => $row->kategori,
            'tanggal' => $row->tanggal_kembali->format('Y-m-d'),
            'jam' => $row->jam_kembali->format('H:i'),
            'catatan' => $row->catatan,
            'peminjaman_id' => $row->peminjaman_id,
            'status' => self::STATUS_DISETUJUI,
            'disetujui_pada' => $row->created_at?->format('Y-m-d H:i'),
        ]);
    }

    /**
     * @return Pengajuan
     */
    private static function dariModel(Pengajuan $pengajuan): array
    {
        return self::normalisasi([
            'id' => $pengajuan->pengajuan_id,
            'user_id' => $pengajuan->user_id,
            'peminjam' => $pengajuan->peminjam,
            'nim' => $pengajuan->nim,
            'kode' => $pengajuan->kode,
            'nama' => $pengajuan->nama,
            'kategori' => $pengajuan->kategori,
            'tanggal' => $pengajuan->tanggal_pengajuan?->format('Y-m-d'),
            'jam' => $pengajuan->jam?->format('H:i'),
            'jamSelesai' => $pengajuan->jam_selesai?->format('H:i'),
            'keperluan' => $pengajuan->keperluan,
            'catatan' => $pengajuan->catatan,
            'peminjaman_id' => $pengajuan->peminjaman_id,
            'status' => $pengajuan->status,
            'alasan' => $pengajuan->alasan,
            'ditolak_pada' => $pengajuan->ditolak_pada?->format('Y-m-d H:i'),
            'disetujui_pada' => $pengajuan->tanggal_persetujuan?->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Samakan bentuk setiap pengajuan agar aman disimpan dan dikirim ke frontend.
     *
     * @param  array<string, mixed>  $item
     * @return Pengajuan
     */
    private static function normalisasi(array $item): array
    {
        return [
            'id' => (string) ($item['id'] ?? ''),
            'user_id' => isset($item['user_id']) ? (int) $item['user_id'] : null,
            'peminjam' => (string) ($item['peminjam'] ?? ''),
            'nim' => isset($item['nim']) && $item['nim'] !== '' ? (string) $item['nim'] : null,
            'kode' => (string) ($item['kode'] ?? ''),
            'nama' => (string) ($item['nama'] ?? ''),
            'kategori' => isset($item['kategori']) && $item['kategori'] !== '' ? (string) $item['kategori'] : null,
            'tanggal' => (string) ($item['tanggal'] ?? ''),
            'tanggal_selesai' => isset($item['tanggal_selesai']) && $item['tanggal_selesai'] !== '' ? (string) $item['tanggal_selesai'] : null,
            'jam' => (string) ($item['jam'] ?? ''),
            'jamSelesai' => isset($item['jamSelesai']) && $item['jamSelesai'] !== '' ? (string) $item['jamSelesai'] : null,
            'keperluan' => isset($item['keperluan']) && $item['keperluan'] !== '' ? (string) $item['keperluan'] : null,
            'catatan' => isset($item['catatan']) && $item['catatan'] !== '' ? (string) $item['catatan'] : null,
            'peminjaman_id' => isset($item['peminjaman_id']) ? (int) $item['peminjaman_id'] : null,
            'status' => (string) ($item['status'] ?? self::STATUS_PENDING),
            'alasan' => isset($item['alasan']) && $item['alasan'] !== '' ? (string) $item['alasan'] : null,
            'ditolak_pada' => isset($item['ditolak_pada']) && $item['ditolak_pada'] !== '' ? (string) $item['ditolak_pada'] : null,
            'disetujui_pada' => isset($item['disetujui_pada']) && $item['disetujui_pada'] !== '' ? (string) $item['disetujui_pada'] : null,
        ];
    }

    /**
     * Urutkan: menunggu dulu, lalu disetujui, lalu ditolak.
     *
     * @param  array<int, Pengajuan>  $items
     * @return array<int, Pengajuan>
     */
    private static function urut(array $items): array
    {
        $peringkat = [
            self::STATUS_PENDING => 0,
            self::STATUS_DISETUJUI => 1,
            self::STATUS_DITOLAK => 2,
        ];

        usort($items, fn (array $a, array $b) => ($peringkat[$a['status']] ?? 3) <=> ($peringkat[$b['status']] ?? 3));

        return $items;
    }

    private static function teksOrNull(mixed $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (string) $nilai;
    }

    private static function angkaOrNull(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) $nilai;
    }
}
