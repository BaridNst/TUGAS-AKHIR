<?php

namespace App\Support;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengajuan;
use App\Models\Pengembalian;
use Illuminate\Support\Carbon;

/**
 * Ringkasan dashboard admin: seluruh angka, grafik, dan daftar dihitung dari
 * tabel di database, sehingga halaman tetap kosong sampai ada data nyata.
 */
class StatistikDashboard
{
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @return array<string, mixed>
     */
    public static function ringkas(): array
    {
        $barang = BarangStatus::daftar();
        $totalBarang = count($barang);
        $tersedia = count(array_filter($barang, fn (array $item): bool => $item['status'] === BarangStatus::READY));

        return [
            'stats' => [
                'totalBarang' => $totalBarang,
                'tersedia' => $tersedia,
                'sedangDipinjam' => $totalBarang - $tersedia,
                'pengajuanPeminjaman' => self::jumlahPengajuan(PengajuanStore::JENIS_PINJAM),
                'pengajuanPengembalian' => self::jumlahPengajuan(PengajuanStore::JENIS_KEMBALI),
                'terlambat' => self::jumlahTerlambat(),
            ],
            'grafikTransaksi' => self::grafikTransaksi(),
            'kategoriBarang' => self::kategoriBarang(),
            'barangTerpopuler' => self::barangTerpopuler(),
            'pengajuanTerbaru' => self::pengajuanTerbaru(),
            'notifikasi' => self::notifikasi(),
        ];
    }

    private static function jumlahPengajuan(string $jenis): int
    {
        return Pengajuan::query()
            ->where('jenis_pengajuan', $jenis)
            ->where('status', PengajuanStore::STATUS_PENDING)
            ->count();
    }

    /**
     * Pinjaman aktif yang sudah melewati batas pengembalian.
     */
    private static function jumlahTerlambat(): int
    {
        $hariIni = now()->startOfDay();

        return Peminjaman::sedangDipinjam()
            ->filter(fn (Peminjaman $pinjam): bool => $pinjam->batas_kembali !== null && $pinjam->batas_kembali->lt($hariIni))
            ->count();
    }

    /**
     * Jumlah peminjaman & pengembalian per bulan berdasarkan timeframe:
     * - 1 Tahun: 12 bulan (Januari s/d Desember) tahun berjalan.
     * - 6 Bulan Terakhir: 6 bulan dari bulan sekarang ke belakang.
     * - 3 Bulan Terakhir: 3 bulan dari bulan sekarang ke belakang.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function grafikTransaksi(): array
    {
        $startOfYear = now()->startOfYear();
        $sub11 = now()->startOfMonth()->subMonths(11);
        $awal = $startOfYear->lt($sub11) ? $startOfYear->toDateString() : $sub11->toDateString();
        $akhir = now()->endOfYear()->toDateString();

        $peminjaman = Peminjaman::query()
            ->whereBetween('tanggal_pinjam', [$awal, $akhir])
            ->get()
            ->countBy(fn (Peminjaman $pinjam): string => $pinjam->tanggal_pinjam->format('Y-m'));

        $pengembalian = Pengembalian::query()
            ->whereBetween('tanggal_kembali', [$awal, $akhir])
            ->get()
            ->countBy(fn (Pengembalian $kembali): string => $kembali->tanggal_kembali->format('Y-m'));

        $tahunIni = (int) now()->format('Y');

        // 1 Tahun: bulan 1 (Jan) sampai bulan 12 (Des) tahun berjalan
        $satuTahun = collect(range(1, 12))->map(function (int $bulanAngka) use ($tahunIni, $peminjaman, $pengembalian): array {
            $kunci = sprintf('%04d-%02d', $tahunIni, $bulanAngka);

            return [
                'bulan' => self::BULAN[$bulanAngka - 1],
                'tahun' => $tahunIni,
                'peminjaman' => $peminjaman->get($kunci, 0),
                'pengembalian' => $pengembalian->get($kunci, 0),
            ];
        })->all();

        // 6 Bulan Terakhir: 6 bulan dari bulan sekarang ke belakang
        $enamBulan = collect(range(0, 5))->map(function (int $urut) use ($peminjaman, $pengembalian): array {
            $bulan = now()->startOfMonth()->subMonths(5 - $urut);
            $kunci = $bulan->format('Y-m');

            return [
                'bulan' => self::BULAN[(int) $bulan->format('n') - 1],
                'tahun' => (int) $bulan->format('Y'),
                'peminjaman' => $peminjaman->get($kunci, 0),
                'pengembalian' => $pengembalian->get($kunci, 0),
            ];
        })->all();

        // 3 Bulan Terakhir: 3 bulan dari bulan sekarang ke belakang
        $tigaBulan = collect(range(0, 2))->map(function (int $urut) use ($peminjaman, $pengembalian): array {
            $bulan = now()->startOfMonth()->subMonths(2 - $urut);
            $kunci = $bulan->format('Y-m');

            return [
                'bulan' => self::BULAN[(int) $bulan->format('n') - 1],
                'tahun' => (int) $bulan->format('Y'),
                'peminjaman' => $peminjaman->get($kunci, 0),
                'pengembalian' => $pengembalian->get($kunci, 0),
            ];
        })->all();

        return [
            '1 Tahun' => $satuTahun,
            '6 Bulan Terakhir' => $enamBulan,
            '3 Bulan Terakhir' => $tigaBulan,
        ];
    }

    /**
     * Persentase barang per kategori (maksimal 5 kelompok terbesar).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function kategoriBarang(): array
    {
        $total = Barang::query()->count();

        if ($total === 0) {
            return [];
        }

        return Barang::query()
            ->pluck('kategori')
            ->map(fn ($kategori): string => filled($kategori) ? (string) $kategori : 'Tanpa Kategori')
            ->countBy()
            ->sortDesc()
            ->take(5)
            ->map(fn (int $jumlah, string $nama): array => [
                'nama' => $nama,
                'persentase' => (int) round($jumlah / $total * 100),
            ])
            ->values()
            ->all();
    }

    /**
     * Barang dengan peminjaman terbanyak (maksimal 5).
     *
     * @return array<int, array<string, mixed>>
     */
    private static function barangTerpopuler(): array
    {
        return Peminjaman::query()
            ->selectRaw('nama_barang as nama, count(*) as jumlah')
            ->groupBy('nama_barang')
            ->orderByDesc('jumlah')
            ->orderBy('nama_barang')
            ->limit(5)
            ->get()
            ->map(fn (object $baris): array => [
                'nama' => (string) $baris->nama,
                'jumlah' => (int) $baris->jumlah,
            ])
            ->all();
    }

    /**
     * Pengajuan terbaru (maksimal 4) lintas jenis.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function pengajuanTerbaru(): array
    {
        return Pengajuan::query()
            ->orderByDesc('id')
            ->limit(4)
            ->get()
            ->map(fn (Pengajuan $pengajuan): array => [
                'id' => $pengajuan->id,
                'jenis' => $pengajuan->jenis_pengajuan === PengajuanStore::JENIS_PINJAM ? 'Peminjaman' : 'Pengembalian',
                'barang' => $pengajuan->nama,
                'peminjam' => $pengajuan->peminjam,
                'waktu' => self::waktuRelatif($pengajuan->created_at),
                'status' => match ($pengajuan->status) {
                    PengajuanStore::STATUS_DISETUJUI => 'Disetujui',
                    PengajuanStore::STATUS_DITOLAK => 'Ditolak',
                    default => 'Pending',
                },
            ])
            ->all();
    }

    /**
     * Peringatan yang perlu ditindaklanjuti admin (terbaru dulu).
     *
     * @param  int  $batas  jumlah notifikasi yang dikembalikan
     * @return array<int, array<string, mixed>>
     */
    public static function notifikasi(int $batas = 4): array
    {
        $daftar = Pengajuan::query()
            ->where('status', PengajuanStore::STATUS_PENDING)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Pengajuan $pengajuan): array => [
                'waktu' => $pengajuan->created_at ?? $pengajuan->tanggal_pengajuan,
                'judul' => $pengajuan->jenis_pengajuan === PengajuanStore::JENIS_PINJAM
                    ? 'Pengajuan peminjaman baru'
                    : 'Pengajuan pengembalian menunggu persetujuan',
                'peminjam' => $pengajuan->peminjam,
                'ikon' => $pengajuan->jenis_pengajuan === PengajuanStore::JENIS_PINJAM ? 'document' : 'return',
            ]);

        $hariIni = now()->startOfDay();

        $pinjaman = Peminjaman::sedangDipinjam()
            ->filter(fn (Peminjaman $pinjam): bool => $pinjam->batas_kembali !== null)
            ->map(function (Peminjaman $pinjam) use ($hariIni): array {
                $terlambat = $pinjam->batas_kembali->lt($hariIni);

                return [
                    'waktu' => $pinjam->batas_kembali,
                    'judul' => $terlambat ? 'Barang terlambat dikembalikan' : 'Batas waktu peminjaman hampir habis',
                    'peminjam' => $pinjam->peminjam,
                    'ikon' => $terlambat ? 'alert' : 'clock',
                ];
            })
            ->filter(fn (array $notifikasi): bool => $notifikasi['waktu']->lte($hariIni->copy()->addDay()));

        return $daftar
            ->concat($pinjaman)
            ->sortByDesc(fn (array $notifikasi): string => $notifikasi['waktu']->toDateTimeString())
            ->take($batas)
            ->values()
            ->map(fn (array $notifikasi, int $urut): array => [
                'id' => $urut + 1,
                'judul' => $notifikasi['judul'],
                'peminjam' => $notifikasi['peminjam'],
                'waktu' => self::waktuRelatif($notifikasi['waktu']),
                'ikon' => $notifikasi['ikon'],
            ])
            ->all();
    }

    private static function waktuRelatif(?Carbon $waktu): string
    {
        if ($waktu === null) {
            return 'Baru saja';
        }

        $detik = now()->getTimestamp() - $waktu->getTimestamp();

        if ($detik < 0) {
            return 'Hari ini';
        }

        if ($detik < 60) {
            return 'Baru saja';
        }

        if ($detik < 3600) {
            return floor($detik / 60).' menit lalu';
        }

        if ($detik < 86400) {
            return floor($detik / 3600).' jam lalu';
        }

        return floor($detik / 86400).' hari lalu';
    }
}
