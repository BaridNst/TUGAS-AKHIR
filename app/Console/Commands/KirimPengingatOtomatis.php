<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use App\Models\User;
use App\Services\MachineLearningService;
use App\Services\NotifikasiService;
use App\Services\WhatsAppGateway;
use Illuminate\Console\Command;

class KirimPengingatOtomatis extends Command
{
    protected $signature = 'notifikasi:kirim-pengingat 
                            {--force : Paksa pengiriman tanpa menunggu jeda atau penanda hari ini}
                            {--nomor= : Alihkan penerima ke nomor tertentu untuk pengujian}';

    protected $description = 'Evaluasi transaksi peminjaman aktif dengan Machine Learning (Random Forest) dan kirim notifikasi WhatsApp otomatis';

    public function handle(): int
    {
        $this->info('====================================================================');
        $this->info('  🤖 Evaluasi Peminjaman Aktif & Otomasi WhatsApp Gateway (AI RF)   ');
        $this->info('====================================================================');

        $transaksi = Peminjaman::sedangDipinjam();

        if ($transaksi->isEmpty()) {
            $this->comment('Tidak ada peminjaman yang sedang aktif saat ini.');

            return self::SUCCESS;
        }

        $terkirim = 0;
        $gagal = 0;
        $dilewati = 0;

        foreach ($transaksi as $pinjam) {
            $user = $pinjam->user_id
                ? User::find($pinjam->user_id)
                : User::where('name', $pinjam->peminjam)->orWhere('nim', $pinjam->nim)->first();
            $targetNomor = $this->option('nomor') ?: $user?->no_hp;

            // 1. Evaluasi Cerdas berbasis Model Random Forest
            $ml = MachineLearningService::prediksiPeminjaman($pinjam);
            $riskStatus = $ml['risk_status'] ?? 'Low Risk';
            $prediction = $ml['prediction'] ?? 'Tepat Waktu';
            $probLate = (float) ($ml['prob_late'] ?? 0);

            // 2. Tentukan Kebutuhan Notifikasi
            $jenis = NotifikasiService::tentukanJenisNotifikasi($pinjam, $ml);

            $this->line(sprintf(
                "\n🔍 [%s] %s | Barang: %s",
                $pinjam->pengajuan_id,
                $pinjam->peminjam,
                $pinjam->nama_barang
            ));
            $this->line(sprintf(
                '   🧠 Prediksi AI: %s (%s, Probabilitas Telat: %d%%)',
                $prediction,
                $riskStatus,
                (int) ($probLate * 100)
            ));

            if (! $jenis) {
                $this->line('   ✅ Status: Masih Aman & Sesuai Jadwal (Belum perlu notifikasi)');
                $dilewati++;

                continue;
            }

            $labelJenis = match ($jenis) {
                'terlambat_berat' => '🚨 Peringatan Terlambat Berat (>= 3 Hari)',
                'terlambat_ringan' => '⚠️ Peringatan Terlambat Ringan (1-2 Hari)',
                'pengingat_batas' => '⏰ Pengingat Batas Waktu Pengembalian (H-1 / Hari H)',
                'peringatan_dini_ai' => '🤖 Peringatan Dini Risiko Keterlambatan (AI High Risk)',
                default => $jenis,
            };

            $this->line("   🔔 Kategori Notifikasi: {$labelJenis}");

            // 3. Pengecekan Jeda Pengiriman agar Tidak Spam Tiap Menit
            $isForce = (bool) $this->option('force');
            if (! $isForce && $pinjam->pengingat_terkirim_at && $pinjam->pengingat_terkirim_at->isToday()) {
                $jamKirim = $pinjam->pengingat_terkirim_at->format('H:i');
                $this->comment("   ⏳ Dilewati: Notifikasi sudah dikirimkan hari ini pada {$jamKirim} WIB.");
                $this->comment('      (Gunakan opsi --force untuk memaksa kirim ulang)');
                $dilewati++;

                continue;
            }

            // 4. Validasi Nomor Tujuan
            if (! $targetNomor) {
                $this->error("   ❌ Gagal: Nomor WhatsApp untuk {$pinjam->peminjam} tidak ditemukan.");
                $gagal++;

                continue;
            }

            // 5. Render Template & Kirim via WhatsApp Gateway (Fonnte/Wablas)
            $dataTemplate = NotifikasiService::siapkanDataTemplate($pinjam);
            $pesan = WhatsAppGateway::render($jenis, $dataTemplate);

            $this->line("   📲 Mengirim WhatsApp ke: {$targetNomor} ...");

            $kirim = WhatsAppGateway::send($targetNomor, $pesan);

            if ($kirim['success']) {
                $pinjam->update(['pengingat_terkirim_at' => now()]);
                $this->info("   🎉 BERHASIL: Pesan terkirim ke {$pinjam->peminjam} ({$targetNomor})");
                $terkirim++;
            } else {
                $this->error("   ❌ GAGAL KIRIM: {$kirim['message']}");
                $gagal++;
            }
        }

        $this->newLine();
        $this->info('====================================================================');
        $this->info("  Selesai. Terkirim: {$terkirim}, Gagal: {$gagal}, Dilewati: {$dilewati}");
        $this->info('====================================================================');

        return $gagal > 0 && $terkirim === 0 ? self::FAILURE : self::SUCCESS;
    }
}
