<?php

namespace App\Support;

use App\Models\Peminjaman;
use App\Models\Pengajuan;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Peringatan dini keterlambatan pengembalian untuk halaman Prediksi.
 *
 * Setiap transaksi yang sedang berjalan diklasifikasikan menjadi
 * `Tepat Waktu` atau `Terlambat` beserta probabilitasnya dari tiga fitur:
 *
 * 1. riwayat ketepatan peminjam (rasio pengembalian terlambat),
 * 2. sisa tenggat (`batas_kembali` terhadap hari ini), dan
 * 3. pengalaman peminjam (belum pernah menyelesaikan peminjaman).
 *
 * Hasil klasifikasi dipakai sebagai sistem peringatan dini dan rekomendasi
 * aksi pengelola (pengingat / notifikasi WhatsApp Gateway).
 */
class PrediksiKeterlambatan
{
    private const BOBOT_RIWAYAT = 0.35;

    private const BOBOT_TENGGAT = 0.55;

    private const BOBOT_PENGALAMAN = 0.10;

    /**
     * Probabilitas minimum & maksimum agar hasil tetap masuk akal.
     */
    private const PROB_MIN = 0.05;

    private const PROB_MAKS = 0.97;

    /**
     * Ambang probabilitas risiko terlambat untuk rekomendasi aksi.
     */
    private const AMBANG_KIRIM = 0.70;

    /**
     * Seluruh transaksi yang sedang berjalan, diurutkan dari risiko tertinggi.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function daftarAktif(): array
    {
        $transaksi = Peminjaman::sedangDipinjam();
        $kontak = self::kontakPeminjam($transaksi);
        $riwayatKembali = self::riwayatPengembalian();
        $riwayatPinjam = self::riwayatPeminjaman();

        return $transaksi
            ->map(fn (Peminjaman $pinjam): array => self::prediksi($pinjam, $kontak, $riwayatKembali, $riwayatPinjam))
            ->sortByDesc(fn (array $baris): float => $baris['probabilitas'])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, User|null>  $kontak
     * @param  array<string, array{total: int, telat: int}>  $riwayatKembali
     * @param  array<string, int>  $riwayatPinjam
     * @return array<string, mixed>
     */
    private static function prediksi(Peminjaman $pinjam, array $kontak, array $riwayatKembali, array $riwayatPinjam): array
    {
        $kunci = self::kunciPeminjam($pinjam);
        $riwayat = $riwayatKembali[$kunci] ?? ['total' => 0, 'telat' => 0];

        $faktorRiwayat = $riwayat['total'] > 0
            ? $riwayat['telat'] / $riwayat['total']
            : 0.25; // belum ada riwayat → risiko sedang (tidak diketahui)

        $sisaHari = self::sisaHari($pinjam);

        $faktorTenggat = match (true) {
            $sisaHari < 0 => 1.0,
            $sisaHari <= 2 => 0.6,
            $sisaHari <= 4 => 0.3,
            default => 0.1,
        };

        $faktorPengalaman = ($riwayatPinjam[$kunci] ?? 0) === 0 ? 1.0 : 0.0;

        $probabilitas = self::BOBOT_RIWAYAT * $faktorRiwayat
            + self::BOBOT_TENGGAT * $faktorTenggat
            + self::BOBOT_PENGALAMAN * $faktorPengalaman;

        $probabilitas = round(min(self::PROB_MAKS, max(self::PROB_MIN, $probabilitas)), 2);

        $terlambat = $probabilitas >= 0.5;

        return [
            'id' => $pinjam->pengajuan_id,
            'tanggalPinjam' => $pinjam->tanggal_pinjam->format('Y-m-d'),
            'batasKembali' => $pinjam->batas_kembali?->format('Y-m-d'),
            'sisaHari' => $sisaHari,
            'peminjam' => $pinjam->peminjam,
            'nim' => $pinjam->nim,
            'kontak' => $kontak[$pinjam->id] ?? null,
            'kode' => $pinjam->kode_barang,
            'nama' => $pinjam->nama_barang,
            'kategori' => $pinjam->kategori,
            'jumlah' => self::jumlahDipinjam($pinjam),
            'label' => $terlambat ? 'Terlambat' : 'Tepat Waktu',
            'probabilitas' => $probabilitas,
            'persentase' => (int) round(($terlambat ? $probabilitas : 1 - $probabilitas) * 100),
            'aksi' => match (true) {
                ! $terlambat => '-',
                $probabilitas >= self::AMBANG_KIRIM => 'Kirim Notifikasi',
                default => 'Perlu Pengingat',
            },
        ];
    }

    /**
     * @param  Collection<int, Peminjaman>  $transaksi
     * @return array<int, string|null>
     */
    private static function kontakPeminjam($transaksi): array
    {
        $pengguna = User::query()
            ->whereIn('id', $transaksi->pluck('user_id')->filter()->values())
            ->get()
            ->keyBy('id');

        $hasil = [];

        foreach ($transaksi as $pinjam) {
            $hasil[$pinjam->id] = $pinjam->user_id !== null
                ? $pengguna->get($pinjam->user_id)?->no_hp
                : null;
        }

        return $hasil;
    }

    /**
     * Rasio keterlambatan per peminjam dari seluruh pengembalian.
     *
     * @return array<string, array{total: int, telat: int}>
     */
    private static function riwayatPengembalian(): array
    {
        return Pengembalian::query()
            ->get()
            ->groupBy(fn (Pengembalian $kembali): string => self::kunciDari($kembali->user_id, $kembali->peminjam))
            ->map(fn ($kelompok): array => [
                'total' => $kelompok->count(),
                'telat' => $kelompok
                    ->filter(fn (Pengembalian $kembali): bool => $kembali->status === Persetujuan::STATUS_TERLAMBAT)
                    ->count(),
            ])
            ->all();
    }

    /**
     * Jumlah peminjaman yang sudah selesai per peminjam (pengalaman).
     *
     * @return array<string, int>
     */
    private static function riwayatPeminjaman(): array
    {
        return Peminjaman::query()
            ->where('status', Peminjaman::STATUS_SELESAI)
            ->get()
            ->countBy(fn (Peminjaman $pinjam): string => self::kunciDari($pinjam->user_id, $pinjam->peminjam))
            ->all();
    }

    private static function sisaHari(Peminjaman $pinjam): int
    {
        if ($pinjam->batas_kembali === null) {
            return 0;
        }

        $detik = $pinjam->batas_kembali->copy()->startOfDay()->getTimestamp()
            - now()->startOfDay()->getTimestamp();

        return (int) round($detik / 86400);
    }

    private static function jumlahDipinjam(Peminjaman $pinjam): int
    {
        $jumlah = Pengajuan::query()
            ->where('pengajuan_id', $pinjam->pengajuan_id)
            ->value('jumlah');

        return $jumlah !== null ? (int) $jumlah : 1;
    }

    private static function kunciPeminjam(Peminjaman $pinjam): string
    {
        return self::kunciDari($pinjam->user_id, $pinjam->peminjam);
    }

    private static function kunciDari(?int $userId, string $nama): string
    {
        return $userId !== null ? 'u'.$userId : 'n'.$nama;
    }
}
