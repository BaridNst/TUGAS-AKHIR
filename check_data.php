<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Barang;

$users = User::where('role', 'user')->get();
echo "Users (role=user): " . $users->count() . "\n";
foreach ($users as $u) {
    echo "  ID: {$u->id}, Name: {$u->name}, no_hp: {$u->no_hp}\n";
}

$barangs = Barang::where('status', Barang::STATUS_TERSEDIA)->get();
echo "Barang (tersedia): " . $barangs->count() . "\n";
foreach ($barangs as $b) {
    echo "  ID: {$b->id}, Kode: {$b->kode_barang}, Nama: {$b->nama_barang}\n";
}