<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Dapatkan Redirect URI yang dinamis sesuai host (Localhost atau Ngrok).
     */
    protected function getRedirectUri(Request $request): string
    {
        $configuredUri = config('services.google.redirect_uri');
        $path = $configuredUri ? (parse_url($configuredUri, PHP_URL_PATH) ?: '/auth/google/callback') : '/auth/google/callback';

        return rtrim($request->getSchemeAndHttpHost(), '/').$path;
    }

    /**
     * Mengarahkan pengguna ke halaman autentikasi Google OAuth 2.0.
     */
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $redirectUri = $this->getRedirectUri($request);

        if (! $clientId || ! $redirectUri) {
            return redirect()->route('login')->with('error', 'Konfigurasi Google Client belum lengkap di server.');
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);
        $request->session()->put('google_oauth_redirect_uri', $redirectUri);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'access_type' => 'offline',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    /**
     * Menangani callback autentikasi dari Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            $errorDesc = $request->query('error_description', 'Login Google dibatalkan.');
            Log::warning('Google OAuth callback error', ['error' => $request->query('error'), 'description' => $errorDesc]);

            return redirect()->route('login')->with('error', 'Login Google dibatalkan atau tidak diizinkan.');
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('login')->with('error', 'Kode autentikasi Google tidak ditemukan.');
        }

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = $request->session()->get('google_oauth_redirect_uri') ?? $this->getRedirectUri($request);

        try {
            $tokenResponse = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('Google OAuth token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'response' => $tokenResponse->json() ?? $tokenResponse->body(),
                ]);

                return redirect()->route('login')->with('error', 'Gagal memverifikasi akun Google dengan server. Silakan coba kembali.');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (! $accessToken) {
                return redirect()->route('login')->with('error', 'Token akses Google tidak valid.');
            }

            $userResponse = Http::withToken($accessToken)->timeout(15)->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if (! $userResponse->successful()) {
                Log::error('Google OAuth userinfo failed', [
                    'status' => $userResponse->status(),
                    'response' => $userResponse->body(),
                ]);

                return redirect()->route('login')->with('error', 'Gagal mengambil informasi profil dari akun Google.');
            }

            $googleUser = $userResponse->json();
            $googleId = $googleUser['sub'] ?? null;
            $email = $googleUser['email'] ?? null;
            $name = $googleUser['name'] ?? ($email ? explode('@', $email)[0] : 'User');
            $avatar = $googleUser['picture'] ?? null;

            if (! $email) {
                return redirect()->route('login')->with('error', 'Email dari akun Google tidak ditemukan.');
            }

            // Cari user berdasarkan google_id atau email
            $user = User::where('google_id', $googleId)->first();

            if (! $user) {
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                // Update google_id / avatar jika belum ada
                $updates = [];
                if (! $user->google_id && $googleId) {
                    $updates['google_id'] = $googleId;
                }
                if ($avatar && $user->avatar !== $avatar) {
                    $updates['avatar'] = $avatar;
                }
                if (! empty($updates)) {
                    $user->update($updates);
                }

                if ($user->status_akun === User::AKUN_NONAKTIF) {
                    return redirect()->route('login')->with('error', 'Akun Anda berstatus nonaktif. Silakan hubungi admin.');
                }
            } else {
                // Buat user baru
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'password' => Hash::make(Str::random(32)),
                    'role' => User::ROLE_USER,
                    'peran' => User::PERAN_MAHASISWA,
                    'status_akun' => User::AKUN_AKTIF,
                    'email_verified_at' => now(),
                ]);
            }

            Auth::login($user, remember: true);

            $request->session()->forget(['google_oauth_state', 'google_oauth_redirect_uri']);
            $request->session()->regenerate();

            if ($user->needsProfileCompletion()) {
                return redirect()->route('profile.complete');
            }

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Throwable $e) {
            Log::error('Google OAuth exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')->with('error', 'Terjadi kesalahan sistem saat login menggunakan Google.');
        }
    }
}
