<?php

namespace Database\Factories;

use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peminjaman>
 */
class PeminjamanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengajuan_id' => 'PMJ-'.fake()->unique()->numberBetween(100000, 999999),
            'user_id' => User::factory(),
            'peminjam' => fake()->name(),
            'nim' => fake()->numerify('2#########'),
            'kode_barang' => 'BRG-001',
            'nama_barang' => fake()->words(3, true),
            'kategori' => fake()->randomElement(['Elektronik', 'Kabel', 'Sensor', 'Aksesori']),
            'tanggal_pinjam' => now()->toDateString(),
            'jam_pinjam' => '08:00',
            'jam_selesai' => '16:00',
            'keperluan' => fake()->sentence(3),
        ];
    }
}
