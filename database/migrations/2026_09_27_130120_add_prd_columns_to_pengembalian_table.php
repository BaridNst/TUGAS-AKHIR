<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom PRD pada tabel transaksi pengembalian: hasil ketepatan waktu
     * (Tepat Waktu | Terlambat) dan relasi langsung ke barang.
     */
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->foreignId('barang_id')->nullable()->after('kategori')->constrained('barangs')->nullOnDelete();
            $table->string('status', 20)->default('Selesai')->after('catatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->dropConstrainedForeignId('barang_id');
            $table->dropColumn('status');
        });
    }
};
