<?php

use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Models\Agile\TestScenario;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('records a passed scenario run with executor and timestamp', function (): void {
    $pm = asRole('project-manager');
    $scenario = TestScenario::factory()->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.test-scenarios.record', $scenario), ['status' => 'passed'])
        ->assertOk()
        ->assertJsonPath('data.execution_status', TestScenarioExecutionStatus::PASSED->value);

    expect($scenario->fresh())
        ->execution_status->toBe(TestScenarioExecutionStatus::PASSED)
        ->last_executed_by->toBe($pm->id)
        ->last_executed_at->not->toBeNull();
});

it('requires failure_notes when status is failed (422)', function (): void {
    $pm = asRole('project-manager');
    $scenario = TestScenario::factory()->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.test-scenarios.record', $scenario), ['status' => 'failed'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['failure_notes']);
});

it('records a failed run when notes are provided', function (): void {
    $pm = asRole('project-manager');
    $scenario = TestScenario::factory()->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.test-scenarios.record', $scenario), [
            'status' => 'failed',
            'failure_notes' => 'Timeout on step 3.',
        ])
        ->assertOk();

    expect($scenario->fresh())
        ->execution_status->toBe(TestScenarioExecutionStatus::FAILED)
        ->failure_notes->toBe('Timeout on step 3.');
});

it('denies a member from recording a run (403)', function (): void {
    $member = asRole('member');
    $scenario = TestScenario::factory()->create();

    $this->actingAs($member)
        ->postJson(route('api.agile.test-scenarios.record', $scenario), ['status' => 'passed'])
        ->assertStatus(403);
});
