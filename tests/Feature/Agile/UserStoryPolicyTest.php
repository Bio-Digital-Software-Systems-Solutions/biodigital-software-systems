<?php

use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows project-manager to CRUD stories and complete/move', function (): void {
    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();

    expect($pm->can('viewAny', UserStory::class))->toBeTrue()
        ->and($pm->can('view', $story))->toBeTrue()
        ->and($pm->can('create', UserStory::class))->toBeTrue()
        ->and($pm->can('update', $story))->toBeTrue()
        ->and($pm->can('delete', $story))->toBeTrue()
        ->and($pm->can('complete', $story))->toBeTrue()
        ->and($pm->can('moveToSprint', $story))->toBeTrue();
});

it('allows assignee to view/update their own story', function (): void {
    $assignee = asRole('member');
    $story = UserStory::factory()->create(['assignee_id' => $assignee->id]);

    expect($assignee->can('view', $story))->toBeTrue()
        ->and($assignee->can('update', $story))->toBeTrue();
});

it('allows the epic owner to complete and move stories of that epic', function (): void {
    $owner = asRole('member');
    $epic = Epic::factory()->create(['owner_id' => $owner->id]);
    $story = UserStory::factory()->for($epic)->create();

    expect($owner->can('complete', $story))->toBeTrue()
        ->and($owner->can('moveToSprint', $story))->toBeTrue();
});

it('denies a plain member from completing an unrelated story', function (): void {
    $member = asRole('member');
    $story = UserStory::factory()->create();

    expect($member->can('view', $story))->toBeTrue()
        ->and($member->can('complete', $story))->toBeFalse()
        ->and($member->can('moveToSprint', $story))->toBeFalse()
        ->and($member->can('create', UserStory::class))->toBeFalse();
});
