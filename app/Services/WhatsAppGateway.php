<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGateway
{
    private const TEMPLATES = [
        'terlambat_ringan' => [
            'label' => 'Terlambat 1-2 Hari',
            'template' => 'Halo {nama}, ini pengingat dari Admin Peminjaman NFC. Barang "{nama_barang}" yang Anda pinjam terlambat dikembalikan {sisa_hari} hari. Mohon segera dikembalikan. Terima kasih.',
        ],
        'terlambat_berat' => [
            'label' => 'Terlambat 3+ Hari',
            'template' => 'Halo {nama}, PERINGATAN: Barang "{nama_barang}" terlambat dikembalikan {sisa_hari} hari. Denda akan berlaku sesuai ketentuan. Segera hubungi admin untuk penyelesaian.',
        ],
        'barang_rusak' => [
            'label' => 'Barang Rusak',
            'template' => 'Halo {nama}, barang "{nama_barang}" yang Anda kembalikan dinyatakan rusak. Mohon hubungi admin untuk proses penggantian/perbaikan sesuai ketentuan.',
        ],
        'barang_hilang' => [
            'label' => 'Barang Hilang',
            'template' => 'Halo {nama}, barang "{nama_barang}" yang Anda pinjam dilaporkan hilang. Mohon segera hubungi admin untuk penyelesaian penggantian barang.',
        ],
        'pengingat_batas' => [
            'label' => 'Pengingat Batas Waktu',
            'template' => 'Halo {nama}, pengingat: batas pengembalian barang "{nama_barang}" adalah {batas_kembali} ({sisa_hari} hari lagi). Mohon dikembalikan tepat waktu.',
        ],
        'peringatan_dini_ai' => [
            'label' => 'Peringatan Dini AI (Risiko Tinggi)',
            'template' => 'Halo {nama}, sistem prediksi AI (Random Forest) mendeteksi risiko keterlambatan pada pengembalian "{nama_barang}". Batas kembali: {batas_kembali}. Mohon pastikan barang dikembalikan tepat waktu.',
        ],
    ];

    public static function templates(): array
    {
        return self::TEMPLATES;
    }

    public static function render(string $type, array $data): string
    {
        $template = self::TEMPLATES[$type]['template'] ?? self::TEMPLATES['terlambat_ringan']['template'];

        return str_replace(
            array_map(fn ($k) => '{'.$k.'}', array_keys($data)),
            array_values($data),
            $template
        );
    }

    public static function send(string $nomorTujuan, string $pesan): array
    {
        $provider = config('whatsapp.provider', 'fonnte');
        $token = config('whatsapp.token');
        $device = config('whatsapp.device_id');

        if (! $token) {
            Log::warning('WhatsApp Gateway: Token tidak dikonfigurasi');

            return ['success' => false, 'message' => 'Token gateway tidak dikonfigurasi di file .env'];
        }

        try {
            $response = match ($provider) {
                'fonnte' => self::sendViaFonnte($nomorTujuan, $pesan, $token),
                'wablas' => self::sendViaWablas($nomorTujuan, $pesan, $token, $device),
                'custom' => self::sendViaCustom($nomorTujuan, $pesan),
                default => ['success' => false, 'message' => 'Provider tidak dikenali'],
            };

            Log::info('WhatsApp sent', ['to' => $nomorTujuan, 'provider' => $provider, 'result' => $response]);

            return $response;
        } catch (\Throwable $e) {
            Log::error('WhatsApp Gateway Error', ['error' => $e->getMessage(), 'to' => $nomorTujuan]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function sendViaFonnte(string $nomor, string $pesan, string $token): array
    {
        $response = Http::asForm()
            ->withHeaders(['Authorization' => $token])
            ->post('https://api.fonnte.com/send', [
                'target' => self::normalizeNomor($nomor),
                'message' => $pesan,
                'countryCode' => '62',
            ]);

        if (! $response->successful()) {
            return ['success' => false, 'message' => $response->json()['reason'] ?? 'Gagal menghubungi server Fonnte'];
        }

        $json = $response->json() ?? [];
        $status = $json['status'] ?? false;

        // Fonnte mengembalikan status: true jika sukses, dan status: false beserta reason jika gagal (misal device disconnected)
        if ($status !== true && $status !== 'true') {
            $reason = $json['reason'] ?? 'Gagal mengirim via Fonnte';
            if (stripos($reason, 'disconnected') !== false) {
                $reason = 'Device WhatsApp Fonnte sedang terputus (Disconnected). Silakan scan ulang QR code di https://md.fonnte.com/';
            }

            return ['success' => false, 'message' => $reason, 'data' => $json];
        }

        return ['success' => true, 'message' => 'Terkirim via Fonnte', 'data' => $json];
    }

    private static function sendViaWablas(string $nomor, string $pesan, string $token, ?string $device): array
    {
        $url = $device
            ? "https://api.wablas.com/api/send-message?device={$device}"
            : 'https://api.wablas.com/api/send-message';

        $response = Http::asForm()
            ->withHeaders(['Authorization' => $token])
            ->post($url, [
                'phone' => self::normalizeNomor($nomor),
                'message' => $pesan,
            ]);

        if (! $response->successful()) {
            return ['success' => false, 'message' => $response->json()['message'] ?? 'Gagal kirim via Wablas'];
        }

        $json = $response->json() ?? [];
        $status = $json['status'] ?? false;

        if ($status !== true && $status !== 'success') {
            return ['success' => false, 'message' => $json['message'] ?? 'Gagal kirim via Wablas', 'data' => $json];
        }

        return ['success' => true, 'message' => 'Terkirim via Wablas', 'data' => $json];
    }

    private static function sendViaCustom(string $nomor, string $pesan): array
    {
        $url = config('whatsapp.custom_url');
        $headers = config('whatsapp.custom_headers', []);

        if (! $url) {
            return ['success' => false, 'message' => 'Custom URL tidak dikonfigurasi'];
        }

        $response = Http::withHeaders($headers)
            ->post($url, [
                'phone' => self::normalizeNomor($nomor),
                'message' => $pesan,
            ]);

        return $response->successful()
            ? ['success' => true, 'message' => 'Terkirim via Custom', 'data' => $response->json()]
            : ['success' => false, 'message' => 'Gagal kirim custom'];
    }

    private static function normalizeNomor(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);
        if (str_starts_with($nomor, '0')) {
            return '62'.substr($nomor, 1);
        }
        if (str_starts_with($nomor, '62')) {
            return $nomor;
        }

        return '62'.$nomor;
    }
}
