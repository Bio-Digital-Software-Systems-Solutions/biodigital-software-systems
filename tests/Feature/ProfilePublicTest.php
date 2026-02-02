<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects unauthenticated users to login', function () {
    $user = User::factory()->create();

    $response = $this->get(route('profile.public', $user));

    $response->assertRedirect(route('login'));
});

it('allows authenticated users to view public profile', function () {
    $viewer = User::factory()->create();
    $profileUser = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $response = $this->actingAs($viewer)->get(route('profile.public', $profileUser));

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
                ->missing('phone')
                ->missing('address')
            )
        );
});

it('returns 404 for non-existent user', function () {
    $viewer = User::factory()->create();

    $response = $this->actingAs($viewer)->get('/profile/00000000-0000-0000-0000-000000000000');

    $response->assertNotFound();
});
