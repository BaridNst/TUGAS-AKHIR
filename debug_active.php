<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Peminjaman;
use Illuminate\Support\Carbon;

$now = Carbon::now();
$batasWaktu = $now->copy()->addMinutes(10);

echo "Now: $now\n";
echo "Batas waktu (10 menit): $batasWaktu\n\n";

$peminjaman = Peminjaman::sedangDipinjam();

echo "Total sedang dipinjam: " . $peminjaman->count() . "\n\n";

foreach ($peminjaman as $pinjam) {
    echo "Pinjam ID: {$pinjam->pengajuan_id}\n";
    echo "  Peminjam: {$pinjam->peminjam}\n";
    echo "  Status: {$pinjam->status}\n";
    echo "  batas_kembali: {$pinjam->batas_kembali}\n";
    echo "  jam_selesai: {$pinjam->jam_selesai}\n";
    
    if (!$pinjam->batas_kembali || !$pinjam->jam_selesai) {
        echo "  SKIP: missing batas_kembali or jam_selesai\n";
        continue;
    }

    $batasKembali = Carbon::parse($pinjam->batas_kembali->format('Y-m-d') . ' ' . $pinjam->jam_selesai->format('H:i:s'));
    echo "  batasKembali parsed: $batasKembali\n";
    echo "  diff minutes: " . $now->diffInMinutes($batasKembali, false) . "\n";
    echo "  gte now: " . ($batasKembali->gte($now) ? 'YES' : 'NO') . "\n";
    echo "  lte batasWaktu: " . ($batasKembali->lte($batasWaktu) ? 'YES' : 'NO') . "\n";
    
    if ($batasKembali->gte($now) && $batasKembali->lte($batasWaktu)) {
        echo "  *** MATCHES! ***\n";
    }
    echo "\n";
}