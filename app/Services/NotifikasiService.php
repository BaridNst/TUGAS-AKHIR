<?php

namespace App\Services;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;

class NotifikasiService
{
    public static function tentukanJenisNotifikasi(Peminjaman $pinjam): ?string
    {
        if ($pinjam->batas_kembali === null) {
            return null;
        }

        $sisaHari = self::hitungSisaHari($pinjam);

        if ($sisaHari < -2) {
            return 'terlambat_berat';
        }

        if ($sisaHari < 0) {
            return 'terlambat_ringan';
        }

        if ($sisaHari <= 2) {
            return 'pengingat_batas';
        }

        return null;
    }

    public static function tentukanJenisDariPengembalian(Pengembalian $kembali): ?string
    {
        return match ($kembali->status) {
            Pengembalian::STATUS_RUSAK => 'barang_rusak',
            Pengembalian::STATUS_HILANG => 'barang_hilang',
            default => null,
        };
    }

    private static function hitungSisaHari(Peminjaman $pinjam): int
    {
        if ($pinjam->batas_kembali === null) {
            return 0;
        }

        $detik = $pinjam->batas_kembali->copy()->startOfDay()->getTimestamp()
            - now()->startOfDay()->getTimestamp();

        return (int) round($detik / 86400);
    }

    public static function siapkanDataTemplate(Peminjaman $pinjam): array
    {
        $sisaHari = self::hitungSisaHari($pinjam);
        $batasKembali = $pinjam->batas_kembali?->format('d F Y') ?? '-';

        return [
            'nama' => $pinjam->peminjam,
            'nama_barang' => $pinjam->nama_barang,
            'kode_barang' => $pinjam->kode_barang,
            'sisa_hari' => $sisaHari < 0 ? abs($sisaHari) : $sisaHari,
            'batas_kembali' => $batasKembali,
            'tanggal_pinjam' => $pinjam->tanggal_pinjam->format('d F Y'),
        ];
    }

    public static function kirimNotifikasiAktif(): array
    {
        $transaksi = Peminjaman::sedangDipinjam()->get();
        $hasil = [];

        foreach ($transaksi as $pinjam) {
            $jenis = self::tentukanJenisNotifikasi($pinjam);

            if (! $jenis) {
                continue;
            }

            $kontak = $pinjam->user_id
                ? User::find($pinjam->user_id)?->no_hp
                : null;

            if (! $kontak) {
                $hasil[] = [
                    'pengajuan_id' => $pinjam->pengajuan_id,
                    'peminjam' => $pinjam->peminjam,
                    'jenis' => $jenis,
                    'status' => 'gagal',
                    'alasan' => 'Nomor telepon peminjam tidak tersedia',
                ];

                continue;
            }

            $data = self::siapkanDataTemplate($pinjam);
            $pesan = WhatsAppGateway::render($jenis, $data);
            $kirim = WhatsAppGateway::send($kontak, $pesan);

            $hasil[] = [
                'pengajuan_id' => $pinjam->pengajuan_id,
                'peminjam' => $pinjam->peminjam,
                'nomor' => $kontak,
                'jenis' => $jenis,
                'pesan' => $pesan,
                'status' => $kirim['success'] ? 'terkirim' : 'gagal',
                'alasan' => $kirim['message'] ?? null,
            ];
        }

        return $hasil;
    }

    public static function kirimNotifikasiPengembalian(Pengembalian $kembali): array
    {
        $jenis = self::tentukanJenisDariPengembalian($kembali);

        if (! $jenis) {
            return ['status' => 'skip', 'alasan' => 'Tidak memerlukan notifikasi'];
        }

        $kontak = $kembali->user_id
            ? User::find($kembali->user_id)?->no_hp
            : null;

        if (! $kontak) {
            return ['status' => 'gagal', 'alasan' => 'Nomor telepon peminjam tidak tersedia'];
        }

        $data = [
            'nama' => $kembali->peminjam,
            'nama_barang' => $kembali->nama_barang,
            'kode_barang' => $kembali->kode_barang,
            'batas_kembali' => $kembali->tanggal_kembali?->format('d F Y') ?? '-',
        ];

        $pesan = WhatsAppGateway::render($jenis, $data);
        $kirim = WhatsAppGateway::send($kontak, $pesan);

        return [
            'status' => $kirim['success'] ? 'terkirim' : 'gagal',
            'jenis' => $jenis,
            'pesan' => $pesan,
            'alasan' => $kirim['message'] ?? null,
        ];
    }
}
