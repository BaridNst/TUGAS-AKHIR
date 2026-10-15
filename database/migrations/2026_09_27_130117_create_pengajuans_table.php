<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Antrean pengajuan peminjaman & pengembalian (PRD: entitas Pengajuan).
     *
     * Hanya pengajuan yang disetujui yang dipindahkan ke tabel `peminjaman`
     * / `pengembalian` sebagai data transaksi; pengajuan ditolak tetap berada
     * di tabel ini dengan status ditolak.
     */
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();
            $table->string('pengajuan_id', 40)->unique();
            $table->string('jenis_pengajuan', 20)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barangs')->nullOnDelete();
            $table->foreignId('peminjaman_id')->nullable()->constrained('peminjaman')->nullOnDelete();
            $table->string('peminjam');
            $table->string('nim', 30)->nullable();
            $table->string('kode', 30)->index();
            $table->string('nama');
            $table->string('kategori', 80)->nullable();
            $table->unsignedTinyInteger('jumlah')->default(1);
            $table->date('tanggal_pengajuan');
            $table->time('jam')->nullable();
            $table->time('jam_selesai')->nullable();
            $table->string('keperluan')->nullable();
            $table->string('catatan')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('alasan')->nullable();
            $table->foreignId('id_pengelola')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_persetujuan')->nullable();
            $table->timestamp('ditolak_pada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};
