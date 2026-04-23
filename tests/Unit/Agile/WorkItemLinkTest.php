<?php

use App\Enums\Agile\WorkItemLinkType;
use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use App\Models\Agile\WorkItemLink;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a polymorphic link between two work items', function (): void {
    $actor = User::factory()->create();
    $epic = Epic::factory()->create();
    $story = UserStory::factory()->create();

    $link = WorkItemLink::create([
        'source_type' => Epic::class,
        'source_id' => $epic->id,
        'target_type' => UserStory::class,
        'target_id' => $story->id,
        'link_type' => WorkItemLinkType::BLOCKS,
        'created_by' => $actor->id,
    ]);

    expect($link->source)->toBeInstanceOf(Epic::class)
        ->and($link->source->id)->toBe($epic->id)
        ->and($link->target)->toBeInstanceOf(UserStory::class)
        ->and($link->target->id)->toBe($story->id)
        ->and($link->link_type)->toBe(WorkItemLinkType::BLOCKS);
});

it('rejects a duplicate link between the same pair with the same type', function (): void {
    $actor = User::factory()->create();
    $a = Epic::factory()->create();
    $b = Epic::factory()->create();

    $payload = [
        'source_type' => Epic::class,
        'source_id' => $a->id,
        'target_type' => Epic::class,
        'target_id' => $b->id,
        'link_type' => WorkItemLinkType::RELATES_TO,
        'created_by' => $actor->id,
    ];

    WorkItemLink::create($payload);

    expect(fn () => WorkItemLink::create($payload))->toThrow(QueryException::class);
});
