<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\Pengajuan;
use App\Models\Pengembalian;
use App\Models\User;
use App\Support\PengajuanStore;
use App\Support\Persetujuan;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk melihat seluruh tampilan halaman.
 *
 * Isi: 5 peminjam, 5 barang, 5 pengembalian, 5 pengajuan
 * (3 menunggu, 2 ditolak), serta 9 peminjaman (5 selesai + 4 masih
 * dipinjam: 2 terlambat untuk halaman Denda dan 2 belum lewat tenggat
 * untuk halaman Prediksi).
 *
 * Jalankan manual: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Barang::query()->exists() || Peminjaman::query()->exists()) {
            $this->command?->warn('Data contoh sudah ada — seeder dilewati agar tidak duplikat.');

            return;
        }

        $peminjam = $this->peminjam();
        $barang = $this->barang();
        $this->transaksi($peminjam, $barang);
        $this->antrean($peminjam, $barang);

        $this->command?->info('Data contoh siap: 5 peminjam, 5 barang, 9 peminjaman, 5 pengembalian, 5 pengajuan.');
    }

    /**
     * @return array<string, User>
     */
    private function peminjam(): array
    {
        $daftar = [
            'ahmad' => [
                'name' => 'Ahmad Rizki Pratama',
                'email' => 'ahmad@contoh.ac.id',
                'nim' => '2210512001',
                'program_studi' => 'Teknologi Informasi',
                'no_hp' => '081234567801',
                'peran' => User::PERAN_MAHASISWA,
                'status_akun' => User::AKUN_AKTIF,
            ],
            'siti' => [
                'name' => 'Siti Aisyah',
                'email' => 'siti@contoh.ac.id',
                'nim' => '2210512002',
                'program_studi' => 'Teknologi Informasi',
                'no_hp' => '081234567802',
                'peran' => User::PERAN_MAHASISWA,
                'status_akun' => User::AKUN_AKTIF,
            ],
            'dimas' => [
                'name' => 'Dimas Saputra',
                'email' => 'dimas@contoh.ac.id',
                'nim' => '2210512003',
                'program_studi' => 'Sistem Informasi',
                'no_hp' => '081234567803',
                'peran' => User::PERAN_MAHASISWA,
                'status_akun' => User::AKUN_AKTIF,
            ],
            'rina' => [
                'name' => 'Rina Wulandari',
                'email' => 'rina@contoh.ac.id',
                'nim' => '2210512004',
                'program_studi' => 'Teknik Komputer',
                'no_hp' => '081234567804',
                'peran' => User::PERAN_MAHASISWA,
                'status_akun' => User::AKUN_NONAKTIF,
            ],
            'bambang' => [
                'name' => 'Bambang Sutrisno',
                'email' => 'bambang@contoh.ac.id',
                'nim' => null,
                'program_studi' => 'Teknologi Informasi',
                'no_hp' => '081234567805',
                'peran' => User::PERAN_DOSEN,
                'status_akun' => User::AKUN_AKTIF,
            ],
        ];

        $hasil = [];

        foreach ($daftar as $kunci => $data) {
            $hasil[$kunci] = User::query()->create([
                ...$data,
                'password' => 'password',
                'role' => User::ROLE_USER,
            ]);
        }

        return $hasil;
    }

    /**
     * @return array<string, Barang>
     */
    private function barang(): array
    {
        $daftar = [
            'BRG-001' => ['nama_barang' => 'Kabel LAN RJ45 5 Meter', 'kategori' => 'Kabel', 'kode_ntag' => '04A3B2C1D0E101', 'lokasi' => 'Ruang IT', 'status' => Barang::STATUS_DIPINJAM],
            'BRG-002' => ['nama_barang' => 'Card Reader NFC ACR122U', 'kategori' => 'Elektronik', 'kode_ntag' => '04A3B2C1D0E102', 'lokasi' => 'Ruang IT', 'status' => Barang::STATUS_DIPINJAM],
            'BRG-003' => ['nama_barang' => 'Router Mikrotik hEX', 'kategori' => 'Jaringan', 'kode_ntag' => '04A3B2C1D0E103', 'lokasi' => 'Lab Jaringan', 'status' => Barang::STATUS_DIPINJAM],
            'BRG-004' => ['nama_barang' => 'Proyektor Epson X490', 'kategori' => 'Elektronik', 'kode_ntag' => '04A3B2C1D0E104', 'lokasi' => 'Ruang Multimedia', 'status' => Barang::STATUS_DIPINJAM],
            'BRG-005' => ['nama_barang' => 'Multimeter Digital Fluke', 'kategori' => 'Alat Lab', 'kode_ntag' => '04A3B2C1D0E105', 'lokasi' => 'Lab Elektronika', 'status' => Barang::STATUS_TERSEDIA],
        ];

        $hasil = [];

        foreach ($daftar as $kode => $data) {
            $hasil[$kode] = Barang::query()->create([
                'kode_barang' => $kode,
                ...$data,
                'kondisi' => 'Baik',
                'deskripsi' => 'Peralatan praktikum siap dipakai.',
            ]);
        }

        return $hasil;
    }

    /**
     * 9 peminjaman (5 selesai, 2 masih dipinjam & terlambat, 2 masih
     * dipinjam sebelum tenggat) beserta 5 pengembalian agar halaman
     * Riwayat, Denda, dan Prediksi terlihat berisi.
     *
     * @param  array<string, User>  $peminjam
     * @param  array<string, Barang>  $barang
     */
    private function transaksi(array $peminjam, array $barang): void
    {
        $pinjam = fn (string $id, User $user, Barang $item, int $hariLalu, string $status, string $keperluan): Peminjaman => Peminjaman::query()->create([
            'pengajuan_id' => $id,
            'user_id' => $user->id,
            'barang_id' => $item->id,
            'peminjam' => $user->name,
            'nim' => $user->nim,
            'kode_barang' => $item->kode_barang,
            'nama_barang' => $item->nama_barang,
            'kategori' => $item->kategori,
            'tanggal_pinjam' => now()->subDays($hariLalu)->toDateString(),
            'jam_pinjam' => '08:00',
            'jam_selesai' => '16:00',
            'batas_kembali' => now()->subDays($hariLalu)->addDays(2)->toDateString(),
            'status' => $status,
            'keperluan' => $keperluan,
        ]);

        $kembali = function (string $id, Peminjaman $pinjaman, int $hariLalu): void {
            $terlambat = now()->subDays($hariLalu)->startOfDay()->greaterThan($pinjaman->batas_kembali->startOfDay());

            Pengembalian::query()->create([
                'pengajuan_id' => $id,
                'peminjaman_id' => $pinjaman->id,
                'user_id' => $pinjaman->user_id,
                'barang_id' => $pinjaman->barang_id,
                'peminjam' => $pinjaman->peminjam,
                'nim' => $pinjaman->nim,
                'kode_barang' => $pinjaman->kode_barang,
                'nama_barang' => $pinjaman->nama_barang,
                'kategori' => $pinjaman->kategori,
                'tanggal_kembali' => now()->subDays($hariLalu)->toDateString(),
                'jam_kembali' => '16:00',
                'catatan' => 'Dikembalikan dalam kondisi baik.',
                'status' => $terlambat ? Persetujuan::STATUS_TERLAMBAT : Persetujuan::STATUS_TEPAT,
            ]);
        };

        // Masih dipinjam & terlambat (sumber halaman Denda "Belum Kembali")
        $pinjam('PMJ-001', $peminjam['ahmad'], $barang['BRG-002'], 8, Peminjaman::STATUS_DIPINJAM, 'Praktikum jaringan komputer');
        $pinjam('PMJ-002', $peminjam['siti'], $barang['BRG-003'], 12, Peminjaman::STATUS_DIPINJAM, 'Uji coba topologi jaringan');

        // Masih dipinjam & belum lewat tenggat (sumber halaman Prediksi yang aman)
        $pinjam('PMJ-008', $peminjam['dimas'], $barang['BRG-001'], 2, Peminjaman::STATUS_DIPINJAM, 'Perpanjangan praktikum kabel');
        $pinjam('PMJ-009', $peminjam['bambang'], $barang['BRG-004'], 5, Peminjaman::STATUS_DIPINJAM, 'Persiapan kelas multimedia');

        // Selesai & sudah dikembalikan (2 di antaranya terlambat)
        $selesai = [
            ['PMJ-003', 'dimas', 'BRG-001', 10, 4, 'Praktikum kabel jaringan'],
            ['PMJ-004', 'rina', 'BRG-004', 20, 10, 'Presentasi tugas akhir'],
            ['PMJ-005', 'bambang', 'BRG-005', 15, 9, 'Praktikum elektronika dasar'],
            ['PMJ-006', 'ahmad', 'BRG-001', 30, 21, 'Latihan konfigurasi switch'],
            ['PMJ-007', 'siti', 'BRG-005', 40, 34, 'Pengukuran rangkaian'],
        ];

        foreach ($selesai as $urut => [$id, $kunci, $kode, $pinjamHari, $kembaliHari, $keperluan]) {
            $baris = $pinjam($id, $peminjam[$kunci], $barang[$kode], $pinjamHari, Peminjaman::STATUS_SELESAI, $keperluan);
            $kembali(sprintf('PGB-%03d', $urut + 1), $baris, $kembaliHari);
        }
    }

    /**
     * Antrean pengajuan: 2 menunggu persetujuan dan 1 ditolak.
     *
     * @param  array<string, User>  $peminjam
     * @param  array<string, Barang>  $barang
     */
    private function antrean(array $peminjam, array $barang): void
    {
        $isi = fn (User $user, Barang $item, string $keperluan): array => [
            'user_id' => $user->id,
            'peminjam' => $user->name,
            'nim' => $user->nim,
            'kode' => $item->kode_barang,
            'nama' => $item->nama_barang,
            'kategori' => $item->kategori,
            'tanggal' => now()->toDateString(),
            'jam' => now()->format('H:i'),
            'jamSelesai' => '16:00',
            'keperluan' => $keperluan,
        ];

        // Menunggu: peminjaman baru
        PengajuanStore::tambah(
            PengajuanStore::JENIS_PINJAM,
            $isi($peminjam['dimas'], $barang['BRG-004'], 'Revisi presentasi karya tulis')
        );

        PengajuanStore::tambah(
            PengajuanStore::JENIS_PINJAM,
            $isi($peminjam['bambang'], $barang['BRG-005'], 'Praktikum mikroprosesor')
        );

        // Menunggu: pengembalian barang yang sedang dipinjam
        $pinjaman = Peminjaman::query()->where('pengajuan_id', 'PMJ-002')->first();

        PengajuanStore::tambah(
            PengajuanStore::JENIS_KEMBALI,
            [
                ...$isi($peminjam['siti'], $barang['BRG-003'], 'Selesai uji coba'),
                'peminjaman_id' => $pinjaman?->id,
                'catatan' => 'Router dikembalikan lengkap.',
            ]
        );

        // Ditolak
        $ditolak = PengajuanStore::tambah(
            PengajuanStore::JENIS_PINJAM,
            $isi($peminjam['rina'], $barang['BRG-005'], 'Pengukuran skema')
        );

        PengajuanStore::tolak(
            PengajuanStore::JENIS_PINJAM,
            $ditolak['id'],
            'Akun berstatus nonaktif, silakan hubungi admin.'
        );

        $ditolakKedua = PengajuanStore::tambah(
            PengajuanStore::JENIS_PINJAM,
            $isi($peminjam['ahmad'], $barang['BRG-004'], 'Pinjam untuk rapat jurusan')
        );

        PengajuanStore::tolak(
            PengajuanStore::JENIS_PINJAM,
            $ditolakKedua['id'],
            'Barang sedang dipakai pengajuan lain yang lebih dahulu masuk.'
        );
    }
}
