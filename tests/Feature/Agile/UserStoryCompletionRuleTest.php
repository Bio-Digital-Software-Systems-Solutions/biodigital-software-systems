<?php

use App\Enums\Agile\UserStoryStatus;
use App\Events\Agile\UserStoryCompleted;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\UserStory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('200 when every acceptance criterion is validated and dispatches UserStoryCompleted', function (): void {
    Event::fake([UserStoryCompleted::class]);

    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->count(3)->create();

    $response = $this->actingAs($pm)
        ->postJson(route('api.agile.user-stories.complete', $story));

    $response->assertOk()
        ->assertJsonPath('data.status', UserStoryStatus::DONE->value);

    expect($story->fresh()->status)->toBe(UserStoryStatus::DONE);
    Event::assertDispatched(UserStoryCompleted::class);
});

it('422 when some criteria are still pending', function (): void {
    Event::fake([UserStoryCompleted::class]);

    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->pending()->count(2)->create();

    $response = $this->actingAs($pm)
        ->postJson(route('api.agile.user-stories.complete', $story));

    $response->assertStatus(422)
        ->assertJsonStructure(['message']);

    expect($story->fresh()->status)->not->toBe(UserStoryStatus::DONE);
    Event::assertNotDispatched(UserStoryCompleted::class);
});

it('422 when the story has no acceptance criteria at all', function (): void {
    $pm = asRole('project-manager');
    $story = UserStory::factory()->create();

    $this->actingAs($pm)
        ->postJson(route('api.agile.user-stories.complete', $story))
        ->assertStatus(422);
});

it('403 for a member without complete-story permission', function (): void {
    $member = asRole('member');
    $story = UserStory::factory()->create();
    AcceptanceCriterion::factory()->for($story, 'userStory')->validated()->count(2)->create();

    $this->actingAs($member)
        ->postJson(route('api.agile.user-stories.complete', $story))
        ->assertStatus(403);
});
