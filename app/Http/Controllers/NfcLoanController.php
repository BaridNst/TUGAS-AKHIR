<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class NfcLoanController extends Controller
{
    /**
     * Menerima UID NFC dari Frontend (Vue 3 / Inertia),
     * mengeksekusi skrip Python ML secara lokal,
     * dan mengembalikan hasil prediksi dalam bentuk respons JSON.
     */
    public function store(Request $request)
    {
        // 1. Validasi input UID dari request
        $validated = $request->validate([
            'nfc_uid' => 'required|string',
        ]);

        $nfcUid = $validated['nfc_uid'];

        // Ambil data histori peminjam dari database jika tersedia
        $user = $request->user();
        $userId = $request->input('user_id', $user?->id);

        $prevLoans = 0;
        $prevLate = 0;
        $peran = $user?->peran ?? 'Mahasiswa';
        $kategori = $request->input('kategori', 'Elektronik');

        if ($userId) {
            $prevLoans = Peminjaman::where('user_id', $userId)->count();
            $prevLate = Pengembalian::where('user_id', $userId)->where('status', 'Terlambat')->count();
        }

        $payload = json_encode([
            'nfc_uid' => $nfcUid,
            'prev_loans' => $prevLoans,
            'prev_late' => $prevLate,
            'duration_days' => (int) $request->input('duration_days', 7),
            'peran' => $peran,
            'kategori' => $kategori,
        ]);

        // 2. Tentukan path Python executable (Virtual Environment lokal vs Laragon vs Sistem)
        $pythonBinary = base_path('venv/Scripts/python.exe');

        if (! file_exists($pythonBinary)) {
            $pythonBinary = 'C:\\laragon\\bin\\python\\python-3.10\\python.exe';
        }

        if (! file_exists($pythonBinary)) {
            $pythonBinary = 'python';
        }

        // Path ke skrip Python ML
        $scriptPath = base_path('ml_scripts/predict_rf.py');

        // 3. Eksekusi skrip Python menggunakan Illuminate\Support\Facades\Process
        $result = Process::run([
            $pythonBinary,
            $scriptPath,
            $payload,
        ]);

        // 4. Penanganan jika proses Python gagal/error
        if ($result->failed()) {
            Log::error('Python ML Execution Failed: '.$result->errorOutput());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengeksekusi skrip Python ML.',
                'error' => $result->errorOutput(),
            ], 500);
        }

        // 5. Parse output JSON dari stdout skrip Python
        $output = json_decode($result->output(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Format output dari skrip Python tidak valid.',
                'raw_output' => $result->output(),
            ], 500);
        }

        // 6. Kembalikan respon sukses dengan data prediksi
        return response()->json([
            'success' => true,
            'nfc_uid' => $nfcUid,
            'data' => $output,
        ]);
    }
}
