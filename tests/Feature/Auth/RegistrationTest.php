<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_are_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.ac.id',
            'nim' => '2210512099',
            'program_studi' => 'Teknologi Informasi',
            'no_hp' => '081234567890',
            'peran' => User::PERAN_MAHASISWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'budi@contoh.ac.id')->firstOrFail();

        $this->assertSame('2210512099', $user->nim);
        $this->assertSame(User::PERAN_MAHASISWA, $user->peran);
        $this->assertSame(User::AKUN_AKTIF, $user->status_akun);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_register_ignores_privileged_attributes(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@contoh.ac.id',
            'peran' => User::PERAN_MAHASISWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => User::ROLE_ADMIN,
            'status_akun' => User::AKUN_NONAKTIF,
        ]);

        $user = User::query()->where('email', 'penyusup@contoh.ac.id')->firstOrFail();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertSame(User::AKUN_AKTIF, $user->status_akun);
    }

    public function test_register_rejects_empty_payload(): void
    {
        $response = $this->from('/register')->post('/register', []);

        $response->assertSessionHasErrors(['name', 'email', 'peran', 'password']);
        $this->assertGuest();
    }

    public function test_register_rejects_mismatched_password_confirmation(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.ac.id',
            'peran' => User::PERAN_MAHASISWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'berbeda123',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_register_rejects_unknown_peran(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.ac.id',
            'peran' => 'Karyawan',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('peran');
        $this->assertGuest();
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'budi@contoh.ac.id']);

        $response = $this->post('/register', [
            'name' => 'Budi Lain',
            'email' => 'budi@contoh.ac.id',
            'peran' => User::PERAN_MAHASISWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
    }

    public function test_register_rejects_duplicate_nim(): void
    {
        User::factory()->create(['nim' => '2210512099']);

        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@contoh.ac.id',
            'nim' => '2210512099',
            'peran' => User::PERAN_MAHASISWA,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('nim');
        $this->assertGuest();
        $this->assertSame(1, User::query()->count());
    }
}
