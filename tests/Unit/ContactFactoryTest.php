<?php

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a contact with valid default attributes', function (): void {
    $contact = Contact::factory()->create();

    expect($contact->name)->not->toBeEmpty()
        ->and($contact->email)->toContain('@')
        ->and($contact->subject)->not->toBeEmpty()
        ->and($contact->message)->not->toBeEmpty()
        ->and($contact->status)->toBeIn(['new', 'in_progress', 'resolved', 'closed'])
        ->and($contact->assigned_to)->toBeNull();
});

it('creates an unread new contact with the unread state', function (): void {
    $contact = Contact::factory()->unread()->create();

    expect($contact->status)->toBe('new')
        ->and($contact->read_at)->toBeNull();
});

it('creates a read contact with the read state', function (): void {
    $contact = Contact::factory()->read()->create();

    expect($contact->read_at)->not->toBeNull();
});

it('creates a read in-progress contact with the inProgress state', function (): void {
    $contact = Contact::factory()->inProgress()->create();

    expect($contact->status)->toBe('in_progress')
        ->and($contact->read_at)->not->toBeNull();
});

it('creates a read resolved contact with the resolved state', function (): void {
    $contact = Contact::factory()->resolved()->create();

    expect($contact->status)->toBe('resolved')
        ->and($contact->read_at)->not->toBeNull();
});

it('creates a read closed contact with the closed state', function (): void {
    $contact = Contact::factory()->closed()->create();

    expect($contact->status)->toBe('closed')
        ->and($contact->read_at)->not->toBeNull();
});

it('assigns the contact to the given user with the assigned state', function (): void {
    $user = User::factory()->create();

    $contact = Contact::factory()->assigned($user)->create();

    expect($contact->assigned_to)->toBe($user->id)
        ->and($contact->assignedTo->is($user))->toBeTrue();
});

it('creates a user on the fly when assigning without a user', function (): void {
    $contact = Contact::factory()->assigned()->create();

    expect($contact->assigned_to)->not->toBeNull()
        ->and($contact->assignedTo)->toBeInstanceOf(User::class);
});
