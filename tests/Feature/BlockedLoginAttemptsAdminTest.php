<?php

use App\Enums\Role;
use App\Models\BlockedLoginAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create super-admin role if it doesn't exist
    SpatieRole::findOrCreate(Role::SUPER_ADMIN->value, 'web');

    // Create super-admin user
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(Role::SUPER_ADMIN->value);

    // Create regular user
    $this->regularUser = User::factory()->create();
});

describe('Blocked Login Attempts List', function () {
    it('allows super-admin to view blocked login attempts', function () {
        $blockedUser = User::factory()->blocked()->create();
        BlockedLoginAttempt::factory()->count(5)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.blocked-login-attempts'));

        $response->assertOk();
        $response->assertJsonStructure([
            'attempts' => [
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'email',
                        'ip_address',
                        'user_agent',
                        'acknowledged',
                        'created_at',
                        'user',
                    ],
                ],
                'current_page',
                'last_page',
                'total',
            ],
            'unacknowledged_count',
        ]);
    });

    it('denies access to non-super-admin users', function () {
        $response = $this->actingAs($this->regularUser)
            ->getJson(route('user-management.blocked-login-attempts'));

        $response->assertForbidden();
    });

    it('filters by acknowledged status', function () {
        $blockedUser = User::factory()->blocked()->create();

        // Create acknowledged attempts
        BlockedLoginAttempt::factory()->count(3)->acknowledged()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        // Create unacknowledged attempts
        BlockedLoginAttempt::factory()->count(2)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        // Get only unacknowledged
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.blocked-login-attempts', ['acknowledged' => 'false']));

        $response->assertOk();
        expect($response->json('attempts.total'))->toBe(2);

        // Get all
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.blocked-login-attempts'));

        $response->assertOk();
        expect($response->json('attempts.total'))->toBe(5);
    });

    it('filters by user_id', function () {
        $blockedUser1 = User::factory()->blocked()->create();
        $blockedUser2 = User::factory()->blocked()->create();

        BlockedLoginAttempt::factory()->count(3)->create([
            'user_id' => $blockedUser1->id,
            'email' => $blockedUser1->email,
        ]);

        BlockedLoginAttempt::factory()->count(2)->create([
            'user_id' => $blockedUser2->id,
            'email' => $blockedUser2->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.blocked-login-attempts', ['user_id' => $blockedUser1->id]));

        $response->assertOk();
        expect($response->json('attempts.total'))->toBe(3);
    });

    it('returns unacknowledged count', function () {
        $blockedUser = User::factory()->blocked()->create();

        BlockedLoginAttempt::factory()->count(3)->acknowledged()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        BlockedLoginAttempt::factory()->count(5)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.blocked-login-attempts'));

        $response->assertOk();
        expect($response->json('unacknowledged_count'))->toBe(5);
    });
});

describe('Acknowledge Single Attempt', function () {
    it('allows super-admin to acknowledge an attempt', function () {
        $blockedUser = User::factory()->blocked()->create();
        $attempt = BlockedLoginAttempt::factory()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.acknowledge-blocked-attempt', ['attempt' => $attempt->id]));

        $response->assertOk();
        $response->assertJson(['message' => 'Attempt acknowledged successfully']);

        $attempt->refresh();
        expect($attempt->acknowledged)->toBeTrue();
        expect($attempt->acknowledged_by)->toBe($this->superAdmin->id);
        expect($attempt->acknowledged_at)->not->toBeNull();
    });

    it('returns error when attempt already acknowledged', function () {
        $blockedUser = User::factory()->blocked()->create();
        $attempt = BlockedLoginAttempt::factory()->acknowledged()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.acknowledge-blocked-attempt', ['attempt' => $attempt->id]));

        $response->assertStatus(422);
        $response->assertJson(['message' => 'This attempt has already been acknowledged']);
    });

    it('denies access to non-super-admin users', function () {
        $blockedUser = User::factory()->blocked()->create();
        $attempt = BlockedLoginAttempt::factory()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->postJson(route('user-management.acknowledge-blocked-attempt', ['attempt' => $attempt->id]));

        $response->assertForbidden();
    });
});

describe('Acknowledge Multiple Attempts', function () {
    it('allows super-admin to acknowledge multiple attempts', function () {
        $blockedUser = User::factory()->blocked()->create();
        $attempts = BlockedLoginAttempt::factory()->count(3)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.acknowledge-multiple-blocked-attempts'), [
                'attempt_ids' => $attempts->pluck('id')->toArray(),
            ]);

        $response->assertOk();
        $response->assertJson([
            'message' => '3 attempts acknowledged successfully',
            'acknowledged_count' => 3,
        ]);

        foreach ($attempts as $attempt) {
            $attempt->refresh();
            expect($attempt->acknowledged)->toBeTrue();
        }
    });

    it('skips already acknowledged attempts', function () {
        $blockedUser = User::factory()->blocked()->create();

        $unacknowledged = BlockedLoginAttempt::factory()->count(2)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $acknowledged = BlockedLoginAttempt::factory()->acknowledged()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $allIds = $unacknowledged->pluck('id')->push($acknowledged->id)->toArray();

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.acknowledge-multiple-blocked-attempts'), [
                'attempt_ids' => $allIds,
            ]);

        $response->assertOk();
        $response->assertJson([
            'acknowledged_count' => 2, // Only 2 were actually acknowledged
        ]);
    });

    it('validates attempt_ids is required', function () {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.acknowledge-multiple-blocked-attempts'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attempt_ids']);
    });

    it('validates attempt_ids exist', function () {
        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('user-management.acknowledge-multiple-blocked-attempts'), [
                'attempt_ids' => [99999, 99998],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attempt_ids.0', 'attempt_ids.1']);
    });
});

describe('User Blocked Attempts', function () {
    it('returns blocked attempts for a specific user', function () {
        $blockedUser = User::factory()->blocked()->create();
        $otherUser = User::factory()->blocked()->create();

        BlockedLoginAttempt::factory()->count(5)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        BlockedLoginAttempt::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.user-blocked-attempts', ['user' => $blockedUser->uuid]));

        $response->assertOk();
        $response->assertJsonStructure([
            'attempts',
            'total_count',
            'unacknowledged_count',
        ]);
        expect($response->json('total_count'))->toBe(5);
        expect(count($response->json('attempts')))->toBe(5);
    });

    it('returns unacknowledged count for user', function () {
        $blockedUser = User::factory()->blocked()->create();

        BlockedLoginAttempt::factory()->count(3)->acknowledged()->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        BlockedLoginAttempt::factory()->count(2)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('user-management.user-blocked-attempts', ['user' => $blockedUser->uuid]));

        $response->assertOk();
        expect($response->json('unacknowledged_count'))->toBe(2);
    });
});

describe('User Management Index', function () {
    it('includes unacknowledged blocked attempts count', function () {
        $blockedUser = User::factory()->blocked()->create();

        BlockedLoginAttempt::factory()->count(5)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('user-management.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('unacknowledgedBlockedAttempts')
            ->where('unacknowledgedBlockedAttempts', 5)
        );
    });
});

describe('User Show Page', function () {
    it('includes blocked attempts for blocked user', function () {
        $blockedUser = User::factory()->blocked()->create();

        BlockedLoginAttempt::factory()->count(3)->create([
            'user_id' => $blockedUser->id,
            'email' => $blockedUser->email,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('user-management.show', ['user' => $blockedUser->uuid]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('blockedAttempts', 3)
            ->where('blockedAttemptsCount', 3)
        );
    });

    it('returns empty attempts for non-blocked user', function () {
        $normalUser = User::factory()->create();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('user-management.show', ['user' => $normalUser->uuid]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('blockedAttempts', 0)
            ->where('blockedAttemptsCount', 0)
        );
    });
});
