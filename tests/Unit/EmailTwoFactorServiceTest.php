<?php

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\EmailTwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new EmailTwoFactorService;
});

describe('EmailTwoFactorService', function (): void {
    describe('generateCode', function (): void {
        it('generates an 8-digit code', function (): void {
            $code = $this->service->generateCode();

            expect($code)->toBeString()
                ->and(strlen($code))->toBe(8)
                ->and($code)->toMatch('/^\d{8}$/');
        });

        it('generates unique codes on each call', function (): void {
            $codes = [];
            for ($i = 0; $i < 100; $i++) {
                $codes[] = $this->service->generateCode();
            }

            // All codes should be unique (or at least most of them statistically)
            $uniqueCodes = array_unique($codes);
            expect(count($uniqueCodes))->toBeGreaterThan(95);
        });

        it('pads codes with leading zeros', function (): void {
            // Generate many codes and check they're all 8 digits
            for ($i = 0; $i < 50; $i++) {
                $code = $this->service->generateCode();
                expect(strlen($code))->toBe(8);
            }
        });
    });

    describe('sendCode', function (): void {
        it('sends a code to the user and stores it in the database', function (): void {
            Notification::fake();

            $user = User::factory()->create();

            $result = $this->service->sendCode($user);

            expect($result)->toBeTrue();

            $user->refresh();
            expect($user->email_two_factor_code)->not->toBeNull()
                ->and(strlen((string) $user->email_two_factor_code))->toBe(8)
                ->and($user->email_two_factor_expires_at)->not->toBeNull()
                ->and($user->email_two_factor_expires_at->isFuture())->toBeTrue();

            Notification::assertSentTo($user, TwoFactorCodeNotification::class);
        });

        it('sets expiration time to 10 minutes', function (): void {
            Notification::fake();

            $user = User::factory()->create();
            $now = now();

            $this->service->sendCode($user);

            $user->refresh();
            $expiresAt = $user->email_two_factor_expires_at;

            // Should expire in approximately 10 minutes (allow 1 minute tolerance)
            $minutesUntilExpiration = $now->diffInMinutes($expiresAt, false);
            expect($minutesUntilExpiration)->toBeGreaterThanOrEqual(9)
                ->and($minutesUntilExpiration)->toBeLessThanOrEqual(11);
        });
    });

    describe('verifyCode', function (): void {
        it('returns true for valid code', function (): void {
            Notification::fake();

            $user = User::factory()->create();
            $this->service->sendCode($user);
            $user->refresh();

            $code = $user->email_two_factor_code;
            $result = $this->service->verifyCode($user, $code);

            expect($result)->toBeTrue();
        });

        it('returns false for invalid code', function (): void {
            Notification::fake();

            $user = User::factory()->create();
            $this->service->sendCode($user);
            $user->refresh();

            $result = $this->service->verifyCode($user, '00000000');

            expect($result)->toBeFalse();
        });

        it('returns false for expired code', function (): void {
            Notification::fake();

            $user = User::factory()->create([
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->subMinutes(15),
            ]);

            $result = $this->service->verifyCode($user, '12345678');

            expect($result)->toBeFalse();
        });

        it('clears the code after successful verification', function (): void {
            Notification::fake();

            $user = User::factory()->create();
            $this->service->sendCode($user);
            $user->refresh();

            $code = $user->email_two_factor_code;
            $this->service->verifyCode($user, $code);

            $user->refresh();
            expect($user->email_two_factor_code)->toBeNull()
                ->and($user->email_two_factor_expires_at)->toBeNull();
        });

        it('does not clear the code after failed verification', function (): void {
            Notification::fake();

            $user = User::factory()->create();
            $this->service->sendCode($user);
            $user->refresh();

            $originalCode = $user->email_two_factor_code;
            $this->service->verifyCode($user, '00000000');

            $user->refresh();
            expect($user->email_two_factor_code)->toBe($originalCode);
        });
    });

    describe('isCodeExpired', function (): void {
        it('returns true when no expiration date is set', function (): void {
            $user = User::factory()->create([
                'email_two_factor_expires_at' => null,
            ]);

            expect($this->service->isCodeExpired($user))->toBeTrue();
        });

        it('returns true when code has expired', function (): void {
            $user = User::factory()->create([
                'email_two_factor_expires_at' => now()->subMinutes(1),
            ]);

            expect($this->service->isCodeExpired($user))->toBeTrue();
        });

        it('returns false when code is still valid', function (): void {
            $user = User::factory()->create([
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            expect($this->service->isCodeExpired($user))->toBeFalse();
        });
    });

    describe('hasPendingCode', function (): void {
        it('returns true when user has a valid pending code', function (): void {
            $user = User::factory()->create([
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            expect($this->service->hasPendingCode($user))->toBeTrue();
        });

        it('returns false when code is expired', function (): void {
            $user = User::factory()->create([
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->subMinutes(5),
            ]);

            expect($this->service->hasPendingCode($user))->toBeFalse();
        });

        it('returns false when no code exists', function (): void {
            $user = User::factory()->create([
                'email_two_factor_code' => null,
            ]);

            expect($this->service->hasPendingCode($user))->toBeFalse();
        });
    });

    describe('clearCode', function (): void {
        it('clears the code and expiration', function (): void {
            $user = User::factory()->create([
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            $this->service->clearCode($user);
            $user->refresh();

            expect($user->email_two_factor_code)->toBeNull()
                ->and($user->email_two_factor_expires_at)->toBeNull();
        });
    });

    describe('enable', function (): void {
        it('enables email 2FA for the user', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
            ]);

            $result = $this->service->enable($user);
            $user->refresh();

            expect($result)->toBeTrue()
                ->and($user->email_two_factor_enabled)->toBeTrue()
                ->and($user->preferred_two_factor_method)->toBe('email');
        });
    });

    describe('disable', function (): void {
        it('disables email 2FA and clears codes', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'email_two_factor_code' => '12345678',
                'email_two_factor_expires_at' => now()->addMinutes(5),
                'preferred_two_factor_method' => 'email',
            ]);

            $result = $this->service->disable($user);
            $user->refresh();

            expect($result)->toBeTrue()
                ->and($user->email_two_factor_enabled)->toBeFalse()
                ->and($user->email_two_factor_code)->toBeNull()
                ->and($user->email_two_factor_expires_at)->toBeNull();
        });

        it('clears preferred method when no TOTP enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'two_factor_confirmed_at' => null,
                'preferred_two_factor_method' => 'email',
            ]);

            $this->service->disable($user);
            $user->refresh();

            expect($user->preferred_two_factor_method)->toBeNull();
        });
    });

    describe('hasAnyTwoFactorEnabled', function (): void {
        it('returns true when email 2FA is enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'two_factor_confirmed_at' => null,
            ]);

            expect($this->service->hasAnyTwoFactorEnabled($user))->toBeTrue();
        });

        it('returns true when TOTP is enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
                'two_factor_confirmed_at' => now(),
            ]);

            expect($this->service->hasAnyTwoFactorEnabled($user))->toBeTrue();
        });

        it('returns true when both are enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
                'two_factor_confirmed_at' => now(),
            ]);

            expect($this->service->hasAnyTwoFactorEnabled($user))->toBeTrue();
        });

        it('returns false when neither is enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
                'two_factor_confirmed_at' => null,
            ]);

            expect($this->service->hasAnyTwoFactorEnabled($user))->toBeFalse();
        });
    });

    describe('getPreferredMethod', function (): void {
        it('returns saved preference when set', function (): void {
            $user = User::factory()->create([
                'preferred_two_factor_method' => 'email',
                'email_two_factor_enabled' => true,
            ]);

            expect($this->service->getPreferredMethod($user))->toBe('email');
        });

        it('defaults to totp when enabled', function (): void {
            $user = User::factory()->create([
                'preferred_two_factor_method' => null,
                'two_factor_confirmed_at' => now(),
                'email_two_factor_enabled' => false,
            ]);

            expect($this->service->getPreferredMethod($user))->toBe('totp');
        });

        it('falls back to email when only email is enabled', function (): void {
            $user = User::factory()->create([
                'preferred_two_factor_method' => null,
                'two_factor_confirmed_at' => null,
                'email_two_factor_enabled' => true,
            ]);

            expect($this->service->getPreferredMethod($user))->toBe('email');
        });

        it('returns null when no 2FA is enabled', function (): void {
            $user = User::factory()->create([
                'preferred_two_factor_method' => null,
                'two_factor_confirmed_at' => null,
                'email_two_factor_enabled' => false,
            ]);

            expect($this->service->getPreferredMethod($user))->toBeNull();
        });
    });

    describe('setPreferredMethod', function (): void {
        it('sets totp as preferred when it is enabled', function (): void {
            $user = User::factory()->create([
                'two_factor_confirmed_at' => now(),
            ]);

            $result = $this->service->setPreferredMethod($user, 'totp');
            $user->refresh();

            expect($result)->toBeTrue()
                ->and($user->preferred_two_factor_method)->toBe('totp');
        });

        it('sets email as preferred when it is enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => true,
            ]);

            $result = $this->service->setPreferredMethod($user, 'email');
            $user->refresh();

            expect($result)->toBeTrue()
                ->and($user->preferred_two_factor_method)->toBe('email');
        });

        it('returns false for invalid method', function (): void {
            $user = User::factory()->create();

            $result = $this->service->setPreferredMethod($user, 'invalid');

            expect($result)->toBeFalse();
        });

        it('returns false when method is not enabled', function (): void {
            $user = User::factory()->create([
                'email_two_factor_enabled' => false,
            ]);

            $result = $this->service->setPreferredMethod($user, 'email');

            expect($result)->toBeFalse();
        });
    });

    describe('getRemainingTime', function (): void {
        it('returns remaining seconds for valid code', function (): void {
            $user = User::factory()->create([
                'email_two_factor_expires_at' => now()->addMinutes(5),
            ]);

            $remaining = $this->service->getRemainingTime($user);

            expect($remaining)->toBeGreaterThan(290)
                ->and($remaining)->toBeLessThanOrEqual(300);
        });

        it('returns 0 for expired code', function (): void {
            $user = User::factory()->create([
                'email_two_factor_expires_at' => now()->subMinutes(1),
            ]);

            expect($this->service->getRemainingTime($user))->toBe(0);
        });

        it('returns 0 when no expiration is set', function (): void {
            $user = User::factory()->create([
                'email_two_factor_expires_at' => null,
            ]);

            expect($this->service->getRemainingTime($user))->toBe(0);
        });
    });
});
