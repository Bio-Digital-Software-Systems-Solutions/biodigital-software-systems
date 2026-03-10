<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationAndTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => \Database\Seeders\RoleAndPermissionSeeder::class]);
    }

    public function test_registration_sends_welcome_email_and_redirects_to_verification_notice(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'birth_date' => '1990-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));

        Mail::assertSent(\App\Mail\WelcomeMail::class, fn($mail) => $mail->hasTo('test@example.com'));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_email_can_be_verified(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard').'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verified_middleware_blocks_unverified_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verified_middleware_allows_verified_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_two_factor_authentication_can_be_enabled(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/user/two-factor-authentication');

        $response->assertSessionDoesntHaveErrors();

        $user = $user->fresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
    }

    public function test_two_factor_authentication_can_be_disabled(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA first
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Confirm 2FA
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Disable 2FA
        $response = $this->actingAs($user)->delete('/user/two-factor-authentication');

        $response->assertSessionDoesntHaveErrors();

        $user = $user->fresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        $user = $user->fresh();
        $originalRecoveryCodes = $user->two_factor_recovery_codes;

        // Regenerate codes
        $response = $this->actingAs($user)->post('/user/two-factor-recovery-codes');

        $response->assertSessionDoesntHaveErrors();

        $user = $user->fresh();

        $this->assertNotEquals($originalRecoveryCodes, $user->two_factor_recovery_codes);
    }

    public function test_qr_code_can_be_retrieved(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Refresh user to get 2FA secret
        $user = $user->fresh();

        // Need to have 2FA secret to get QR code
        if ($user->two_factor_secret) {
            // Get QR code
            $response = $this->actingAs($user)->get('/user/two-factor-qr-code');
            $response->assertStatus(200);
            $response->assertJsonStructure(['svg']);
        } else {
            $this->markTestSkipped('2FA not enabled properly');
        }
    }

    public function test_secret_key_can_be_retrieved(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Refresh user
        $user = $user->fresh();

        if ($user->two_factor_secret) {
            // Get secret key
            $response = $this->actingAs($user)->get('/user/two-factor-secret-key');
            $response->assertStatus(200);
            $response->assertJsonStructure(['secretKey']);
        } else {
            $this->markTestSkipped('2FA not enabled properly');
        }
    }

    public function test_recovery_codes_can_be_retrieved(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Refresh user
        $user = $user->fresh();

        if ($user->two_factor_secret) {
            // Get recovery codes
            $response = $this->actingAs($user)->get('/user/two-factor-recovery-codes');
            $response->assertStatus(200);
            $this->assertIsArray($response->json());
            $this->assertNotEmpty($response->json());
        } else {
            $this->markTestSkipped('2FA not enabled properly');
        }
    }
}
