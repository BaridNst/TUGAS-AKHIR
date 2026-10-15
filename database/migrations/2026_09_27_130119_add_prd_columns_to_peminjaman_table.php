<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom PRD pada tabel transaksi peminjaman: tenggat pengembalian
     * (`batas_kembali`), status pinjaman, dan relasi langsung ke barang.
     */
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->foreignId('barang_id')->nullable()->after('kategori')->constrained('barangs')->nullOnDelete();
            $table->date('batas_kembali')->nullable()->after('jam_selesai');
            $table->string('status', 20)->default('Dipinjam')->after('batas_kembali');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropConstrainedForeignId('barang_id');
            $table->dropColumn(['batas_kembali', 'status']);
        });
    }
};
