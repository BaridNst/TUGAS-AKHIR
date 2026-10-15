<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use App\Models\User;
use App\Services\NotifikasiService;
use App\Services\WhatsAppGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class KirimPengingatOtomatis extends Command
{
    protected $signature = 'notifikasi:kirim-pengingat';

    protected $description = 'Kirim pengingat WhatsApp otomatis 10 menit sebelum batas pengembalian';

    public function handle(): int
    {
        $now = Carbon::now();
        $batasWaktu = $now->copy()->addMinutes(10);

        $peminjaman = Peminjaman::sedangDipinjam()
            ->filter(function (Peminjaman $pinjam) use ($now, $batasWaktu) {
                if (! $pinjam->batas_kembali || ! $pinjam->jam_selesai) {
                    return false;
                }

                $batasKembali = Carbon::parse($pinjam->batas_kembali->format('Y-m-d').' '.$pinjam->jam_selesai->format('H:i:s'));

                return $batasKembali->gte($now) && $batasKembali->lte($batasWaktu);
            });

        $terkirim = 0;
        $gagal = 0;

        foreach ($peminjaman as $pinjam) {
            $user = $pinjam->user_id ? User::find($pinjam->user_id) : null;
            $kontak = $user?->no_hp;

            if (! $kontak) {
                $this->warn("No HP tidak tersedia untuk {$pinjam->peminjam} (ID: {$pinjam->pengajuan_id})");
                $gagal++;

                continue;
            }

            $data = NotifikasiService::siapkanDataTemplate($pinjam);
            $pesan = WhatsAppGateway::render('pengingat_batas', $data);
            $kirim = WhatsAppGateway::send($kontak, $pesan);

            if ($kirim['success']) {
                $this->info("Terkirim ke {$pinjam->peminjam} ({$kontak})");
                $terkirim++;
            } else {
                $this->error("Gagal ke {$pinjam->peminjam}: {$kirim['message']}");
                $gagal++;
            }
        }

        $this->info("Selesai. Terkirim: {$terkirim}, Gagal: {$gagal}");

        return self::SUCCESS;
    }
}
