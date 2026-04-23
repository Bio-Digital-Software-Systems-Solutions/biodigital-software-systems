<?php

use App\Enums\Agile\UserStoryStatus;
use App\Models\Agile\Epic;
use App\Models\Agile\UserStory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('creates a user story with the narrative shape', function (): void {
    $pm = asRole('project-manager');
    $epic = Epic::factory()->create();
    $reporter = User::factory()->create();

    $this->actingAs($pm)
        ->post(route('agile.user-stories.store'), [
            'epic_id' => $epic->id,
            'reporter_id' => $reporter->id,
            'title' => 'Ma story',
            'as_a' => 'membre',
            'i_want' => 'voir le dashboard',
            'so_that' => 'suivre mes activités',
        ])
        ->assertRedirect();

    $story = UserStory::where('title', 'Ma story')->firstOrFail();
    expect($story->as_a)->toBe('membre')
        ->and($story->i_want)->toBe('voir le dashboard')
        ->and($story->so_that)->toBe('suivre mes activités')
        ->and($story->status)->toBe(UserStoryStatus::BACKLOG);
});

it('rejects creation with status=done (must use /complete)', function (): void {
    $pm = asRole('project-manager');
    $epic = Epic::factory()->create();
    $reporter = User::factory()->create();

    $this->actingAs($pm)
        ->postJson(route('agile.user-stories.store'), [
            'epic_id' => $epic->id,
            'reporter_id' => $reporter->id,
            'title' => 'Story déjà faite',
            'as_a' => 'x',
            'i_want' => 'y',
            'so_that' => 'z',
            'status' => UserStoryStatus::DONE->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('rejects update with status=done (must use /complete)', function (): void {
    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();

    $this->actingAs($pm)
        ->patchJson(route('agile.user-stories.update', $story), [
            'status' => UserStoryStatus::DONE->value,
        ])
        ->assertStatus(422);
});

it('rejects required field omissions', function (): void {
    $pm = asRole('project-manager');

    $this->actingAs($pm)
        ->postJson(route('agile.user-stories.store'), [
            // missing title, as_a, i_want, so_that, reporter_id
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'as_a', 'i_want', 'so_that', 'reporter_id']);
});
