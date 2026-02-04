<?php

use App\Models\SpokenLanguage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a spoken language with uuid', function () {
    $language = SpokenLanguage::factory()->french()->create();

    expect($language)->toBeInstanceOf(SpokenLanguage::class)
        ->and($language->uuid)->not->toBeNull()
        ->and($language->name)->toBe('French')
        ->and($language->code)->toBe('fr')
        ->and($language->native_name)->toBe('Français');
});

it('has users relationship with level pivot', function () {
    $language = SpokenLanguage::factory()->english()->create();
    $user = User::factory()->create();

    $language->users()->attach($user->id, ['level' => 'native']);

    expect($language->users)->toHaveCount(1)
        ->and($language->users->first()->id)->toBe($user->id)
        ->and($language->users->first()->pivot->level)->toBe('native');
});

it('uses uuid as route key', function () {
    $language = SpokenLanguage::factory()->create();

    expect($language->getRouteKeyName())->toBe('uuid');
});

it('has unique name constraint', function () {
    SpokenLanguage::factory()->create(['name' => 'Spanish', 'code' => 'es']);

    $this->expectException(\Illuminate\Database\QueryException::class);

    SpokenLanguage::factory()->create(['name' => 'Spanish', 'code' => 'es2']);
});

it('has unique code constraint', function () {
    SpokenLanguage::factory()->create(['name' => 'Portuguese', 'code' => 'pt']);

    $this->expectException(\Illuminate\Database\QueryException::class);

    SpokenLanguage::factory()->create(['name' => 'Brazilian Portuguese', 'code' => 'pt']);
});

it('supports different language levels', function () {
    $language = SpokenLanguage::factory()->german()->create();
    $users = User::factory()->count(4)->create();

    $levels = ['beginner', 'intermediate', 'advanced', 'native'];
    foreach ($users as $index => $user) {
        $language->users()->attach($user->id, ['level' => $levels[$index]]);
    }

    $language->refresh();
    $attachedUsers = $language->users()->get();

    expect($attachedUsers)->toHaveCount(4);
    expect($attachedUsers->pluck('pivot.level')->toArray())
        ->toBe(['beginner', 'intermediate', 'advanced', 'native']);
});

it('can have optional native name', function () {
    $language = SpokenLanguage::factory()->create([
        'name' => 'Esperanto',
        'code' => 'eo',
        'native_name' => null,
    ]);

    expect($language->native_name)->toBeNull();
});
