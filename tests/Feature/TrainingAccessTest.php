<?php

use App\Models\Training;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->training = Training::factory()->private()->create([
        'is_active' => true,
        'teacher_id' => $this->admin->id,
    ]);
});

it('loads the access page using uuid', function (): void {
    $response = $this->actingAs($this->admin)
        ->get(route('trainings.access', $this->training->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Training/Access'));
});

it('returns 404 when accessing with numeric id instead of uuid', function (): void {
    $response = $this->actingAs($this->admin)
        ->get("/trainings/{$this->training->id}/access");

    $response->assertNotFound();
});

it('grants user access to private training', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($this->admin)
        ->post(route('trainings.access.grant-users', $this->training->uuid), [
            'user_ids' => [$user->id],
        ]);

    $response->assertRedirect();
    expect($this->training->accessUsers()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('revokes user access from private training', function (): void {
    $user = User::factory()->create();
    $this->training->accessUsers()->attach($user->id, ['granted_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->delete(route('trainings.access.revoke-users', $this->training->uuid), [
            'user_ids' => [$user->id],
        ]);

    $response->assertRedirect();
    expect($this->training->accessUsers()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('grants role access to private training', function (): void {
    $role = \Spatie\Permission\Models\Role::findByName('member');

    $response = $this->actingAs($this->admin)
        ->post(route('trainings.access.grant-roles', $this->training->uuid), [
            'role_ids' => [$role->id],
        ]);

    $response->assertRedirect();
    expect($this->training->accessRoles()->where('role_id', $role->id)->exists())->toBeTrue();
});

it('revokes role access from private training', function (): void {
    $role = \Spatie\Permission\Models\Role::findByName('member');
    $this->training->accessRoles()->attach($role->id, ['granted_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)
        ->delete(route('trainings.access.revoke-roles', $this->training->uuid), [
            'role_ids' => [$role->id],
        ]);

    $response->assertRedirect();
    expect($this->training->accessRoles()->where('role_id', $role->id)->exists())->toBeFalse();
});

it('generates a share link with qr code', function (): void {
    $response = $this->actingAs($this->admin)
        ->postJson(route('trainings.generate-share-link', $this->training->uuid));

    $response->assertSuccessful();
    $response->assertJsonStructure(['url', 'token', 'expires_at', 'qr_code']);

    $this->training->refresh();
    expect($this->training->share_token)->not->toBeNull();
    expect($this->training->share_token_expires_at)->not->toBeNull();
    expect($this->training->isShareTokenValid())->toBeTrue();
});

it('revokes a share link', function (): void {
    $this->training->generateShareToken(24);

    $response = $this->actingAs($this->admin)
        ->postJson(route('trainings.revoke-share-link', $this->training->uuid));

    $response->assertSuccessful();

    $this->training->refresh();
    expect($this->training->share_token)->toBeNull();
    expect($this->training->share_token_expires_at)->toBeNull();
});

it('shows shared training page with valid token', function (): void {
    $this->training->generateShareToken(24);
    $token = $this->training->share_token;

    $response = $this->get(route('trainings.shared', $token));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Training/SharedView'));
});

it('shows expired page when token is invalid', function (): void {
    $response = $this->get(route('trainings.shared', 'invalid-token'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Training/SharedExpired'));
});

it('shows expired page when token has expired', function (): void {
    $this->training->generateShareToken(24);
    $token = $this->training->share_token;

    // Expire the token
    $this->training->update(['share_token_expires_at' => now()->subHour()]);

    $response = $this->get(route('trainings.shared', $token));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Training/SharedExpired'));
});

it('prevents member-only user from managing access', function (): void {
    $member = User::factory()->create();
    $member->assignRole('member');

    $response = $this->actingAs($member)
        ->get(route('trainings.access', $this->training->uuid));

    // restrict.member middleware redirects member-only users to user dashboard
    $response->assertRedirect(route('user.dashboard'));
});

it('includes share data in access page when token is active', function (): void {
    $this->training->generateShareToken(24);

    $response = $this->actingAs($this->admin)
        ->get(route('trainings.access', $this->training->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Training/Access')
        ->has('shareData')
        ->where('shareData.token', $this->training->share_token)
    );
});

it('passes null share data when no active token', function (): void {
    $response = $this->actingAs($this->admin)
        ->get(route('trainings.access', $this->training->uuid));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Training/Access')
        ->where('shareData', null)
    );
});
