<?php

use App\Enums\Agile\UserStoryStatus;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\UserStory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists a user story with enum cast status', function (): void {
    $story = UserStory::factory()->ready()->create([
        'as_a' => 'utilisateur',
        'i_want' => 'ouvrir la page X',
        'so_that' => 'y faire Y',
    ]);

    expect($story->status)->toBe(UserStoryStatus::READY)
        ->and($story->as_a)->toBe('utilisateur')
        ->and($story->i_want)->toBe('ouvrir la page X')
        ->and($story->so_that)->toBe('y faire Y');
});

it('marks a done story with completed_at via factory state', function (): void {
    $story = UserStory::factory()->done()->create();

    expect($story->status)->toBe(UserStoryStatus::DONE)
        ->and($story->completed_at)->not->toBeNull();
});

it('is not completable with zero acceptance criteria', function (): void {
    $story = UserStory::factory()->create();

    expect($story->canBeCompleted())->toBeFalse()
        ->and($story->pendingCriteriaCount())->toBe(0);
});

it('is not completable while any criterion is still pending', function (): void {
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->pending()->create();

    $story->refresh()->load('acceptanceCriteria');

    expect($story->canBeCompleted())->toBeFalse()
        ->and($story->pendingCriteriaCount())->toBe(1);
});

it('is completable when every criterion is validated', function (): void {
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->count(3)->create();

    $story->refresh()->load('acceptanceCriteria');

    expect($story->canBeCompleted())->toBeTrue()
        ->and($story->pendingCriteriaCount())->toBe(0);
});

it('is not completable when any criterion is rejected', function (): void {
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->rejected()->create();

    $story->refresh()->load('acceptanceCriteria');

    expect($story->canBeCompleted())->toBeFalse()
        ->and($story->pendingCriteriaCount())->toBe(1);
});
