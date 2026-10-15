<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Peminjaman;
use App\Models\User;
use App\Support\PengajuanStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Barang::query()->create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
            'kode_ntag' => '04A3B2C1D0E101',
            'lokasi' => 'Ruang IT',
            'status' => Barang::STATUS_TERSEDIA,
        ]);

        Barang::query()->create([
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Card Reader NFC ACR122U',
            'kategori' => 'NFC Reader',
            'kode_ntag' => '04A3B2C1D0E102',
            'lokasi' => 'Ruang IT',
            'status' => Barang::STATUS_DIPINJAM,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('peminjaman.store'), $this->payload())
            ->assertRedirect(route('login'));
    }

    public function test_user_submits_loan_request_from_nfc_scan(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('peminjaman.store'), $this->payload(['catatan' => 'Praktikum jaringan']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $pengajuan = collect($this->antreanPinjam())->first(
            fn (array $item) => $item['kode'] === 'BRG-001' && $item['user_id'] === $user->id
        );

        $this->assertNotNull($pengajuan);
        $this->assertSame(PengajuanStore::STATUS_PENDING, $pengajuan['status']);
        $this->assertSame($user->id, $pengajuan['user_id']);
        $this->assertSame($user->name, $pengajuan['peminjam']);
        $this->assertSame('Praktikum jaringan', $pengajuan['keperluan']);
        $this->assertSame('Kabel LAN RJ45 5 Meter', $pengajuan['nama']);

        $this->actingAs($this->admin())
            ->get(route('admin.peminjaman'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pengajuan', fn ($pengajuan) => $pengajuan->contains(
                    fn (array $item) => $item['kode'] === 'BRG-001' && $item['user_id'] === $user->id
                )));
    }

    public function test_admin_cannot_submit_loan_request(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->post(route('peminjaman.store'), $this->payload())
            ->assertForbidden();

        $this->assertCount(0, array_filter(
            $this->antreanPinjam(),
            fn (array $item) => $item['user_id'] !== null
        ));
    }

    public function test_unknown_nfc_tag_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $sebelum = count($this->antreanPinjam());

        $this->actingAs($user)
            ->post(route('peminjaman.store'), $this->payload(['nfc_uid' => 'FF000000000000']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('error');

        $this->assertCount($sebelum, $this->antreanPinjam());
    }

    public function test_duplicate_pending_loan_request_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('peminjaman.store'), $this->payload());
        $this->actingAs($user)
            ->post(route('peminjaman.store'), $this->payload())
            ->assertSessionHas('error');

        $this->assertCount(1, array_filter(
            $this->antreanPinjam(),
            fn (array $item) => $item['user_id'] === $user->id && $item['kode'] === 'BRG-001'
        ));
    }

    public function test_loan_request_for_borrowed_barang_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $sebelum = count($this->antreanPinjam());

        $this->actingAs($user)
            ->post(route('peminjaman.store'), $this->payload(['nfc_uid' => '04A3B2C1D0E102']))
            ->assertSessionHas('error');

        $this->assertCount($sebelum, $this->antreanPinjam());
    }

    public function test_return_request_without_open_loan_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $sebelum = count($this->antreanKembali());

        $this->actingAs($user)
            ->post(route('pengembalian.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertSessionHas('error');

        $this->assertCount($sebelum, $this->antreanKembali());
    }

    public function test_return_request_for_another_users_loan_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Peminjaman::factory()->create([
            'user_id' => User::factory()->create()->id,
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
        ]);
        $sebelum = count($this->antreanKembali());

        $this->actingAs($user)
            ->post(route('pengembalian.store'), $this->payload())
            ->assertSessionHas('error');

        $this->assertCount($sebelum, $this->antreanKembali());
    }

    public function test_user_submits_return_request_for_own_loan(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $loan = Peminjaman::factory()->create([
            'user_id' => $user->id,
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Kabel LAN RJ45 5 Meter',
            'kategori' => 'Kabel',
        ]);

        $this->actingAs($user)
            ->post(route('pengembalian.store'), $this->payload(['catatan' => 'Kabel sudah dipakai']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $pengajuan = collect($this->antreanKembali())->first(
            fn (array $item) => $item['kode'] === 'BRG-001' && $item['user_id'] === $user->id
        );

        $this->assertNotNull($pengajuan);
        $this->assertSame(PengajuanStore::STATUS_PENDING, $pengajuan['status']);
        $this->assertSame($loan->id, $pengajuan['peminjaman_id']);
        $this->assertSame($user->id, $pengajuan['user_id']);
        $this->assertSame('Kabel sudah dipakai', $pengajuan['catatan']);
    }

    public function test_admin_cannot_submit_return_request(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->post(route('pengembalian.store'), $this->payload())
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nfc_uid' => '04A3B2C1D0E101',
            'catatan' => null,
        ], $overrides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function antreanPinjam(): array
    {
        return PengajuanStore::antrean(PengajuanStore::JENIS_PINJAM);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function antreanKembali(): array
    {
        return PengajuanStore::antrean(PengajuanStore::JENIS_KEMBALI);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }
}
