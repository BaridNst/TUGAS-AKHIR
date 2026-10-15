<?php

namespace App\Support;

/**
 * Normalisasi UID stiker NFC/NTAG agar format yang dikirim pembaca HP dan
 * nilai yang tersimpan di `barangs.kode_ntag` selalu dianggap sama.
 *
 * Web NDEFReader (Chrome Android) mengirim `serialNumber` dengan pemisah kolom
 * atau strip, misalnya `04:a3:b2:c1:d0:e1:01` atau `00-11-22-33-44-55-66`,
 * sedangkan UID yang disimpan lewat halaman admin ditulis huruf besar tanpa
 * pemisah, misalnya `04A3B2C1D0E101`.
 */
class NfcUid
{
    /**
     * Bentuk kanonik UID: huruf besar tanpa pemisah.
     */
    public static function normalisasi(string $nfcUid): string
    {
        return strtoupper((string) preg_replace('/[:\-\.\s,]/', '', trim($nfcUid)));
    }

    /**
     * Apakah nilai hasil normalisasi masih berupa UID heksadesimal 4-40 karakter?
     */
    public static function valid(string $nfcUid): bool
    {
        return preg_match('/^[0-9A-F]{4,40}$/', self::normalisasi($nfcUid)) === 1;
    }

    /**
     * Ekspresi SQL untuk membandingkan kolom `kode_ntag` dengan nilai kanonik
     * tanpa memedulikan pemisah maupun huruf besar/kecil.
     */
    public static function sqlNormalisasi(string $kolom = 'kode_ntag'): string
    {
        return "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$kolom}, ':', ''), '-', ''), '.', ''), ' ', ''), ',', ''))";
    }
}
