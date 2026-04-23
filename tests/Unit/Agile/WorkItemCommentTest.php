<?php

use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use App\Models\Agile\WorkItemComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('attaches a comment to an Epic via morph relation', function (): void {
    $epic = Epic::factory()->create();
    $author = User::factory()->create();

    $comment = WorkItemComment::create([
        'commentable_type' => Epic::class,
        'commentable_id' => $epic->id,
        'user_id' => $author->id,
        'body' => 'Beau travail sur cet epic.',
    ]);

    expect($comment->commentable)->toBeInstanceOf(Epic::class)
        ->and($epic->comments)->toHaveCount(1)
        ->and($epic->comments->first()->user_id)->toBe($author->id);
});

it('threads replies via parent_id', function (): void {
    $story = UserStory::factory()->create();
    $author = User::factory()->create();

    $root = WorkItemComment::create([
        'commentable_type' => UserStory::class,
        'commentable_id' => $story->id,
        'user_id' => $author->id,
        'body' => 'Question sur cette story.',
    ]);

    $reply = WorkItemComment::create([
        'commentable_type' => UserStory::class,
        'commentable_id' => $story->id,
        'user_id' => $author->id,
        'parent_id' => $root->id,
        'body' => 'Réponse.',
    ]);

    expect($reply->parent?->id)->toBe($root->id)
        ->and($root->replies)->toHaveCount(1)
        ->and($story->comments)->toHaveCount(2);
});

it('attaches comments to an AcceptanceCriterion', function (): void {
    $ac = AcceptanceCriterion::factory()->create();
    $author = User::factory()->create();

    WorkItemComment::create([
        'commentable_type' => AcceptanceCriterion::class,
        'commentable_id' => $ac->id,
        'user_id' => $author->id,
        'body' => 'AC à préciser.',
    ]);

    expect($ac->comments)->toHaveCount(1);
});
