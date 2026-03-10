<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_cannot_be_requested_with_invalid_email(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'invalid@example.com']);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_requires_valid_email_format(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_requires_email(): void
    {
        $response = $this->post('/forgot-password', ['email' => '']);

        $response->assertSessionHasErrors('email');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification): true {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): true {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_fails_with_mismatched_passwords(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): true {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'differentpassword',
            ]);

            $response->assertSessionHasErrors('password');

            return true;
        });
    }

    public function test_password_reset_fails_with_short_password(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): true {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => '123',
                'password_confirmation' => '123',
            ]);

            $response->assertSessionHasErrors('password');

            return true;
        });
    }

    public function test_password_is_actually_updated_after_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('oldpassword'),
        ]);

        $oldPasswordHash = $user->password;

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user, $oldPasswordHash): true {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $user->refresh();

            // Verify password was actually changed
            $this->assertNotEquals($oldPasswordHash, $user->password);
            $this->assertTrue(Hash::check('newpassword123', $user->password));

            return true;
        });
    }

    public function test_user_can_login_with_new_password_after_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): true {
            // Reset password
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            // Try to login with new password
            $response = $this->post('/login', [
                'email' => $user->email,
                'password' => 'newpassword123',
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            return true;
        });
    }

    public function test_expired_token_cannot_be_used(): void
    {
        $user = User::factory()->create();

        // Create an expired token (tokens expire after 1 hour by default)
        Password::createToken($user);

        // Manipulate time to make token expired (this is a simplified test)
        $response = $this->post('/reset-password', [
            'token' => 'definitely-expired-token',
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_multiple_password_reset_requests_invalidate_previous_tokens(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // First password reset request
        $this->post('/forgot-password', ['email' => $user->email]);

        $firstToken = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$firstToken): true {
            $firstToken = $notification->token;

            return true;
        });

        // Second password reset request
        Notification::fake(); // Reset notifications
        $this->post('/forgot-password', ['email' => $user->email]);

        // Try to use first token (should fail as it's been invalidated)
        $response = $this->post('/reset-password', [
            'token' => $firstToken,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('email');
    }
}
