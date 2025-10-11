<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);
    }

    public function test_user_can_view_2fa_settings_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Profile/Edit')
        );
    }

    public function test_user_can_enable_2fa(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // User should not have 2FA enabled
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);

        // Enable 2FA
        $response = $this->actingAs($user)
            ->post('/user/two-factor-authentication');

        $response->assertStatus(200);

        // Refresh user
        $user = $user->fresh();

        // Check that 2FA is enabled
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
    }

    public function test_user_can_view_qr_code_after_enabling_2fa(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Get QR code
        $response = $this->actingAs($user)->get('/user/two-factor-qr-code');

        $response->assertStatus(200);
        $response->assertJsonStructure(['svg']);
        $this->assertStringContainsString('<svg', $response->json('svg'));
    }

    public function test_user_can_view_recovery_codes_after_enabling_2fa(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Get recovery codes
        $response = $this->actingAs($user)->get('/user/two-factor-recovery-codes');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
        $this->assertCount(8, $response->json()); // Should have 8 recovery codes
    }

    public function test_user_can_regenerate_recovery_codes(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Get original codes
        $originalCodes = $this->actingAs($user)
            ->get('/user/two-factor-recovery-codes')
            ->json();

        // Regenerate codes
        $this->actingAs($user)->post('/user/two-factor-recovery-codes');

        // Get new codes
        $newCodes = $this->actingAs($user)
            ->get('/user/two-factor-recovery-codes')
            ->json();

        // Codes should be different
        $this->assertNotEquals($originalCodes, $newCodes);
    }

    public function test_user_can_disable_2fa(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');

        // Confirm 2FA
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Verify 2FA is enabled
        $this->assertNotNull($user->two_factor_secret);

        // Disable 2FA
        $response = $this->actingAs($user)
            ->delete('/user/two-factor-authentication');

        $response->assertStatus(200);

        // Refresh user
        $user = $user->fresh();

        // Check that 2FA is disabled
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_login_requires_2fa_code_when_2fa_is_enabled(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Enable and confirm 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Logout
        $this->post('/logout');

        // Try to login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Should be redirected to 2FA challenge
        $response->assertRedirect('/two-factor-challenge');
    }

    public function test_can_login_with_valid_2fa_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Enable and confirm 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Get the secret
        $secret = decrypt($user->two_factor_secret);

        // Generate valid OTP
        $google2fa = new Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        // Logout
        $this->post('/logout');

        // Login with credentials
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Submit 2FA code
        $response = $this->post('/two-factor-challenge', [
            'code' => $validCode,
        ]);

        // Should be redirected to dashboard
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_can_login_with_recovery_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Enable and confirm 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Get recovery codes
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $recoveryCode = $recoveryCodes[0];

        // Logout
        $this->post('/logout');

        // Login with credentials
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Submit recovery code
        $response = $this->post('/two-factor-challenge', [
            'recovery_code' => $recoveryCode,
        ]);

        // Should be redirected to dashboard
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Recovery code should be marked as used
        $user = $user->fresh();
        $newRecoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $this->assertNotContains($recoveryCode, $newRecoveryCodes);
    }

    public function test_cannot_login_with_invalid_2fa_code(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Enable and confirm 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Logout
        $this->post('/logout');

        // Login with credentials
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Submit invalid code
        $response = $this->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        // Should have validation error
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_unverified_users_cannot_enable_2fa(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null, // Not verified
        ]);

        // Try to enable 2FA
        $response = $this->actingAs($user)
            ->post('/user/two-factor-authentication');

        // Should be redirected to verification notice
        $response->assertRedirect('/verify-email');
    }

    public function test_2fa_status_is_shown_correctly_in_profile(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Initially, 2FA should be disabled
        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);

        // Enable 2FA
        $this->actingAs($user)->post('/user/two-factor-authentication');
        $user = $user->fresh();
        $user->two_factor_confirmed_at = now();
        $user->save();

        // 2FA should now be enabled
        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
    }
}
