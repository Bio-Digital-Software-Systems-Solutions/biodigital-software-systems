<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated user can access settings page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('settings.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->has('settings')
    );
});

test('unauthenticated user cannot access settings page', function () {
    $response = $this->get(route('settings.index'));

    $response->assertRedirect(route('login'));
});

test('settings page displays user settings', function () {
    $response = $this->actingAs($this->user)
        ->get(route('settings.index'));

    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->has('settings', fn ($settings) => $settings
            ->has('email_notifications')
            ->has('sms_notifications')
            ->has('push_notifications')
            ->has('newsletter')
            ->has('event_reminders')
            ->has('training_updates')
            ->has('message_notifications')
        )
    );
});

test('user can update settings', function () {
    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => false,
            'sms_notifications' => true,
            'push_notifications' => true,
            'newsletter' => true,
            'event_reminders' => false,
            'training_updates' => true,
            'message_notifications' => false,
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('message', 'Settings updated successfully.');

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'email_notifications' => false,
        'sms_notifications' => true,
        'push_notifications' => true,
        'newsletter' => true,
        'event_reminders' => false,
        'training_updates' => true,
        'message_notifications' => false,
    ]);
});

test('user can update individual settings', function () {
    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => false,
        ]);

    $response->assertRedirect();

    $this->user->refresh();
    expect($this->user->email_notifications)->toBeFalse();
});

test('settings update validates boolean fields', function () {
    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => 'invalid',
        ]);

    $response->assertSessionHasErrors('email_notifications');
});

test('unauthenticated user cannot update settings', function () {
    $response = $this->post(route('settings.update'), [
        'email_notifications' => false,
    ]);

    $response->assertRedirect(route('login'));
});

test('settings page includes auth user data', function () {
    $response = $this->actingAs($this->user)
        ->get(route('settings.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('auth.user', fn ($user) => $user
            ->where('id', $this->user->id)
            ->where('email', $this->user->email)
            ->etc()
        )
    );
});
