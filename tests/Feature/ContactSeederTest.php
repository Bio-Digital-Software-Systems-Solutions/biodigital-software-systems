<?php

use App\Models\Contact;
use App\Models\User;
use Database\Seeders\ContactSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('creates contact messages', function (): void {
    $this->seed(ContactSeeder::class);

    expect(Contact::count())->toBeGreaterThan(0);
});

it('creates contacts with valid statuses only', function (): void {
    $this->seed(ContactSeeder::class);

    $statuses = Contact::pluck('status')->unique();

    expect($statuses->diff(['new', 'in_progress', 'resolved', 'closed']))->toBeEmpty();
});

it('marks every non-new contact as read', function (): void {
    $this->seed(ContactSeeder::class);

    expect(Contact::where('status', '!=', 'new')->whereNull('read_at')->count())->toBe(0);
});

it('assigns contacts only to users with the manage contacts permission', function (): void {
    Permission::create(['name' => 'manage contacts']);

    $handler = User::factory()->create();
    $handler->givePermissionTo('manage contacts');

    User::factory()->create();

    $this->seed(ContactSeeder::class);

    $assignedIds = Contact::whereNotNull('assigned_to')->pluck('assigned_to')->unique();

    expect($assignedIds->diff([$handler->id]))->toBeEmpty();
});

it('seeds unassigned contacts when no user has the permission', function (): void {
    User::factory()->create();

    $this->seed(ContactSeeder::class);

    expect(Contact::count())->toBeGreaterThan(0)
        ->and(Contact::whereNotNull('assigned_to')->count())->toBe(0);
});

it('spreads contacts over the last six months', function (): void {
    $this->seed(ContactSeeder::class);

    expect(Contact::where('created_at', '<', now()->subMonths(6)->startOfDay())->count())->toBe(0)
        ->and(Contact::where('created_at', '>', now())->count())->toBe(0);
});
