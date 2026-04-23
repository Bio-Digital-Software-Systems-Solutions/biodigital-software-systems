<?php

use App\Enums\Agile\UserStoryStatus;
use App\Events\Agile\UserStoryCompleted;
use App\Exceptions\Agile\CannotCompleteStoryException;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\UserStory;
use App\Models\User;
use App\Services\Agile\UserStoryCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new UserStoryCompletionService;
    $this->actor = User::factory()->create();
});

it('completes a story when every acceptance criterion is validated', function (): void {
    Event::fake([UserStoryCompleted::class]);

    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->count(3)->create();

    $completed = $this->service->complete($story, $this->actor);

    expect($completed->status)->toBe(UserStoryStatus::DONE)
        ->and($completed->completed_at)->not->toBeNull();

    Event::assertDispatched(UserStoryCompleted::class, fn (UserStoryCompleted $e): bool => $e->story->is($story) && $e->actor->is($this->actor)
    );
});

it('throws when a story has no acceptance criteria at all', function (): void {
    Event::fake([UserStoryCompleted::class]);
    $story = UserStory::factory()->create();

    expect(fn () => $this->service->complete($story, $this->actor))
        ->toThrow(CannotCompleteStoryException::class);

    Event::assertNotDispatched(UserStoryCompleted::class);
    $story->refresh();
    expect($story->status)->not->toBe(UserStoryStatus::DONE)
        ->and($story->completed_at)->toBeNull();
});

it('throws when one or more criteria are still pending', function (): void {
    Event::fake([UserStoryCompleted::class]);
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->pending()->create();

    try {
        $this->service->complete($story, $this->actor);
        expect(false)->toBeTrue('expected exception was not thrown');
    } catch (CannotCompleteStoryException $e) {
        expect($e->pendingCriteriaCount)->toBe(1)
            ->and($e->story->is($story))->toBeTrue();
    }

    Event::assertNotDispatched(UserStoryCompleted::class);
});

it('throws when a criterion is rejected', function (): void {
    Event::fake([UserStoryCompleted::class]);
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->count(2)->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->rejected()->create();

    expect(fn () => $this->service->complete($story, $this->actor))
        ->toThrow(CannotCompleteStoryException::class);

    Event::assertNotDispatched(UserStoryCompleted::class);
});

it('throws when a criterion is still in review', function (): void {
    Event::fake([UserStoryCompleted::class]);
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->inReview()->create();

    expect(fn () => $this->service->complete($story, $this->actor))
        ->toThrow(CannotCompleteStoryException::class);
});

it('exposes a canBeCompleted predicate mirroring the model rule', function (): void {
    $empty = UserStory::factory()->create();
    expect($this->service->canBeCompleted($empty))->toBeFalse();

    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->count(2)->create();
    expect($this->service->canBeCompleted($story))->toBeTrue();
});
