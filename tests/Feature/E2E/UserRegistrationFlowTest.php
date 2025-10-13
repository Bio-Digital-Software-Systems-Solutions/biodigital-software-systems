<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\CreatesPermissions;

class UserRegistrationFlowTest extends TestCase
{
    use RefreshDatabase, CreatesPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function complete_user_registration_flow(): void
    {
        // Step 1: Visit registration page
        $response = $this->get('/register');
        $response->assertSuccessful();

        // Step 2: Fill registration form
        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ];

        $response = $this->post('/register', $userData);

        // Step 3: Verify successful redirect (to verification notice or dashboard)
        $this->assertTrue($response->isRedirect());

        // Step 4: Verify user created in database
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        // Step 5: Verify user has default member role
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertTrue($user->hasRole('member'));

        // Step 6: Verify authenticated user is redirected or can access dashboard
        $user->email_verified_at = now();
        $user->save();

        $response = $this->actingAs($user)->get('/dashboard');
        // Accept either successful access or redirect (depends on email verification settings)
        $this->assertTrue(
            $response->isSuccessful() ||
            $response->isRedirect()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_login_after_registration(): void
    {
        // Register user
        $this->post('/register', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'birth_date' => '1995-05-15',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        // Logout
        $this->post('/logout');

        // Login with credentials
        $response = $this->post('/login', [
            'email' => 'jane@example.com',
            'password' => 'SecurePassword123!',
        ]);

        $response->assertRedirect('/dashboard');

        // Verify authenticated
        $this->assertAuthenticated();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function newly_registered_user_can_view_events(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/events');

        $response->assertSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function newly_registered_user_cannot_create_events_without_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this->actingAs($user)->get('/events/create');

        // Accept either 403 Forbidden or 302 Redirect as access denied
        $this->assertContains($response->status(), [403, 302]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_profile_is_accessible_after_registration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Edit')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_update_profile_after_registration(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Name',
            'birth_date' => '1990-01-01',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'birth_date' => $user->birth_date->toDateString(),
            'email' => $user->email,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function duplicate_email_registration_is_prevented(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registration_creates_activity_log(): void
    {
        $this->post('/register', [
            'first_name' => 'Activity',
            'last_name' => 'User',
            'email' => 'activity@example.com',
            'birth_date' => '1992-03-20',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);

        $user = User::where('email', 'activity@example.com')->first();

        // Verify activity log exists
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'event' => 'created',
        ]);
    }
}
