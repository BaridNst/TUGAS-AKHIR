<?php

namespace App\Http\Controllers;

use App\Support\BarangStatus;
use Inertia\Inertia;
use Inertia\Response;

class BarangController extends Controller
{
    /**
     * Tampilkan daftar lengkap barang beserta status ketersediaannya.
     */
    public function index(): Response
    {
        return Inertia::render('User/DataBarang', [
            'barang' => $this->sortedBarang(),
        ]);
    }

    /**
     * Susun data barang: yang ready di atas, yang dipinjam di bawahnya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function sortedBarang(): array
    {
        return collect(BarangStatus::daftar())
            ->sortBy(fn (array $item) => $item['status'] === BarangStatus::READY ? 0 : 1)
            ->values()
            ->all();
    }
}
