<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Transaksi pengembalian yang sudah disetujui admin.
 *
 * Sama seperti peminjaman, baris hanya dibuat saat pengajuan disetujui sehingga
 * pengajuan yang ditolak tidak pernah menjadi bagian dari data transaksi.
 *
 * @property int $id
 * @property string $pengajuan_id
 * @property int|null $peminjaman_id
 * @property int|null $user_id
 * @property int|null $barang_id
 * @property string $peminjam
 * @property string|null $nim
 * @property string $kode_barang
 * @property string $nama_barang
 * @property string|null $kategori
 * @property Carbon $tanggal_kembali
 * @property Carbon $jam_kembali
 * @property string|null $catatan
 * @property string $status
 */
#[Fillable([
    'pengajuan_id',
    'peminjaman_id',
    'user_id',
    'barang_id',
    'peminjam',
    'nim',
    'kode_barang',
    'nama_barang',
    'kategori',
    'tanggal_kembali',
    'jam_kembali',
    'catatan',
    'status',
])]
class Pengembalian extends Model
{
    use HasFactory;

    protected $table = 'pengembalian';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kembali' => 'date',
            'jam_kembali' => 'datetime',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
