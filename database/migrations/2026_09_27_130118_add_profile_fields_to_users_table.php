<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil pengguna sesuai PRD (entitas Pengguna): identitas akademik,
     * nomor HP untuk kanal WhatsApp, dan status akun.
     *
     * `peran` berisi Mahasiswa | Dosen (identitas akademik pada tab Data
     * Peminjam), bukan peran akses aplikasi — peran akses tetap di `role`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nim', 30)->nullable()->unique()->after('name');
            $table->string('program_studi', 120)->nullable()->after('nim');
            $table->string('no_hp', 30)->nullable()->after('program_studi');
            $table->string('peran', 20)->default('Mahasiswa')->after('no_hp');
            $table->string('status_akun', 20)->default('Aktif')->after('peran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nim', 'program_studi', 'no_hp', 'peran', 'status_akun']);
        });
    }
};
