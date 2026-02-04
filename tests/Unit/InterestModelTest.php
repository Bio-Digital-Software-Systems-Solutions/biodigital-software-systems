<?php

use App\Models\Interest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an interest with uuid', function () {
    $interest = Interest::factory()->create(['name' => 'Reading']);

    expect($interest)->toBeInstanceOf(Interest::class)
        ->and($interest->uuid)->not->toBeNull()
        ->and($interest->name)->toBe('Reading');
});

it('has users relationship', function () {
    $interest = Interest::factory()->create();
    $user = User::factory()->create();

    $interest->users()->attach($user->id);

    expect($interest->users)->toHaveCount(1)
        ->and($interest->users->first()->id)->toBe($user->id);
});

it('uses uuid as route key', function () {
    $interest = Interest::factory()->create();

    expect($interest->getRouteKeyName())->toBe('uuid');
});

it('can store optional icon', function () {
    $interest = Interest::factory()->create(['name' => 'Music', 'icon' => 'musical-note']);

    expect($interest->icon)->toBe('musical-note');
});

it('has unique name constraint', function () {
    Interest::factory()->create(['name' => 'Photography']);

    $this->expectException(\Illuminate\Database\QueryException::class);

    Interest::factory()->create(['name' => 'Photography']);
});

it('can be attached to multiple users', function () {
    $interest = Interest::factory()->create(['name' => 'Travel']);
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        $interest->users()->attach($user->id);
    }

    expect($interest->users)->toHaveCount(3);
});
