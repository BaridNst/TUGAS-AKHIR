<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->string('pengajuan_id', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('peminjam');
            $table->string('nim', 30)->nullable();
            $table->string('kode_barang', 30)->index();
            $table->string('nama_barang');
            $table->string('kategori', 80)->nullable();
            $table->date('tanggal_pinjam');
            $table->time('jam_pinjam');
            $table->time('jam_selesai')->nullable();
            $table->string('keperluan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
