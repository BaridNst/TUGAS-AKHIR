<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

// Ambil data nyata dari DB
$dbData = [];
$peminjamans = Peminjaman::all();

foreach ($peminjamans as $p) {
    $user = User::find($p->user_id);
    $pengembalian = Pengembalian::where('peminjaman_id', $p->id)->first();

    // Hitung riwayat sebelumnya untuk user ini
    $prevLoans = Peminjaman::where('user_id', $p->user_id)
        ->where('created_at', '<', $p->created_at)
        ->count();

    $prevLate = Pengembalian::where('user_id', $p->user_id)
        ->where('status', 'Terlambat')
        ->where('created_at', '<', $p->created_at)
        ->count();

    $ratio = $prevLoans > 0 ? round($prevLate / $prevLoans, 2) : 0.0;

    $isLate = 0;
    if ($pengembalian) {
        $isLate = ($pengembalian->status === 'Terlambat') ? 1 : 0;
    } elseif ($p->status === 'Dipinjam' && now()->greaterThan($p->batas_kembali)) {
        $isLate = 1;
    }

    $dbData[] = [
        'user_id' => $p->user_id,
        'peran' => $user?->peran ?? 'Mahasiswa',
        'kategori' => $p->kategori ?? 'Elektronik',
        'prev_loans' => $prevLoans,
        'prev_late' => $prevLate,
        'late_ratio' => $ratio,
        'duration_days' => 7,
        'is_late' => $isLate,
    ];
}

// Tambahkan dataset sintetis historis realistis (150 sampel) untuk melatih model Random Forest
$categories = ['Elektronik', 'Jaringan', 'Komputer', 'Perkakas', 'Aksesori'];
$roles = ['Mahasiswa', 'Dosen'];

mt_srand(42); // Seed konsisten

for ($i = 0; $i < 150; $i++) {
    $prevLoans = rand(0, 15);
    $prevLate = $prevLoans > 0 ? rand(0, $prevLoans) : 0;
    $ratio = $prevLoans > 0 ? round($prevLate / $prevLoans, 2) : 0.0;
    $role = $roles[rand(0, 1)];
    $kategori = $categories[rand(0, count($categories) - 1)];
    $duration = rand(1, 14);

    // Probabilitas keterlambatan berdasarkan rasio riwayat terlambat
    $probLate = 0.15 + ($ratio * 0.65) + ($duration > 7 ? 0.1 : 0.0);
    if ($role === 'Dosen') {
        $probLate -= 0.1;
    }
    $probLate = max(0.05, min(0.95, $probLate));

    $isLate = (mt_rand() / mt_getrandmax()) < $probLate ? 1 : 0;

    $dbData[] = [
        'user_id' => 1000 + $i,
        'peran' => $role,
        'kategori' => $kategori,
        'prev_loans' => $prevLoans,
        'prev_late' => $prevLate,
        'late_ratio' => $ratio,
        'duration_days' => $duration,
        'is_late' => $isLate,
    ];
}

$outputFile = __DIR__.'/dataset.json';
file_put_contents($outputFile, json_encode($dbData, JSON_PRETTY_PRINT));
echo 'Berhasil mengekspor '.count($dbData).' sampel data historis ke '.$outputFile."\n";
