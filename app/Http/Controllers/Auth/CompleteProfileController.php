<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompleteProfileController extends Controller
{
    /**
     * Tampilkan formulir pelengkap profil untuk akun baru / Google OAuth.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->needsProfileCompletion()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/CompleteProfile', [
            'user' => $user->only(['id', 'name', 'email', 'avatar', 'nim', 'program_studi', 'no_hp', 'peran']),
        ]);
    }

    /**
     * Simpan data pelengkap profil (no_hp, nim, program_studi, peran).
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->filled('no_hp')) {
            $noHpClean = (string) preg_replace('/[^\d+]/', '', (string) $request->input('no_hp'));
            if (str_starts_with($noHpClean, '+62')) {
                $noHpClean = '0'.substr($noHpClean, 3);
            } elseif (str_starts_with($noHpClean, '62') && strlen($noHpClean) >= 10) {
                $noHpClean = '0'.substr($noHpClean, 2);
            }
            $request->merge(['no_hp' => $noHpClean]);
        }

        $validated = $request->validate([
            'no_hp' => [
                'required',
                'string',
                'max:30',
                'regex:/^08[0-9]{8,12}$/',
            ],
            'nim' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'nim')->ignore($user->id),
            ],
            'program_studi' => [
                'required',
                'string',
                'max:120',
            ],
            'peran' => [
                'required',
                'string',
                Rule::in([User::PERAN_MAHASISWA, User::PERAN_DOSEN]),
            ],
        ], [
            'no_hp.required' => 'Nomor telepon / WhatsApp wajib diisi.',
            'no_hp.regex' => 'Format nomor telepon tidak valid (contoh: 081234567890).',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM ini sudah digunakan oleh akun lain.',
            'program_studi.required' => 'Program studi wajib diisi.',
            'peran.required' => 'Peran wajib dipilih.',
            'peran.in' => 'Peran harus dipilih antara Mahasiswa atau Dosen.',
        ]);

        $user->update($validated);

        return redirect()->route('dashboard')->with('success', 'Profil berhasil dilengkapi!');
    }
}
