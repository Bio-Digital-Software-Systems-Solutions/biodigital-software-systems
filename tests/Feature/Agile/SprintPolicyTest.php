<?php

use App\Models\Project;
use App\Models\Sprint;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows project-manager to start and close sprints', function (): void {
    $pm = asRole('project-manager');
    $sprint = Sprint::factory()->create();

    expect($pm->can('viewAny', Sprint::class))->toBeTrue()
        ->and($pm->can('start', $sprint))->toBeTrue()
        ->and($pm->can('close', $sprint))->toBeTrue();
});

it('allows the project manager of the underlying project to start and close their sprints', function (): void {
    $manager = asRole('member');
    $project = Project::factory()->create(['project_manager_id' => $manager->id]);
    $sprint = Sprint::factory()->for($project)->create();

    expect($manager->can('start', $sprint))->toBeTrue()
        ->and($manager->can('close', $sprint))->toBeTrue();
});

it('denies member from starting or closing sprints they do not manage', function (): void {
    $member = asRole('member');
    $sprint = Sprint::factory()->create();

    expect($member->can('start', $sprint))->toBeFalse()
        ->and($member->can('close', $sprint))->toBeFalse();
});
