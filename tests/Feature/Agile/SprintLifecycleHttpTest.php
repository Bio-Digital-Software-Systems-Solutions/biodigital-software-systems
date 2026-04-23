<?php

use App\Events\Agile\SprintClosed;
use App\Events\Agile\SprintStarted;
use App\Models\Agile\UserStory;
use App\Models\Project;
use App\Models\Sprint;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('starts a sprint via POST /sprints/{id}/start', function (): void {
    Event::fake([SprintStarted::class]);

    $pm = asRole('project-manager');
    $sprint = Sprint::factory()->create(['status' => 'planned']);

    $this->actingAs($pm)
        ->postJson(route('api.agile.sprints.start', $sprint))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');

    Event::assertDispatched(SprintStarted::class);
});

it('refuses to start a sprint when another is active on the same project (422)', function (): void {
    Event::fake([SprintStarted::class]);

    $pm = asRole('project-manager');
    $project = Project::factory()->create();
    Sprint::factory()->for($project)->create(['status' => 'active']);
    $candidate = Sprint::factory()->for($project)->create(['status' => 'planned']);

    $this->actingAs($pm)
        ->postJson(route('api.agile.sprints.start', $candidate))
        ->assertStatus(422);

    expect($candidate->fresh()->status)->toBe('planned');
    Event::assertNotDispatched(SprintStarted::class);
});

it('closes a sprint via POST /sprints/{id}/close', function (): void {
    Event::fake([SprintClosed::class]);

    $pm = asRole('project-manager');
    $sprint = Sprint::factory()->create(['status' => 'active']);

    $this->actingAs($pm)
        ->postJson(route('api.agile.sprints.close', $sprint))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    Event::assertDispatched(SprintClosed::class);
});

it('moves a story into a planned sprint', function (): void {
    $pm = asRole('project-manager');
    $sprint = Sprint::factory()->create(['status' => 'planned']);
    $story = UserStory::factory()->create(['sprint_id' => null]);

    $this->actingAs($pm)
        ->postJson(route('api.agile.user-stories.move', $story), ['sprint_id' => $sprint->id])
        ->assertOk();

    expect($story->fresh()->sprint_id)->toBe($sprint->id);
});

it('refuses to move a story into a completed sprint (422)', function (): void {
    $pm = asRole('project-manager');
    $sprint = Sprint::factory()->create(['status' => 'completed']);
    $story = UserStory::factory()->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.user-stories.move', $story), ['sprint_id' => $sprint->id])
        ->assertStatus(422);
});

it('denies a member from starting a sprint they do not manage (403)', function (): void {
    $member = asRole('member');
    $sprint = Sprint::factory()->create(['status' => 'planned']);

    $this->actingAs($member)
        ->postJson(route('api.agile.sprints.start', $sprint))
        ->assertStatus(403);
});
