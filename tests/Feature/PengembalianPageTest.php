<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PengembalianPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pengembalian')->assertRedirect(route('login'));
    }

    public function test_pengembalian_page_is_accessible(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/pengembalian')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('User/Pengembalian'));
    }
}
