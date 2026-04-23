<?php

use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows product-owner to validate and reject acceptance criteria', function (): void {
    $po = asRole('product-owner');
    $ac = AcceptanceCriterion::factory()->create();

    expect($po->can('validate', $ac))->toBeTrue()
        ->and($po->can('reject', $ac))->toBeTrue();
});

it('denies project-manager from validating acceptance criteria', function (): void {
    $pm = asRole('project-manager');
    $ac = AcceptanceCriterion::factory()->create();

    expect($pm->can('view', $ac))->toBeTrue()
        ->and($pm->can('create', AcceptanceCriterion::class))->toBeTrue()
        ->and($pm->can('update', $ac))->toBeTrue()
        ->and($pm->can('validate', $ac))->toBeFalse()
        ->and($pm->can('reject', $ac))->toBeFalse();
});

it('allows the epic owner to validate criteria of stories in their epic', function (): void {
    $owner = asRole('member');
    $epic = Epic::factory()->create(['owner_id' => $owner->id]);
    $story = UserStory::factory()->for($epic)->create();
    $ac = AcceptanceCriterion::factory()->for($story, 'userStory')->create();

    expect($owner->can('validate', $ac))->toBeTrue()
        ->and($owner->can('reject', $ac))->toBeTrue();
});

it('denies member from validating unrelated criteria', function (): void {
    $member = asRole('member');
    $ac = AcceptanceCriterion::factory()->create();

    expect($member->can('view', $ac))->toBeTrue()
        ->and($member->can('validate', $ac))->toBeFalse();
});
