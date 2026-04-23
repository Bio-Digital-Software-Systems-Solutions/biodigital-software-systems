<?php

use App\Enums\Agile\EpicStatus;
use App\Models\Agile\Epic;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists an epic with enum cast status', function (): void {
    $epic = Epic::factory()->ready()->create();

    expect($epic->status)->toBe(EpicStatus::READY)
        ->and($epic->uuid)->toBeString()
        ->and($epic->uuid)->not->toBeEmpty()
        ->and($epic->priority)->toBeBetween(1, 5);
});

// completionPercentage() coverage is deferred to Step 4 once UserStory exists.
