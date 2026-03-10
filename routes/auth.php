<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailTwoFactorController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:3,5')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    // Email 2FA management routes (for authenticated users)
    Route::prefix('user/email-two-factor')->group(function (): void {
        Route::post('/', [EmailTwoFactorController::class, 'enable'])
            ->name('email-two-factor.enable');

        Route::delete('/', [EmailTwoFactorController::class, 'disable'])
            ->name('email-two-factor.disable');

        Route::get('/status', [EmailTwoFactorController::class, 'status'])
            ->name('email-two-factor.status');

        Route::post('/preferred-method', [EmailTwoFactorController::class, 'setPreferredMethod'])
            ->name('email-two-factor.preferred-method');
    });
});

// Two-factor challenge routes (for users in 2FA challenge phase - not fully authenticated yet)
Route::middleware(['web'])->group(function (): void {
    // GET route for 2FA challenge page (Fortify doesn't register this when 'views' => false)
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])
        ->name('two-factor.login');
});

// Email 2FA challenge routes (for users in 2FA challenge phase - not fully authenticated yet)
Route::middleware(['web', 'throttle:two-factor'])->group(function (): void {
    Route::post('/two-factor-challenge/email/send', [EmailTwoFactorController::class, 'sendCode'])
        ->name('two-factor.email.send');

    Route::post('/two-factor-challenge/email/resend', [EmailTwoFactorController::class, 'resendCode'])
        ->name('two-factor.email.resend');

    Route::post('/two-factor-challenge/email/verify', [EmailTwoFactorController::class, 'verify'])
        ->name('two-factor.email.verify');
});
