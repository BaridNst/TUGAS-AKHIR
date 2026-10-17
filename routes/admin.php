<?php

use App\Http\Controllers\Admin\AdminMenuController;
use App\Http\Controllers\Admin\BarangController;
use App\Http\Controllers\Admin\PeminjamanController;
use App\Http\Controllers\Admin\PeminjamController;
use App\Http\Controllers\Admin\PengembalianController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute panel admin
|
| Setiap menu mempunya URL sendiri-sendiri agar berdiri sendiri dan
| tidak saling bertabrakan dengan rute user di routes/web.php.
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('data-barang&peminjaman', [AdminMenuController::class, 'dataBarangPeminjaman'])->name('dataBarangPeminjaman');
    Route::get('data-barang&peminjaman/data-barang', [AdminMenuController::class, 'dataBarangTab'])->name('dataBarangPeminjaman.barang');
    Route::get('data-barang&peminjaman/data-peminjaman', [AdminMenuController::class, 'dataPeminjamanTab'])->name('dataBarangPeminjaman.peminjaman');
    Route::get('data-barang', fn () => redirect()->route('admin.dataBarangPeminjaman.barang'))->name('dataBarang');
    Route::get('data-peminjam', fn () => redirect()->route('admin.dataBarangPeminjaman.peminjaman'))->name('dataPeminjam');
    Route::post('barang', [BarangController::class, 'store'])->name('barang.store');
    Route::put('barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
    Route::delete('barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');
    Route::put('peminjam/{peminjam}', [PeminjamController::class, 'update'])->name('peminjam.update');
    Route::delete('peminjam/{peminjam}', [PeminjamController::class, 'destroy'])->name('peminjam.destroy');
    Route::get('peminjaman', [PeminjamanController::class, 'index'])->name('peminjaman');
    Route::post('peminjaman/{pengajuan}/approve', [PeminjamanController::class, 'approve'])->name('peminjaman.approve');
    Route::post('peminjaman/{pengajuan}/reject', [PeminjamanController::class, 'reject'])->name('peminjaman.reject');
    Route::get('pengembalian', [PengembalianController::class, 'index'])->name('pengembalian');
    Route::post('pengembalian/{pengajuan}/approve', [PengembalianController::class, 'approve'])->name('pengembalian.approve');
    Route::post('pengembalian/{pengajuan}/reject', [PengembalianController::class, 'reject'])->name('pengembalian.reject');
    Route::get('riwayat', [AdminMenuController::class, 'riwayat'])->name('riwayat');
    Route::get('prediksi', [AdminMenuController::class, 'prediksi'])->name('prediksi');
    Route::post('prediksi/kirim-whatsapp', [AdminMenuController::class, 'kirimWhatsApp'])->name('prediksi.kirimWhatsApp');
    Route::get('denda', [AdminMenuController::class, 'denda'])->name('denda');
    Route::get('notifikasi', [AdminMenuController::class, 'notifikasi'])->name('notifikasi');
    Route::get('pengguna', [AdminMenuController::class, 'pengguna'])->name('pengguna');
    Route::get('pengaturan', [AdminMenuController::class, 'pengaturan'])->name('pengaturan');
});
