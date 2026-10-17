<?php

namespace App\Services;

use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class MachineLearningService
{
    /**
     * Memprediksi risiko keterlambatan pengembalian menggunakan model Random Forest (Scikit-Learn).
     *
     * @return array{
     *     risk_status: string,
     *     prediction: string,
     *     confidence_score: float,
     *     prob_late: float,
     *     message: string,
     *     history_summary: array{prev_loans: int, prev_late: int, late_ratio: float}
     * }
     */
    public static function prediksiPeminjaman(Peminjaman $pinjam): array
    {
        $user = $pinjam->user_id
            ? User::find($pinjam->user_id)
            : User::where('name', $pinjam->peminjam)->orWhere('nim', $pinjam->nim)->first();
        $prevLoans = 0;
        $prevLate = 0;

        $targetUserId = $user?->id ?? $pinjam->user_id;

        if ($targetUserId) {
            $prevLoans = Peminjaman::where('user_id', $targetUserId)
                ->where('created_at', '<', $pinjam->created_at ?? now())
                ->count();

            $prevLate = Pengembalian::where('user_id', $targetUserId)
                ->where('status', 'Terlambat')
                ->where('created_at', '<', $pinjam->created_at ?? now())
                ->count();
        } else {
            $prevLoans = Peminjaman::where('peminjam', $pinjam->peminjam)
                ->where('created_at', '<', $pinjam->created_at ?? now())
                ->count();

            $prevLate = Pengembalian::where('peminjam', $pinjam->peminjam)
                ->where('status', 'Terlambat')
                ->where('created_at', '<', $pinjam->created_at ?? now())
                ->count();
        }

        $durationDays = 7;
        if ($pinjam->batas_kembali && $pinjam->tanggal_pinjam) {
            $durationDays = max(1, (int) $pinjam->tanggal_pinjam->diffInDays($pinjam->batas_kembali));
        }

        return self::inferensi([
            'nfc_uid' => $pinjam->kode_barang,
            'prev_loans' => $prevLoans,
            'prev_late' => $prevLate,
            'duration_days' => $durationDays,
            'peran' => $user?->peran ?? 'Mahasiswa',
            'kategori' => $pinjam->kategori ?? 'Elektronik',
        ]);
    }

    /**
     * Jalankan inferensi skrip Python predict_rf.py dengan model model_rf.joblib.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public static function inferensi(array $params): array
    {
        $payload = json_encode($params);

        // Cari Python binary yang valid (venv -> Laragon -> Sistem)
        $pythonBinary = base_path('venv/Scripts/python.exe');

        if (! file_exists($pythonBinary)) {
            $pythonBinary = 'C:\\laragon\\bin\\python\\python-3.10\\python.exe';
        }

        if (! file_exists($pythonBinary)) {
            $pythonBinary = 'python';
        }

        $scriptPath = base_path('ml_scripts/predict_rf.py');

        try {
            $result = Process::run([
                $pythonBinary,
                $scriptPath,
                $payload,
            ]);

            if ($result->successful()) {
                $output = json_decode($result->output(), true);
                if (is_array($output) && isset($output['risk_status'])) {
                    return $output;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('MachineLearningService Error: '.$e->getMessage());
        }

        // Fallback cerdas jika python lingkungan lokal belum terpasang
        $prevLoans = (int) ($params['prev_loans'] ?? 0);
        $prevLate = (int) ($params['prev_late'] ?? 0);
        $ratio = $prevLoans > 0 ? ($prevLate / $prevLoans) : 0.0;

        $riskStatus = $ratio >= 0.5 ? 'High Risk' : ($ratio >= 0.25 ? 'Medium Risk' : 'Low Risk');
        $predictionLabel = $ratio >= 0.5 ? 'Terlambat' : ($ratio >= 0.25 ? 'Perlu Perhatian' : 'Tepat Waktu');
        $confidence = $ratio >= 0.5 ? 0.85 : ($ratio >= 0.25 ? 0.65 : 0.90);

        return [
            'nfc_uid' => $params['nfc_uid'] ?? 'UNKNOWN',
            'risk_status' => $riskStatus,
            'prediction' => $predictionLabel,
            'confidence_score' => $confidence,
            'prob_late' => round($ratio, 2),
            'message' => "Analisis risiko historis ({$prevLate} terlambat dari {$prevLoans} peminjaman).",
            'history_summary' => [
                'prev_loans' => $prevLoans,
                'prev_late' => $prevLate,
                'late_ratio' => round($ratio, 2),
            ],
        ];
    }
}
