<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master barang sesuai PRD (entitas Barang).
     *
     * `status` memakai label PRD: Tersedia | Dipinjam. Nilai tersebut ikut
     * disinkronkan setiap ada persetujuan peminjaman/pengembalian, sedangkan
     * turunan yang dikirim ke frontend tetap dihitung ulang oleh BarangStatus.
     */
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 30)->unique();
            $table->string('nama_barang');
            $table->string('kategori', 80)->nullable();
            $table->string('kode_ntag', 40)->unique();
            $table->string('lokasi')->nullable();
            $table->string('kondisi', 40)->default('Baik');
            $table->string('status', 20)->default('Tersedia');
            $table->string('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
