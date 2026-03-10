<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('authenticated user can access settings page', function (): void {
    $response = $this->actingAs($this->user)
        ->get(route('settings.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Index')
        ->has('settings')
    );
});

test('unauthenticated user cannot access settings page', function (): void {
    $response = $this->get(route('settings.index'));

    $response->assertRedirect(route('login'));
});

test('settings page displays user settings', function (): void {
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

test('user can update settings', function (): void {
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

test('user can update individual settings', function (): void {
    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => false,
        ]);

    $response->assertRedirect();

    $this->user->refresh();
    expect($this->user->email_notifications)->toBeFalse();
});

test('settings update validates boolean fields', function (): void {
    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => 'invalid',
        ]);

    $response->assertSessionHasErrors('email_notifications');
});

test('unauthenticated user cannot update settings', function (): void {
    $response = $this->post(route('settings.update'), [
        'email_notifications' => false,
    ]);

    $response->assertRedirect(route('login'));
});

test('settings page includes auth user data', function (): void {
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

test('user can toggle email notifications on', function (): void {
    $this->user->update(['email_notifications' => false]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => true,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->email_notifications)->toBeTrue();
});

test('user can toggle sms notifications on', function (): void {
    $this->user->update(['sms_notifications' => false]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'sms_notifications' => true,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->sms_notifications)->toBeTrue();
});

test('user can toggle push notifications off', function (): void {
    $this->user->update(['push_notifications' => true]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'push_notifications' => false,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->push_notifications)->toBeFalse();
});

test('user can subscribe to newsletter', function (): void {
    $this->user->update(['newsletter' => false]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'newsletter' => true,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->newsletter)->toBeTrue();
});

test('user can unsubscribe from newsletter', function (): void {
    $this->user->update(['newsletter' => true]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'newsletter' => false,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->newsletter)->toBeFalse();
});

test('user can toggle event reminders', function (): void {
    $this->user->update(['event_reminders' => true]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'event_reminders' => false,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->event_reminders)->toBeFalse();
});

test('user can toggle training updates', function (): void {
    $this->user->update(['training_updates' => true]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'training_updates' => false,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->training_updates)->toBeFalse();
});

test('user can toggle message notifications', function (): void {
    $this->user->update(['message_notifications' => true]);

    $response = $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'message_notifications' => false,
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    expect($this->user->message_notifications)->toBeFalse();
});

test('settings are persisted across page reloads', function (): void {
    $this->actingAs($this->user)
        ->post(route('settings.update'), [
            'email_notifications' => false,
            'newsletter' => true,
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('settings.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('settings.email_notifications', false)
        ->where('settings.newsletter', true)
    );
});

test('multiple users can have different settings', function (): void {
    $user2 = User::factory()->create();

    $this->actingAs($this->user)
        ->post(route('settings.update'), ['email_notifications' => false]);

    $this->actingAs($user2)
        ->post(route('settings.update'), ['email_notifications' => true]);

    $this->user->refresh();
    $user2->refresh();

    expect($this->user->email_notifications)->toBeFalse();
    expect($user2->email_notifications)->toBeTrue();
});

test('all settings default to sensible values for new users', function (): void {
    $newUser = User::factory()->create();

    $response = $this->actingAs($newUser)
        ->get(route('settings.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('settings.email_notifications', true)
        ->where('settings.sms_notifications', false)
        ->where('settings.push_notifications', true)
        ->where('settings.newsletter', false)
        ->where('settings.event_reminders', true)
        ->where('settings.training_updates', true)
        ->where('settings.message_notifications', true)
    );
});
