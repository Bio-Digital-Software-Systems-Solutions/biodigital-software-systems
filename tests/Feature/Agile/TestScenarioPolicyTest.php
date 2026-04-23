<?php

use App\Models\Agile\TestScenario;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows project-manager to CRUD and record scenario runs', function (): void {
    $pm = asRole('project-manager');
    $ts = TestScenario::factory()->create();

    expect($pm->can('viewAny', TestScenario::class))->toBeTrue()
        ->and($pm->can('create', TestScenario::class))->toBeTrue()
        ->and($pm->can('update', $ts))->toBeTrue()
        ->and($pm->can('delete', $ts))->toBeTrue()
        ->and($pm->can('recordRun', $ts))->toBeTrue();
});

it('allows member to view but not record runs', function (): void {
    $member = asRole('member');
    $ts = TestScenario::factory()->create();

    expect($member->can('viewAny', TestScenario::class))->toBeTrue()
        ->and($member->can('view', $ts))->toBeTrue()
        ->and($member->can('recordRun', $ts))->toBeFalse()
        ->and($member->can('create', TestScenario::class))->toBeFalse();
});

it('denies a role without agile permissions from any action', function (): void {
    $pastor = asRole('pastor');
    $ts = TestScenario::factory()->create();

    expect($pastor->can('viewAny', TestScenario::class))->toBeFalse()
        ->and($pastor->can('view', $ts))->toBeFalse()
        ->and($pastor->can('recordRun', $ts))->toBeFalse();
});
