<?php

namespace App\Http\Controllers;

use App\Support\RiwayatTransaksi;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatController extends Controller
{
    /**
     * Tampilkan riwayat transaksi milik akun yang sedang login.
     */
    public function index(): Response
    {
        return Inertia::render('User/Riwayat', [
            'riwayat' => RiwayatTransaksi::milikPengguna((int) auth()->id()),
        ]);
    }
}
