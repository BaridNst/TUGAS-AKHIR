<?php

namespace Database\Factories;

use App\Models\Barang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barang>
 */
class BarangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_barang' => 'BRG-'.fake()->unique()->numberBetween(1, 9999),
            'nama_barang' => fake()->words(3, true),
            'kategori' => fake()->randomElement(['Elektronik', 'Kabel', 'Sensor', 'Aksesori']),
            'kode_ntag' => '04A3'.fake()->unique()->numerify('##########'),
            'lokasi' => fake()->randomElement(['Ruang IT', 'Lab IoT', 'Ruang Multimedia']),
            'kondisi' => 'Baik',
            'status' => Barang::STATUS_TERSEDIA,
            'deskripsi' => null,
        ];
    }
}
