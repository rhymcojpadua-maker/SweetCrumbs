<?php

namespace Tests\Feature;

use Statamic\Facades\User;
use Tests\TestCase;

class StatamicCpTest extends TestCase
{
    public function test_login_screen_is_reachable(): void
    {
        $this->get('/cp')->assertRedirect('/cp/auth/login');
        $this->get('/cp/auth/login')->assertOk();
    }

    public function test_super_admin_can_log_in_with_credentials(): void
    {
        $this->post('/cp/auth/login', [
            'email' => 'admin@cakelle.ph',
            'password' => 'Password123!',
        ])->assertRedirect('/cp');

        $this->assertAuthenticatedAs(User::findByEmail('admin@cakelle.ph'), 'cp');
    }

    public function test_super_admin_can_open_the_control_panel(): void
    {
        $admin = User::findByEmail('admin@cakelle.ph');

        $this->assertNotNull($admin);
        $this->assertTrue($admin->isSuper());

        $this->actingAs($admin, 'cp')
            ->get('/cp')
            ->assertRedirect('/cp/dashboard');

        $this->actingAs($admin, 'cp')
            ->get('/cp/dashboard')
            ->assertOk();
    }
}
