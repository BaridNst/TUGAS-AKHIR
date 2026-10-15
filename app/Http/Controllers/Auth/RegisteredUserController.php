<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman pendaftaran akun baru.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Buat akun baru, selalu dengan role user, lalu masukkan penggunanya.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'nim', 'program_studi', 'no_hp', 'peran']),
            'password' => $request->string('password')->toString(),
            'role' => User::ROLE_USER,
            'status_akun' => User::AKUN_AKTIF,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
