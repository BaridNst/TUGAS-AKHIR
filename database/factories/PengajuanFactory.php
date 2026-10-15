<?php

namespace Database\Factories;

use App\Models\Pengajuan;
use App\Support\PengajuanStore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengajuan>
 */
class PengajuanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengajuan_id' => 'PMJ-'.fake()->unique()->numberBetween(1, 999999),
            'jenis_pengajuan' => PengajuanStore::JENIS_PINJAM,
            'user_id' => null,
            'barang_id' => null,
            'peminjam' => fake()->name(),
            'nim' => fake()->numerify('2#########'),
            'kode' => 'BRG-'.fake()->numberBetween(1, 999),
            'nama' => fake()->words(3, true),
            'kategori' => fake()->randomElement(['Elektronik', 'Kabel', 'Sensor']),
            'jumlah' => 1,
            'tanggal_pengajuan' => now()->toDateString(),
            'jam' => now()->format('H:i'),
            'jam_selesai' => null,
            'keperluan' => fake()->sentence(4),
            'catatan' => null,
            'status' => PengajuanStore::STATUS_PENDING,
            'alasan' => null,
            'id_pengelola' => null,
            'tanggal_persetujuan' => null,
            'ditolak_pada' => null,
        ];
    }
}
