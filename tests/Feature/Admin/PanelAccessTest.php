<?php

namespace Tests\Feature\Admin;

use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_admins_can_open_the_admin_panel(): void
    {
        $this->get('/ruang-admin')->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'user']))->get('/ruang-admin')->assertForbidden();
        $this->get('/admin')->assertNotFound();
    }

    public function test_admin_panel_requests_shorten_the_session_lifetime_without_affecting_the_public_app(): void
    {
        $this->get('/');
        $this->assertNotSame(30, config('session.lifetime'));

        $this->get('/ruang-admin/login');
        $this->assertSame(30, config('session.lifetime'));
    }
}
