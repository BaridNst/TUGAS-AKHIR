<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Kolom opsional dikosongkan dulu agar '' tidak tersimpan dan
     * aturan unique NIM mengecek NULL (banyak NULL tetap diizinkan).
     */
    protected function prepareForValidation(): void
    {
        foreach (['nim', 'program_studi', 'no_hp'] as $kolom) {
            if ($this->input($kolom) === '') {
                $this->merge([$kolom => null]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'nim' => ['nullable', 'string', 'max:30', 'unique:users,nim'],
            'program_studi' => ['nullable', 'string', 'max:120'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'peran' => ['required', 'string', Rule::in([User::PERAN_MAHASISWA, User::PERAN_DOSEN])],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
