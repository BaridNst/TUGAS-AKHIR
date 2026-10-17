<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Transaksi peminjaman yang sudah disetujui admin.
 *
 * Baris hanya dibuat saat pengajuan disetujui, sehingga setiap baris di tabel
 * ini selalu menjadi bagian dari data transaksi.
 *
 * @property int $id
 * @property string $pengajuan_id
 * @property int|null $user_id
 * @property int|null $barang_id
 * @property string $peminjam
 * @property string|null $nim
 * @property string $kode_barang
 * @property string $nama_barang
 * @property string|null $kategori
 * @property Carbon $tanggal_pinjam
 * @property Carbon $jam_pinjam
 * @property Carbon|null $jam_selesai
 * @property Carbon|null $batas_kembali
 * @property string $status
 * @property string|null $keperluan
 */
#[Fillable([
    'pengajuan_id',
    'user_id',
    'barang_id',
    'peminjam',
    'nim',
    'kode_barang',
    'nama_barang',
    'kategori',
    'tanggal_pinjam',
    'jam_pinjam',
    'jam_selesai',
    'batas_kembali',
    'status',
    'keperluan',
    'pengingat_terkirim_at',
])]
class Peminjaman extends Model
{
    use HasFactory;

    public const STATUS_DIPINJAM = 'Dipinjam';

    public const STATUS_SELESAI = 'Selesai';

    protected $table = 'peminjaman';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_pinjam' => 'date',
            'jam_pinjam' => 'datetime',
            'jam_selesai' => 'datetime',
            'batas_kembali' => 'date',
            'pengingat_terkirim_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function pengembalian(): HasMany
    {
        return $this->hasMany(Pengembalian::class);
    }

    /**
     * Peminjaman disetujui yang belum ditutup oleh pengembalian disetujui.
     *
     * @return Collection<int, Peminjaman>
     */
    public static function sedangDipinjam(): Collection
    {
        $sudahKembali = Pengembalian::query()->pluck('peminjaman_id')->filter()->values();

        $query = static::query();

        if ($sudahKembali->isNotEmpty()) {
            $query->whereNotIn('id', $sudahKembali);
        }

        return $query->orderBy('id')->get();
    }
}
