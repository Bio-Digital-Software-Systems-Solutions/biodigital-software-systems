<?php

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\Notification;

describe('Email Two Factor Authentication', function (): void {
    describe('Enable/Disable Email 2FA', function (): void {
        it('allows authenticated user to enable email 2FA', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
            ]);

            $response = $this->actingAs($user)
                ->postJson(route('email-two-factor.enable'));

            $response->assertOk()
                ->assertJson([
                    'email_two_factor_enabled' => true,
                ]);

            $user->refresh();
            expect($user->email_two_factor_enabled)->toBeTrue()
                ->and($user->preferred_two_factor_method)->toBe('email');
        });

        it('allows authenticated user to disable email 2FA', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
                'preferred_two_factor_method' => 'email',
            ]);

            $response = $this->actingAs($user)
                ->deleteJson(route('email-two-factor.disable'));

            $response->assertOk()
                ->assertJson([
                    'email_two_factor_enabled' => false,
                ]);

            $user->refresh();
            expect($user->email_two_factor_enabled)->toBeFalse()
                ->and($user->email_two_factor_code)->toBeNull()
                ->and($user->email_two_factor_expires_at)->toBeNull();
        });

        it('requires authentication to enable email 2FA', function (): void {
            $response = $this->postJson(route('email-two-factor.enable'));

            $response->assertUnauthorized();
        });

        it('requires authentication to disable email 2FA', function (): void {
            $response = $this->deleteJson(route('email-two-factor.disable'));

            $response->assertUnauthorized();
        });
    });

    describe('Get 2FA Status', function (): void {
        it('returns correct 2FA status for user with email 2FA only', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'two_factor_confirmed_at' => null,
                'preferred_two_factor_method' => 'email',
            ]);

            $response = $this->actingAs($user)
                ->getJson(route('email-two-factor.status'));

            $response->assertOk()
                ->assertJson([
                    'totp_enabled' => false,
                    'email_enabled' => true,
                    'preferred_method' => 'email',
                    'has_any_2fa' => true,
                ]);
        });

        it('returns correct 2FA status for user with TOTP only', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
                'two_factor_confirmed_at' => now(),
                'preferred_two_factor_method' => 'totp',
            ]);

            $response = $this->actingAs($user)
                ->getJson(route('email-two-factor.status'));

            $response->assertOk()
                ->assertJson([
                    'totp_enabled' => true,
                    'email_enabled' => false,
                    'preferred_method' => 'totp',
                    'has_any_2fa' => true,
                ]);
        });

        it('returns correct 2FA status for user with both methods', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
                'preferred_two_factor_method' => 'email',
            ]);

            $response = $this->actingAs($user)
                ->getJson(route('email-two-factor.status'));

            $response->assertOk()
                ->assertJson([
                    'totp_enabled' => true,
                    'email_enabled' => true,
                    'preferred_method' => 'email',
                    'has_any_2fa' => true,
                ]);
        });

        it('returns correct 2FA status for user with no 2FA', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
                'two_factor_confirmed_at' => null,
                'preferred_two_factor_method' => null,
            ]);

            $response = $this->actingAs($user)
                ->getJson(route('email-two-factor.status'));

            $response->assertOk()
                ->assertJson([
                    'totp_enabled' => false,
                    'email_enabled' => false,
                    'preferred_method' => null,
                    'has_any_2fa' => false,
                ]);
        });
    });

    describe('Set Preferred Method', function (): void {
        it('allows setting email as preferred method when enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'preferred_two_factor_method' => null,
            ]);

            $response = $this->actingAs($user)
                ->postJson(route('email-two-factor.preferred-method'), [
                    'method' => 'email',
                ]);

            $response->assertOk()
                ->assertJson([
                    'preferred_method' => 'email',
                ]);

            $user->refresh();
            expect($user->preferred_two_factor_method)->toBe('email');
        });

        it('allows setting totp as preferred method when enabled', function (): void {
            $user = User::factory()->create([
                'two_factor_confirmed_at' => now(),
                'preferred_two_factor_method' => null,
            ]);

            $response = $this->actingAs($user)
                ->postJson(route('email-two-factor.preferred-method'), [
                    'method' => 'totp',
                ]);

            $response->assertOk()
                ->assertJson([
                    'preferred_method' => 'totp',
                ]);

            $user->refresh();
            expect($user->preferred_two_factor_method)->toBe('totp');
        });

        it('rejects setting method that is not enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
                'two_factor_confirmed_at' => null,
            ]);

            $response = $this->actingAs($user)
                ->postJson(route('email-two-factor.preferred-method'), [
                    'method' => 'email',
                ]);

            $response->assertStatus(400);
        });

        it('validates method parameter', function (): void {
            $user = User::factory()->create();

            $response = $this->actingAs($user)
                ->postJson(route('email-two-factor.preferred-method'), [
                    'method' => 'invalid',
                ]);

            $response->assertUnprocessable();
        });
    });

    describe('Send Email Code During 2FA Challenge', function (): void {
        it('sends code when user has valid login session', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
            ]);

            // Simulate the login session state
            $this->withSession([
                'login.id' => $user->id,
                'login.remember' => false,
            ]);

            $response = $this->postJson(route('two-factor.email.send'));

            $response->assertOk()
                ->assertJson([
                    'expires_in_minutes' => 10,
                    'can_resend' => false,
                ]);

            Notification::assertSentTo($user, TwoFactorCodeNotification::class);

            $user->refresh();
            expect($user->email_two_factor_code)->not->toBeNull()
                ->and(strlen((string) $user->email_two_factor_code))->toBe(8);
        });

        it('returns error when login session is invalid', function (): void {
            $response = $this->postJson(route('two-factor.email.send'));

            $response->assertUnauthorized();
        });

        it('returns error when user does not have email 2FA enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
            ]);

            $this->withSession([
                'login.id' => $user->id,
            ]);

            $response = $this->postJson(route('two-factor.email.send'));

            $response->assertStatus(400);
        });

        it('informs when code already sent and not expired', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            $this->withSession([
                'login.id' => $user->id,
            ]);

            $response = $this->postJson(route('two-factor.email.send'));

            $response->assertOk()
                ->assertJsonStructure([
                    'message',
                    'remaining_seconds',
                    'can_resend',
                ]);

            // No new notification should be sent
            Notification::assertNothingSent();
        });
    });

    describe('Resend Email Code', function (): void {
        it('resends code even if previous code exists', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            $this->withSession([
                'login.id' => $user->id,
            ]);

            $response = $this->postJson(route('two-factor.email.resend'));

            $response->assertOk();

            Notification::assertSentTo($user, TwoFactorCodeNotification::class);

            $user->refresh();
            expect($user->email_two_factor_code)->not->toBe('12345678');
        });
    });

    describe('Verify Email Code', function (): void {
        it('logs in user with valid code', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            $this->withSession([
                'login.id' => $user->id,
                'login.remember' => false,
            ]);

            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => '12345678',
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);

            // Code should be cleared
            $user->refresh();
            expect($user->email_two_factor_code)->toBeNull();
        });

        it('rejects invalid code', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            $this->withSession([
                'login.id' => $user->id,
            ]);

            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => '00000000',
            ]);

            $response->assertSessionHasErrors('email_code');
            $this->assertGuest();
        });

        it('rejects expired code', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->subMinutes(15),
            ]);

            $this->withSession([
                'login.id' => $user->id,
            ]);

            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => '12345678',
            ]);

            $response->assertSessionHasErrors('email_code');
            $this->assertGuest();
        });

        it('validates code format', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
            ]);

            $this->withSession([
                'login.id' => $user->id,
            ]);

            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => '123', // Too short
            ]);

            $response->assertSessionHasErrors('email_code');
        });

        it('rejects when no login session exists', function (): void {
            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => '12345678',
            ]);

            $response->assertSessionHasErrors('email_code');
        });
    });

    describe('Two Factor Challenge View', function (): void {
        it('shows 2FA challenge when user has TOTP enabled via login flow', function (): void {
            $password = 'password123';
            $user = User::factory()->create([
                'password' => bcrypt($password),
                'two_factor_secret' => encrypt('TESTSECRET'),
                'two_factor_confirmed_at' => now(),
                'email_two_factor_enabled' => true,
                'preferred_two_factor_method' => 'email',
            ]);

            // Login to trigger the 2FA challenge
            $this->post('/login', [
                'email' => $user->email,
                'password' => $password,
            ]);

            // Now access the challenge page
            $response = $this->get('/two-factor-challenge');

            $response->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Auth/TwoFactorChallenge')
                    ->has('totpEnabled')
                    ->has('emailEnabled')
                    ->has('preferredMethod')
                );
        });
    });

    describe('Integration: Email 2FA Used in Challenge Phase', function (): void {
        it('allows user to use email code after entering 2FA challenge', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            // Simulate being in the 2FA challenge phase
            $this->withSession([
                'login.id' => $user->id,
                'login.remember' => false,
            ]);

            // User is now in the challenge phase and can verify with email code
            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => '12345678',
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);
        });

        it('can request and verify email code during 2FA challenge', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
            ]);

            // Simulate being in the 2FA challenge phase
            $this->withSession([
                'login.id' => $user->id,
                'login.remember' => false,
            ]);

            // Step 1: Request email code
            $response = $this->postJson(route('two-factor.email.send'));
            $response->assertOk();

            Notification::assertSentTo($user, TwoFactorCodeNotification::class);

            // Step 2: Get the code from the database
            $user->refresh();
            $code = $user->email_two_factor_code;
            expect($code)->not->toBeNull()
                ->and(strlen($code))->toBe(8);

            // Step 3: Verify with the code
            $response = $this->post(route('two-factor.email.verify'), [
                'email_code' => $code,
            ]);

            $response->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($user);
        });
    });
});
