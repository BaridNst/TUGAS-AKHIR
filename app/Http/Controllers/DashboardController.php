<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BarangStatus;
use App\Support\PrediksiKeterlambatan;
use App\Support\RiwayatTransaksi;
use App\Support\StatistikDashboard;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman dashboard beserta tabel ketersediaan barang
     * dan pratinjau riwayat transaksi milik akun ini.
     */
    public function index(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user instanceof User && $user->google_id && $user->needsProfileCompletion()) {
            return redirect()->route('profile.complete');
        }

        if ($user instanceof User && $user->isAdmin()) {
            return Inertia::render('Admin/Dashboard', [
                ...StatistikDashboard::ringkas(),
                'prediksi' => PrediksiKeterlambatan::daftarAktif(),
            ]);
        }

        return Inertia::render('User/Dashboard', [
            'barang' => $this->sortedBarang(),
            'riwayat' => RiwayatTransaksi::milikPengguna((int) auth()->id()),
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
