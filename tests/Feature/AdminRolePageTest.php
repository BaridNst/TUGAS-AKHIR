<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminRolePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_user_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(User::ROLE_USER, $user->fresh()->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_regular_user_sees_user_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('User/Dashboard'));

        $this->actingAs($user)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('User/Profile'));
    }

    public function test_admin_sees_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->isAdmin());

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Dashboard')
                ->where('auth.user.role', User::ROLE_ADMIN));

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Profile'));
    }

    public function test_regular_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dataBarangPeminjaman'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.prediksi'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.pengguna'))
            ->assertForbidden();
    }

    public function test_admin_seeder_creates_admin_account(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'admin')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue(Hash::check('123456', $admin->password));
    }
}
