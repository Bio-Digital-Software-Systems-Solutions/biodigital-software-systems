<?php

use App\Models\Agile\Epic;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows project-manager to CRUD epics', function (): void {
    $pm = asRole('project-manager');
    $epic = Epic::factory()->create();

    expect($pm->can('viewAny', Epic::class))->toBeTrue()
        ->and($pm->can('view', $epic))->toBeTrue()
        ->and($pm->can('create', Epic::class))->toBeTrue()
        ->and($pm->can('update', $epic))->toBeTrue()
        ->and($pm->can('delete', $epic))->toBeTrue();
});

it('allows an owner to update their own epic even without project-manager role', function (): void {
    $owner = asRole('member');
    $epic = Epic::factory()->create(['owner_id' => $owner->id]);

    expect($owner->can('view', $epic))->toBeTrue()
        ->and($owner->can('update', $epic))->toBeTrue()
        ->and($owner->can('delete', $epic))->toBeTrue();
});

it('allows member read-only access', function (): void {
    $member = asRole('member');
    $epic = Epic::factory()->create();

    expect($member->can('viewAny', Epic::class))->toBeTrue()
        ->and($member->can('view', $epic))->toBeTrue()
        ->and($member->can('create', Epic::class))->toBeFalse()
        ->and($member->can('update', $epic))->toBeFalse()
        ->and($member->can('delete', $epic))->toBeFalse();
});

it('denies epic access to an unrelated role with no agile permissions', function (): void {
    $pastor = asRole('pastor');
    $epic = Epic::factory()->create();

    expect($pastor->can('viewAny', Epic::class))->toBeFalse()
        ->and($pastor->can('view', $epic))->toBeFalse()
        ->and($pastor->can('create', Epic::class))->toBeFalse();
});
