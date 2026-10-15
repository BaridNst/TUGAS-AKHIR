<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Tampilkan halaman profil akun yang sedang login.
     */
    public function index(Request $request): Response
    {
        return Inertia::render($request->user()->isAdmin() ? 'Admin/Profile' : 'User/Profile', [
            'user' => $request->user()->only(['id', 'name', 'email', 'no_hp', 'nim', 'program_studi', 'peran', 'avatar', 'google_id']),
        ]);
    }

    /**
     * Perbarui informasi profil (nama, email & no_hp).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'no_hp' => ['nullable', 'string', 'max:20', 'regex:/^08[0-9]{8,11}$/'],
        ]);

        $request->user()->update($validated);

        return back();
    }

    /**
     * Perbarui kata sandi akun.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back();
    }
}
