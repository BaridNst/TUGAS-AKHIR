<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NfcLoanController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PengembalianController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Endpoint POST Jembatan ke Python ML
Route::post('/nfc-loan/predict', [NfcLoanController::class, 'store'])->name('nfc.loan.predict');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::get('/data-barang', [BarangController::class, 'index'])->middleware('auth')->name('barang.index');
Route::get('/data-barang&peminjaman', fn () => redirect()->route('admin.dataBarangPeminjaman'))->middleware('auth');
Route::get('/data-barang&peminjaman/data-barang', fn () => redirect()->route('admin.dataBarangPeminjaman.barang'))->middleware('auth');
Route::get('/data-barang&peminjaman/data-peminjaman', fn () => redirect()->route('admin.dataBarangPeminjaman.peminjaman'))->middleware('auth');

Route::get('/peminjaman', [PeminjamanController::class, 'index'])->middleware('auth')->name('peminjaman.index');
Route::post('/peminjaman', [PeminjamanController::class, 'store'])->middleware('auth')->name('peminjaman.store');

Route::get('/pengembalian', [PengembalianController::class, 'index'])->middleware('auth')->name('pengembalian.index');
Route::post('/pengembalian', [PengembalianController::class, 'store'])->middleware('auth')->name('pengembalian.store');

Route::get('/riwayat', [RiwayatController::class, 'index'])->middleware('auth')->name('riwayat.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
