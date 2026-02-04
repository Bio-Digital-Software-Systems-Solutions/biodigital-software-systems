<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Support\Facades\Log;

class EmailTwoFactorService
{
    /**
     * Code length (8 digits as requested)
     */
    public const CODE_LENGTH = 8;

    /**
     * Code expiration time in minutes
     */
    public const CODE_EXPIRATION_MINUTES = 10;

    /**
     * Generate a random 8-digit code
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(0, 99999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Generate and send a 2FA code to the user's email
     */
    public function sendCode(User $user): bool
    {
        try {
            $code = $this->generateCode();
            $expiresAt = now()->addMinutes(self::CODE_EXPIRATION_MINUTES);

            // Store the code in the database
            $user->update([
                'email_two_factor_code' => $code,
                'email_two_factor_expires_at' => $expiresAt,
            ]);

            // Send the notification
            $user->notify(new TwoFactorCodeNotification($code, self::CODE_EXPIRATION_MINUTES));

            Log::info('2FA email code sent successfully', [
                'user_id' => $user->id,
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA email code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify a 2FA code
     */
    public function verifyCode(User $user, string $code): bool
    {
        // Check if the code matches
        if ($user->email_two_factor_code !== $code) {
            Log::warning('Invalid 2FA code attempt', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Check if the code has expired
        if ($this->isCodeExpired($user)) {
            Log::warning('Expired 2FA code attempt', [
                'user_id' => $user->id,
                'expired_at' => $user->email_two_factor_expires_at?->toDateTimeString(),
            ]);

            return false;
        }

        // Clear the code after successful verification
        $this->clearCode($user);

        Log::info('2FA code verified successfully', [
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Check if the current code has expired
     */
    public function isCodeExpired(User $user): bool
    {
        if (! $user->email_two_factor_expires_at) {
            return true;
        }

        return $user->email_two_factor_expires_at->isPast();
    }

    /**
     * Check if the user has a pending code
     */
    public function hasPendingCode(User $user): bool
    {
        return ! empty($user->email_two_factor_code) && ! $this->isCodeExpired($user);
    }

    /**
     * Clear the 2FA code
     */
    public function clearCode(User $user): void
    {
        $user->update([
            'email_two_factor_code' => null,
            'email_two_factor_expires_at' => null,
        ]);
    }

    /**
     * Enable email 2FA for a user
     */
    public function enable(User $user): bool
    {
        try {
            $user->update([
                'email_two_factor_enabled' => true,
                'preferred_two_factor_method' => 'email',
            ]);

            Log::info('Email 2FA enabled for user', [
                'user_id' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable email 2FA', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Disable email 2FA for a user
     */
    public function disable(User $user): bool
    {
        try {
            $user->update([
                'email_two_factor_enabled' => false,
                'email_two_factor_code' => null,
                'email_two_factor_expires_at' => null,
            ]);

            // If no TOTP enabled either, clear preferred method
            if (! $user->two_factor_confirmed_at) {
                $user->update(['preferred_two_factor_method' => null]);
            }

            Log::info('Email 2FA disabled for user', [
                'user_id' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable email 2FA', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if the user has any form of 2FA enabled
     */
    public function hasAnyTwoFactorEnabled(User $user): bool
    {
        return $user->email_two_factor_enabled || $user->two_factor_confirmed_at;
    }

    /**
     * Get the user's preferred 2FA method
     */
    public function getPreferredMethod(User $user): ?string
    {
        if ($user->preferred_two_factor_method) {
            return $user->preferred_two_factor_method;
        }

        // Default to TOTP if enabled
        if ($user->two_factor_confirmed_at) {
            return 'totp';
        }

        // Fall back to email if enabled
        if ($user->email_two_factor_enabled) {
            return 'email';
        }

        return null;
    }

    /**
     * Set the user's preferred 2FA method
     */
    public function setPreferredMethod(User $user, string $method): bool
    {
        if (! in_array($method, ['totp', 'email'])) {
            return false;
        }

        // Verify the method is enabled before setting as preferred
        if ($method === 'totp' && ! $user->two_factor_confirmed_at) {
            return false;
        }

        if ($method === 'email' && ! $user->email_two_factor_enabled) {
            return false;
        }

        $user->update(['preferred_two_factor_method' => $method]);

        return true;
    }

    /**
     * Get remaining time for the current code in seconds
     */
    public function getRemainingTime(User $user): int
    {
        if (! $user->email_two_factor_expires_at || $this->isCodeExpired($user)) {
            return 0;
        }

        return (int) now()->diffInSeconds($user->email_two_factor_expires_at, false);
    }
}
