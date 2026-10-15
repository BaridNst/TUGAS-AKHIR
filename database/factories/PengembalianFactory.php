<?php

namespace Database\Factories;

use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pengembalian>
 */
class PengembalianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengajuan_id' => 'PGB-'.fake()->unique()->numberBetween(100000, 999999),
            'peminjaman_id' => null,
            'user_id' => User::factory(),
            'peminjam' => fake()->name(),
            'nim' => fake()->numerify('2#########'),
            'kode_barang' => 'BRG-001',
            'nama_barang' => fake()->words(3, true),
            'kategori' => fake()->randomElement(['Elektronik', 'Kabel', 'Sensor', 'Aksesori']),
            'tanggal_kembali' => now()->toDateString(),
            'jam_kembali' => '10:00',
            'catatan' => 'Kondisi barang baik.',
        ];
    }
}
