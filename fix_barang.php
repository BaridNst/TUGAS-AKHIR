<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Barang;

$barangs = Barang::all();
echo "All Barang: " . $barangs->count() . "\n";
foreach ($barangs as $b) {
    echo "  ID: {$b->id}, Kode: {$b->kode_barang}, Nama: {$b->nama_barang}, Status: {$b->status}\n";
}

// Make first barang available
$first = Barang::first();
if ($first) {
    $first->status = Barang::STATUS_TERSEDIA;
    $first->save();
    echo "\nMade {$first->kode_barang} available\n";
}