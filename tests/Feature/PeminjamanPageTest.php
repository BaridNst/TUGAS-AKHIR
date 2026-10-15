<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PeminjamanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/peminjaman')->assertRedirect(route('login'));
    }

    public function test_peminjaman_page_is_accessible(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/peminjaman')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Peminjaman'));
    }
}
