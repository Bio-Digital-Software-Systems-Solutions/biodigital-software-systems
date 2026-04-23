<?php

use App\Enums\Agile\AcceptanceCriterionStatus;
use App\Events\Agile\AcceptanceCriterionRejected;
use App\Events\Agile\AcceptanceCriterionValidated;
use App\Exceptions\Agile\AcceptanceCriterionHasPassedTestsException;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\TestScenario;
use App\Models\Agile\UserStory;
use App\Models\User;
use App\Services\Agile\AcceptanceCriterionValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new AcceptanceCriterionValidationService;
    $this->validator = User::factory()->create();
});

it('marks a criterion as validated with actor and timestamp', function (): void {
    Event::fake([AcceptanceCriterionValidated::class, AcceptanceCriterionRejected::class]);
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->service->validate($ac, $this->validator, 'Looks good.');

    $ac->refresh();
    expect($ac->status)->toBe(AcceptanceCriterionStatus::VALIDATED)
        ->and($ac->validated_by)->toBe($this->validator->id)
        ->and($ac->validated_at)->not->toBeNull()
        ->and($ac->validation_notes)->toBe('Looks good.');

    Event::assertDispatched(AcceptanceCriterionValidated::class);
});

it('marks a criterion as rejected with mandatory notes', function (): void {
    Event::fake([AcceptanceCriterionValidated::class, AcceptanceCriterionRejected::class]);
    $ac = AcceptanceCriterion::factory()->pending()->create();

    $this->service->reject($ac, $this->validator, 'Edge case not covered.');

    $ac->refresh();
    expect($ac->status)->toBe(AcceptanceCriterionStatus::REJECTED)
        ->and($ac->validation_notes)->toBe('Edge case not covered.');

    Event::assertDispatched(AcceptanceCriterionRejected::class);
});

it('blocks deletion when a scenario has passed', function (): void {
    $ac = AcceptanceCriterion::factory()->create();
    TestScenario::factory()->for($ac, 'acceptanceCriterion')->passed()->create();

    expect(fn () => $this->service->guardDelete($ac))
        ->toThrow(AcceptanceCriterionHasPassedTestsException::class);
});

it('allows deletion when scenarios are not in passed state', function (): void {
    $ac = AcceptanceCriterion::factory()->create();
    TestScenario::factory()->for($ac, 'acceptanceCriterion')->failed()->create();

    $this->service->guardDelete($ac);

    // no exception thrown — delete is allowed
    expect(true)->toBeTrue();
});

it('reorders criteria by writing position from 1 to N', function (): void {
    $story = UserStory::factory()->create();
    $first = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(1)->create();
    $second = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(2)->create();
    $third = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(3)->create();

    $this->service->reorder($story, [$third->id, $first->id, $second->id]);

    expect($third->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2)
        ->and($second->fresh()->position)->toBe(3);
});

it('silently ignores reorder ids not belonging to the story', function (): void {
    $story = UserStory::factory()->create();
    $other = UserStory::factory()->create();
    $mine = AcceptanceCriterion::factory()->for($story, 'userStory')->atPosition(1)->create();
    $stranger = AcceptanceCriterion::factory()->for($other, 'userStory')->atPosition(1)->create();

    $this->service->reorder($story, [$stranger->id, $mine->id]);

    // $mine is at index 1 -> position 2; $stranger untouched at 1
    expect($mine->fresh()->position)->toBe(2)
        ->and($stranger->fresh()->position)->toBe(1);
});
