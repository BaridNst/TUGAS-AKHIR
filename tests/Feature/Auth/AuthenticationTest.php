<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_users_can_authenticate_using_their_name(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->name,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_password_hash_is_upgraded_to_current_cost_on_login(): void
    {
        $user = User::factory()->create();

        // Cost lama dipasang langsung ke DB: cast `hashed` menolak cost > konfigurasi.
        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make('password', ['rounds' => 6]),
        ]);

        $user->refresh();

        $this->assertTrue(Hash::needsRehash($user->password));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertFalse(Hash::needsRehash($user->fresh()->password));
    }

    public function test_user_is_redirected_to_google_oauth(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth', $redirectUrl);
        $this->assertStringContainsString('client_id=', $redirectUrl);
        $this->assertStringContainsString('redirect_uri=', $redirectUrl);
    }

    public function test_user_can_authenticate_via_google_callback_and_is_redirected_to_complete_profile(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'mock_google_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google_uid_12345',
                'email' => 'mahasiswa@example.com',
                'name' => 'Budi Google',
                'picture' => 'https://example.com/avatar.jpg',
            ]),
        ]);

        $response = $this->get('/login?code=mock_authorization_code');

        $this->assertAuthenticated();
        // Karena profil baru dibuat via Google belum memiliki no_hp/nim/prodi, diarahkan ke complete-profile
        $response->assertRedirect(route('profile.complete', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa@example.com',
            'google_id' => 'google_uid_12345',
            'name' => 'Budi Google',
        ]);
    }

    public function test_google_user_with_complete_profile_is_redirected_to_dashboard(): void
    {
        User::factory()->create([
            'email' => 'dosen@example.com',
            'google_id' => 'google_uid_99999',
            'nim' => '198701012015011001',
            'program_studi' => 'Teknik Elektro',
            'no_hp' => '081298765432',
            'peran' => User::PERAN_DOSEN,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'mock_google_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google_uid_99999',
                'email' => 'dosen@example.com',
                'name' => 'Pak Dosen',
            ]),
        ]);

        $response = $this->get('/login?code=mock_authorization_code');

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_user_can_view_complete_profile_page(): void
    {
        $user = User::factory()->create([
            'nim' => null,
            'no_hp' => null,
            'program_studi' => null,
        ]);

        $response = $this->actingAs($user)->get(route('profile.complete'));

        $response->assertOk();
    }

    public function test_user_can_submit_complete_profile_form(): void
    {
        $user = User::factory()->create([
            'nim' => null,
            'no_hp' => null,
            'program_studi' => null,
        ]);

        $response = $this->actingAs($user)->post(route('profile.complete.store'), [
            'no_hp' => '081234567890',
            'nim' => '2210512001',
            'program_studi' => 'Teknologi Informasi',
            'peran' => 'Mahasiswa',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertSame('081234567890', $user->no_hp);
        $this->assertSame('2210512001', $user->nim);
        $this->assertSame('Teknologi Informasi', $user->program_studi);
        $this->assertSame(User::PERAN_MAHASISWA, $user->peran);
    }

    public function test_complete_profile_rejects_invalid_peran(): void
    {
        $user = User::factory()->create([
            'nim' => null,
            'no_hp' => null,
            'program_studi' => null,
        ]);

        $response = $this->actingAs($user)->post(route('profile.complete.store'), [
            'no_hp' => '081234567890',
            'nim' => '2210512001',
            'program_studi' => 'Teknologi Informasi',
            'peran' => 'Admin', // Tidak diizinkan, hanya Mahasiswa dan Dosen
        ]);

        $response->assertSessionHasErrors(['peran']);
    }
}
