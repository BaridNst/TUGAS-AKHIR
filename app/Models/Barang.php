<?php

namespace App\Models;

use App\Support\NfcUid;
use Database\Factories\BarangFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master barang (PRD: entitas Barang).
 *
 * Nilai `status` memakai label PRD (Tersedia | Dipinjam) dan ikut disinkronkan
 * pada saat persetujuan, sedangkan status yang dikirim ke frontend tetap
 * dihitung ulang oleh App\Support\BarangStatus berdasarkan data transaksi.
 *
 * @property int $id
 * @property string $kode_barang
 * @property string $nama_barang
 * @property string|null $kategori
 * @property string $kode_ntag
 * @property string|null $lokasi
 * @property string $kondisi
 * @property string $status
 * @property string|null $deskripsi
 */
#[Fillable([
    'kode_barang',
    'nama_barang',
    'kategori',
    'kode_ntag',
    'lokasi',
    'kondisi',
    'status',
    'deskripsi',
])]
class Barang extends Model
{
    /** @use HasFactory<BarangFactory> */
    use HasFactory;

    public const STATUS_TERSEDIA = 'Tersedia';

    public const STATUS_DIPINJAM = 'Dipinjam';

    /**
     * Cari barang berdasarkan UID stiker NFC tanpa memedulikan huruf besar/kecil
     * maupun pemisah (`04:a3-b2` sama dengan `04A3B2`).
     */
    public static function dariNfcUid(string $nfcUid): ?self
    {
        $normal = NfcUid::normalisasi($nfcUid);

        if ($normal === '') {
            return null;
        }

        return static::query()
            ->whereRaw(NfcUid::sqlNormalisasi().' = ?', [$normal])
            ->first();
    }

    /**
     * Buat kode barang baru berikutnya (BRG-001, BRG-002, ...).
     *
     * Form admin tidak meminta kode, sehingga kode dihasilkan sistem.
     */
    public static function kodeBaru(): string
    {
        $terakhir = static::query()
            ->orderByDesc('kode_barang')
            ->value('kode_barang');

        $urut = preg_match('/^BRG-(\d+)$/', (string) $terakhir, $cocok) === 1
            ? (int) $cocok[1]
            : 0;

        do {
            $urut++;
            $kode = sprintf('BRG-%03d', $urut);
        } while (static::query()->where('kode_barang', $kode)->exists());

        return $kode;
    }
}
