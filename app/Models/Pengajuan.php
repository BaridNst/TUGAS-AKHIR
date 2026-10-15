<?php

namespace App\Models;

use Database\Factories\PengajuanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pengajuan peminjaman / pengembalian (PRD: entitas Pengajuan).
 *
 * Hanya pengajuan yang disetujui yang memunculkan baris di tabel
 * `peminjaman` / `pengembalian`, sehingga pengajuan ditolak tidak pernah
 * menjadi data transaksi.
 *
 * @property int $id
 * @property string $pengajuan_id
 * @property string $jenis_pengajuan
 * @property int|null $user_id
 * @property int|null $barang_id
 * @property int|null $peminjaman_id
 * @property string $peminjam
 * @property string|null $nim
 * @property string $kode
 * @property string $nama
 * @property string|null $kategori
 * @property int $jumlah
 * @property Carbon $tanggal_pengajuan
 * @property Carbon|null $jam
 * @property Carbon|null $jam_selesai
 * @property string|null $keperluan
 * @property string|null $catatan
 * @property string $status
 * @property string|null $alasan
 * @property int|null $id_pengelola
 * @property Carbon|null $tanggal_persetujuan
 * @property Carbon|null $ditolak_pada
 */
#[Fillable([
    'pengajuan_id',
    'jenis_pengajuan',
    'user_id',
    'barang_id',
    'peminjaman_id',
    'peminjam',
    'nim',
    'kode',
    'nama',
    'kategori',
    'jumlah',
    'tanggal_pengajuan',
    'jam',
    'jam_selesai',
    'keperluan',
    'catatan',
    'status',
    'alasan',
    'id_pengelola',
    'tanggal_persetujuan',
    'ditolak_pada',
])]
class Pengajuan extends Model
{
    /** @use HasFactory<PengajuanFactory> */
    use HasFactory;

    protected $table = 'pengajuans';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal_pengajuan' => 'date',
            'jam' => 'datetime',
            'jam_selesai' => 'datetime',
            'tanggal_persetujuan' => 'datetime',
            'ditolak_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function pengelola(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengelola');
    }
}
