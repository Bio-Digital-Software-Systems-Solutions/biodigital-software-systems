<?php

use App\Models\ProfileSkill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a profile skill with uuid', function (): void {
    $skill = ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);

    expect($skill)->toBeInstanceOf(ProfileSkill::class)
        ->and($skill->uuid)->not->toBeNull()
        ->and($skill->name)->toBe('PHP')
        ->and($skill->category)->toBe('technical');
});

it('uses the profile_skills table', function (): void {
    $skill = new ProfileSkill;

    expect($skill->getTable())->toBe('profile_skills');
});

it('has users relationship', function (): void {
    $skill = ProfileSkill::factory()->create();
    $user = User::factory()->create();

    $skill->users()->attach($user->id, ['level' => 'advanced']);

    expect($skill->users)->toHaveCount(1)
        ->and($skill->users->first()->id)->toBe($user->id)
        ->and($skill->users->first()->pivot->level)->toBe('advanced');
});

it('can scope by category', function (): void {
    ProfileSkill::factory()->create(['name' => 'Communication', 'category' => 'soft']);
    ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);
    ProfileSkill::factory()->create(['name' => 'Leadership', 'category' => 'soft']);

    expect(ProfileSkill::soft()->count())->toBe(2)
        ->and(ProfileSkill::technical()->count())->toBe(1);
});

it('can scope by hard skills', function (): void {
    ProfileSkill::factory()->create(['name' => 'Project Management', 'category' => 'hard']);
    ProfileSkill::factory()->create(['name' => 'Data Analysis', 'category' => 'hard']);
    ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);

    expect(ProfileSkill::hard()->count())->toBe(2);
});

it('uses uuid as route key', function (): void {
    $skill = ProfileSkill::factory()->create();

    expect($skill->getRouteKeyName())->toBe('uuid');
});

it('has unique constraint on name and category combination', function (): void {
    ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'technical']);

    // Same name, different category should work
    $skill = ProfileSkill::factory()->create(['name' => 'PHP', 'category' => 'hard']);
    expect($skill)->toBeInstanceOf(ProfileSkill::class);
});
