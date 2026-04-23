<?php

use App\Events\Agile\SprintClosed;
use App\Events\Agile\SprintStarted;
use App\Exceptions\Agile\ActiveSprintAlreadyExistsException;
use App\Exceptions\Agile\ClosedSprintCannotAcceptStoriesException;
use App\Models\Agile\UserStory;
use App\Models\Project;
use App\Models\Sprint;
use App\Services\Agile\SprintLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new SprintLifecycleService;
});

it('transitions a planned sprint to active and dispatches SprintStarted', function (): void {
    Event::fake([SprintStarted::class, SprintClosed::class]);
    $project = Project::factory()->create();
    $sprint = Sprint::factory()->for($project)->create(['status' => 'planned']);

    $this->service->start($sprint);

    expect($sprint->fresh()->status)->toBe('active');
    Event::assertDispatched(SprintStarted::class);
});

it('refuses to start a sprint while another is already active on the same project', function (): void {
    Event::fake([SprintStarted::class, SprintClosed::class]);
    $project = Project::factory()->create();
    Sprint::factory()->for($project)->create(['status' => 'active']);
    $candidate = Sprint::factory()->for($project)->create(['status' => 'planned']);

    expect(fn () => $this->service->start($candidate))
        ->toThrow(ActiveSprintAlreadyExistsException::class);

    expect($candidate->fresh()->status)->toBe('planned');
    Event::assertNotDispatched(SprintStarted::class);
});

it('allows activating a sprint when only another project has an active sprint', function (): void {
    Event::fake([SprintStarted::class, SprintClosed::class]);
    $projectA = Project::factory()->create();
    $projectB = Project::factory()->create();
    Sprint::factory()->for($projectA)->create(['status' => 'active']);
    $candidate = Sprint::factory()->for($projectB)->create(['status' => 'planned']);

    $this->service->start($candidate);

    expect($candidate->fresh()->status)->toBe('active');
});

it('closes a sprint and dispatches SprintClosed', function (): void {
    Event::fake([SprintStarted::class, SprintClosed::class]);
    $sprint = Sprint::factory()->create(['status' => 'active']);

    $this->service->close($sprint);

    expect($sprint->fresh()->status)->toBe('completed');
    Event::assertDispatched(SprintClosed::class);
});

it('moves a story into a planned sprint', function (): void {
    $sprint = Sprint::factory()->create(['status' => 'planned']);
    $story = UserStory::factory()->create(['sprint_id' => null]);

    $this->service->moveStoryToSprint($story, $sprint);

    expect($story->fresh()->sprint_id)->toBe($sprint->id);
});

it('moves a story into an active sprint', function (): void {
    $sprint = Sprint::factory()->create(['status' => 'active']);
    $story = UserStory::factory()->create();

    $this->service->moveStoryToSprint($story, $sprint);

    expect($story->fresh()->sprint_id)->toBe($sprint->id);
});

it('refuses to move a story into a completed sprint', function (): void {
    $sprint = Sprint::factory()->create(['status' => 'completed']);
    $story = UserStory::factory()->create();

    expect(fn () => $this->service->moveStoryToSprint($story, $sprint))
        ->toThrow(ClosedSprintCannotAcceptStoriesException::class);

    expect($story->fresh()->sprint_id)->toBeNull();
});

it('detaches a story from its sprint when target is null', function (): void {
    $sprint = Sprint::factory()->create(['status' => 'active']);
    $story = UserStory::factory()->create(['sprint_id' => $sprint->id]);

    $this->service->moveStoryToSprint($story, null);

    expect($story->fresh()->sprint_id)->toBeNull();
});
