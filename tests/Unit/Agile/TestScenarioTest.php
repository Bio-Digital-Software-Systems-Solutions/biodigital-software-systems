<?php

use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Models\Agile\TestScenario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists a Gherkin scenario with enum-cast execution status', function (): void {
    $scenario = TestScenario::factory()->gherkin()->create();

    expect($scenario->execution_status)->toBe(TestScenarioExecutionStatus::NOT_RUN)
        ->and($scenario->isGherkin())->toBeTrue()
        ->and($scenario->hasPassed())->toBeFalse();
});

it('persists a free-form scenario without Gherkin fields', function (): void {
    $scenario = TestScenario::factory()->freeForm()->create();

    expect($scenario->isGherkin())->toBeFalse()
        ->and($scenario->free_form)->not->toBeNull();
});

it('marks a passed scenario with executor and timestamp', function (): void {
    $user = User::factory()->create();
    $scenario = TestScenario::factory()->passed($user)->create();

    expect($scenario->hasPassed())->toBeTrue()
        ->and($scenario->last_executed_by)->toBe($user->id)
        ->and($scenario->last_executed_at)->not->toBeNull();
});

it('marks a failed scenario with failure notes', function (): void {
    $scenario = TestScenario::factory()->failed()->create();

    expect($scenario->execution_status)->toBe(TestScenarioExecutionStatus::FAILED)
        ->and($scenario->failure_notes)->not->toBeNull();
});

it('exposes a criterion to its test scenarios via hasPassingScenarios()', function (): void {
    $scenario = TestScenario::factory()->passed()->create();

    expect($scenario->acceptanceCriterion->hasPassingScenarios())->toBeTrue();
});
