<?php

use App\Enums\Agile\UserStoryStatus;
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

// canBeCompleted() and pendingCriteriaCount() coverage lands in Step 5
// once AcceptanceCriterion exists; the full invariant is exercised by
// UserStoryCompletionServiceTest in Step 10.
