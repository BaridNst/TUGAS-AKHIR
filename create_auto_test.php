<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Peminjaman;
use App\Models\User;
use App\Models\Barang;
use Carbon\Carbon;

// Create a new active loan ending in 5 minutes
$futureTime = Carbon::now()->addMinutes(5);

$user = User::where('role', 'user')->first();
$barang = Barang::where('status', Barang::STATUS_TERSEDIA)->first();

if (!$user || !$barang) {
    echo "No user or barang available\n";
    exit;
}

$pinjam = Peminjaman::create([
    'pengajuan_id' => 'PMJ-AUTO-' . time(),
    'user_id' => $user->id,
    'barang_id' => $barang->id,
    'peminjam' => $user->name,
    'nim' => $user->nim,
    'kode_barang' => $barang->kode_barang,
    'nama_barang' => $barang->nama_barang,
    'kategori' => $barang->kategori,
    'tanggal_pinjam' => Carbon::now()->toDateString(),
    'jam_pinjam' => Carbon::now()->format('H:i:s'),
    'jam_selesai' => $futureTime->format('H:i:s'),
    'batas_kembali' => Carbon::now()->toDateString(),
    'status' => Peminjaman::STATUS_DIPINJAM,
    'keperluan' => 'Test otomatis 5 menit',
]);

echo "Created loan: {$pinjam->pengajuan_id}\n";
echo "jam_selesai: {$pinjam->jam_selesai}\n";
echo "Ends in: " . Carbon::now()->diffInMinutes($futureTime) . " minutes\n";