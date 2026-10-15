<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Buat akun admin (login memakai email ATAU nama).
     */
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin'],
            [
                'name' => 'Admin',
                'password' => '123456',
                'role' => User::ROLE_ADMIN,
                'no_hp' => '085212583609',
            ],
        );

        if ($admin->role !== User::ROLE_ADMIN) {
            $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        }
    }
}
