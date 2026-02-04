<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects unauthenticated users to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('profile.public', $user));

    $response->assertRedirect(route('login'));
});

it('allows authenticated users to view public profile with default privacy settings', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '+1234567890',
        'address' => '123 Main St',
    ]);

    $response = $this->actingAs($viewer)->get(route('profile.public', $profileUser));

    // By default, privacy settings are all public, so fields should be present
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Public')
            ->has('user', fn (Assert $user) => $user
                ->has('id')
                ->has('name')
                ->has('first_name')
                ->has('last_name')
                ->has('avatar')
                ->has('created_at')
                ->has('email')
                ->has('phone_number')
                ->has('address')
                ->etc()
            )
        );
});

it('hides fields when privacy settings are set to private', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'phone_number' => '+9876543210',
        'address' => '456 Oak Ave',
        'privacy_settings' => [
            'email' => false,
            'phone_number' => false,
            'address' => false,
        ],
    ]);

    $response = $this->actingAs($viewer)->get(route('profile.public', $profileUser));

    // With private settings, these fields should not be present
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Public')
            ->has('user', fn (Assert $user) => $user
                ->has('id')
                ->has('name')
                ->has('first_name')
                ->has('last_name')
                ->has('avatar')
                ->has('created_at')
                ->missing('email')
                ->missing('phone_number')
                ->missing('address')
                ->etc()
            )
        );
});

it('returns 404 for non-existent user', function () {
    $viewer = User::factory()->create();

    $response = $this->actingAs($viewer)->get('/profile/00000000-0000-0000-0000-000000000000');

    $response->assertNotFound();
});
