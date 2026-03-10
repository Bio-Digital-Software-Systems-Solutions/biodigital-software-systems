<?php

use App\Enums\Role;
use App\Models\BlockedLoginAttempt;
use App\Models\User;
use App\Notifications\BlockedLoginAttemptNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Ensure super-admin role exists for all tests (required by LoginRequest)
    SpatieRole::findOrCreate(Role::SUPER_ADMIN->value, 'web');
});

describe('Blocked User Login', function (): void {
    it('prevents blocked users from logging in', function (): void {
        $user = User::factory()->blocked()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('shows the correct error message for blocked users', function (): void {
        $user = User::factory()->blocked()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => __('auth.blocked'),
        ]);
    });

    it('logs blocked login attempts when password is correct', function (): void {
        $user = User::factory()->blocked()->create();

        $this->assertDatabaseCount('blocked_login_attempts', 0);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('blocked_login_attempts', 1);
        $this->assertDatabaseHas('blocked_login_attempts', [
            'user_id' => $user->id,
            'email' => $user->email,
            'acknowledged' => false,
        ]);
    });

    it('does not log attempts when password is incorrect for blocked user', function (): void {
        $user = User::factory()->blocked()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseCount('blocked_login_attempts', 0);
    });

    it('records IP address and user agent in blocked login attempts', function (): void {
        $user = User::factory()->blocked()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'HTTP_USER_AGENT' => 'Test Browser/1.0',
        ]);

        $attempt = BlockedLoginAttempt::first();
        expect($attempt->ip_address)->not->toBeNull();
        expect($attempt->user_agent)->toBe('Test Browser/1.0');
    });

    it('allows unblocked users to log in normally', function (): void {
        $user = User::factory()->active()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    });

    it('does not log attempts for unblocked users', function (): void {
        $user = User::factory()->active()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('blocked_login_attempts', 0);
    });

    it('allows a user to log in after being unblocked', function (): void {
        $user = User::factory()->blocked()->create();

        // First, verify they can't log in while blocked
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertGuest();

        // Unblock the user
        $user->update(['is_blocked' => false]);

        // Now they should be able to log in
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    });

    it('logs multiple blocked login attempts separately', function (): void {
        $user = User::factory()->blocked()->create();

        // First attempt
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Second attempt
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Third attempt
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('blocked_login_attempts', 3);
    });
});

describe('Blocked Login Attempts Management', function (): void {
    it('can be acknowledged by an admin', function (): void {
        $admin = User::factory()->create();
        $blockedUser = User::factory()->blocked()->create();

        $attempt = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
        ]);

        expect($attempt->acknowledged)->toBeFalse();

        $attempt->acknowledge($admin);

        $attempt->refresh();
        expect($attempt->acknowledged)->toBeTrue();
        expect($attempt->acknowledged_by)->toBe($admin->id);
        expect($attempt->acknowledged_at)->not->toBeNull();
    });

    it('can filter unacknowledged attempts', function (): void {
        $blockedUser = User::factory()->blocked()->create();
        $admin = User::factory()->create();

        // Create acknowledged attempt
        $acknowledged = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.1',
            'acknowledged' => true,
            'acknowledged_by' => $admin->id,
            'acknowledged_at' => now(),
        ]);

        // Create unacknowledged attempt
        $unacknowledged = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.2',
        ]);

        $unacknowledgedAttempts = BlockedLoginAttempt::unacknowledged()->get();

        expect($unacknowledgedAttempts)->toHaveCount(1);
        expect($unacknowledgedAttempts->first()->id)->toBe($unacknowledged->id);
    });

    it('can filter recent attempts', function (): void {
        $blockedUser = User::factory()->blocked()->create();

        // Create old attempt (31 days ago)
        $old = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.1',
        ]);
        // Use DB to update created_at timestamp
        \Illuminate\Support\Facades\DB::table('blocked_login_attempts')
            ->where('id', $old->id)
            ->update(['created_at' => now()->subDays(31)]);

        // Create recent attempt
        $recent = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.2',
        ]);

        $recentAttempts = BlockedLoginAttempt::recent()->get();

        expect($recentAttempts)->toHaveCount(1);
        expect($recentAttempts->first()->id)->toBe($recent->id);
    });

    it('has correct user relationship', function (): void {
        $blockedUser = User::factory()->blocked()->create();

        $attempt = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.1',
        ]);

        expect($attempt->user->id)->toBe($blockedUser->id);
        expect($attempt->user->full_name)->toBe($blockedUser->full_name);
    });

    it('has correct acknowledgedByUser relationship', function (): void {
        $blockedUser = User::factory()->blocked()->create();
        $admin = User::factory()->create();

        $attempt = BlockedLoginAttempt::create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
            'ip_address' => '127.0.0.1',
            'acknowledged' => true,
            'acknowledged_by' => $admin->id,
            'acknowledged_at' => now(),
        ]);

        expect($attempt->acknowledgedByUser->id)->toBe($admin->id);
    });
});

describe('BlockedLoginAttempt Factory', function (): void {
    it('creates a blocked login attempt with factory', function (): void {
        $attempt = BlockedLoginAttempt::factory()->create();

        expect($attempt->user)->not->toBeNull();
        expect($attempt->user->is_blocked)->toBeTrue();
        expect($attempt->acknowledged)->toBeFalse();
    });

    it('creates an acknowledged attempt with factory state', function (): void {
        $attempt = BlockedLoginAttempt::factory()->acknowledged()->create();

        expect($attempt->acknowledged)->toBeTrue();
        expect($attempt->acknowledged_by)->not->toBeNull();
        expect($attempt->acknowledged_at)->not->toBeNull();
    });
});

describe('User Factory Blocked State', function (): void {
    it('creates a blocked user with factory state', function (): void {
        $user = User::factory()->blocked()->create();

        expect($user->is_blocked)->toBeTrue();
        expect($user->status_reason)->not->toBeNull();
    });

    it('creates an active user with factory state', function (): void {
        $user = User::factory()->active()->create();

        expect($user->is_active)->toBeTrue();
        expect($user->is_blocked)->toBeFalse();
    });
});

describe('Blocked Login Email Notification to Admins', function (): void {
    it('sends email notification to super-admins when blocked user attempts to log in', function (): void {
        Notification::fake();

        $superAdmin1 = User::factory()->create();
        $superAdmin1->assignRole(Role::SUPER_ADMIN->value);

        $superAdmin2 = User::factory()->create();
        $superAdmin2->assignRole(Role::SUPER_ADMIN->value);

        $blockedUser = User::factory()->blocked()->create();

        $this->post('/login', [
            'email' => $blockedUser->email,
            'password' => 'password',
        ]);

        Notification::assertSentTo(
            [$superAdmin1, $superAdmin2],
            BlockedLoginAttemptNotification::class
        );
    });

    it('notification contains correct blocked user information', function (): void {
        Notification::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $blockedUser = User::factory()->blocked()->create();

        $this->post('/login', [
            'email' => $blockedUser->email,
            'password' => 'password',
        ]);

        Notification::assertSentTo(
            $superAdmin,
            BlockedLoginAttemptNotification::class,
            fn(BlockedLoginAttemptNotification $notification): bool => $notification->blockedUser->id === $blockedUser->id
                && $notification->attempt->email === $blockedUser->email
        );
    });

    it('does not send notification when password is incorrect for blocked user', function (): void {
        Notification::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $blockedUser = User::factory()->blocked()->create();

        $this->post('/login', [
            'email' => $blockedUser->email,
            'password' => 'wrong-password',
        ]);

        Notification::assertNothingSent();
    });

    it('does not send notification when non-blocked user logs in', function (): void {
        Notification::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $normalUser = User::factory()->active()->create();

        $this->post('/login', [
            'email' => $normalUser->email,
            'password' => 'password',
        ]);

        Notification::assertNothingSent();
    });

    it('does not send notification when there are no super-admins', function (): void {
        Notification::fake();

        $blockedUser = User::factory()->blocked()->create();

        $this->post('/login', [
            'email' => $blockedUser->email,
            'password' => 'password',
        ]);

        Notification::assertNothingSent();
    });

    it('sends notification for each blocked login attempt', function (): void {
        Notification::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::SUPER_ADMIN->value);

        $blockedUser = User::factory()->blocked()->create();

        // First attempt
        $this->post('/login', [
            'email' => $blockedUser->email,
            'password' => 'password',
        ]);

        // Second attempt
        $this->post('/login', [
            'email' => $blockedUser->email,
            'password' => 'password',
        ]);

        Notification::assertSentToTimes($superAdmin, BlockedLoginAttemptNotification::class, 2);
    });
});
