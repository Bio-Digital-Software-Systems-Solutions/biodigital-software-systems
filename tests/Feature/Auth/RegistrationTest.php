<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // User is created but needs to verify email
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        // User should be redirected (either to dashboard or email verification)
        $response->assertRedirect();
    }
}
