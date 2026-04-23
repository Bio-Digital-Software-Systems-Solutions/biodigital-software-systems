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

it('returns zero completion percentage when no user stories exist', function (): void {
    $epic = Epic::factory()->create();

    expect($epic->completionPercentage())->toBe(0);
});

it('computes completion percentage from done stories only', function (): void {
    $epic = Epic::factory()->create();
    \App\Models\Agile\UserStory::factory()->for($epic)->done()->count(2)->create();
    \App\Models\Agile\UserStory::factory()->for($epic)->inProgress()->count(3)->create();

    expect($epic->completionPercentage())->toBe(40);
});

it('rounds completion percentage to the nearest integer', function (): void {
    $epic = Epic::factory()->create();
    \App\Models\Agile\UserStory::factory()->for($epic)->done()->count(1)->create();
    \App\Models\Agile\UserStory::factory()->for($epic)->inProgress()->count(2)->create();

    expect($epic->completionPercentage())->toBe(33);
});
