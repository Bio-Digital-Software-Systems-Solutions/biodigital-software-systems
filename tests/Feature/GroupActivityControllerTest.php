<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesPermissions;
use Tests\TestCase;

class GroupActivityControllerTest extends TestCase
{
    use CreatesPermissions, RefreshDatabase;

    public User $user;

    public Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupPermissions();

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('edit groups');

        $this->group = Group::factory()->create();
    }

    public function test_can_list_group_activities(): void
    {
        GroupActivity::factory()->count(3)->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/groups/{$this->group->uuid}/activities");

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['uuid', 'title', 'activity_date', 'status', 'type'],
            ],
        ]);
    }

    public function test_can_create_group_activity(): void
    {
        $data = [
            'title' => 'Nouvelle activité',
            'description' => 'Description de l\'activité',
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'type' => 'task',
            'location' => 'Bureau 1',
            'notes' => 'Notes',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Nouvelle activité');

        $this->assertDatabaseHas('group_activities', [
            'group_id' => $this->group->id,
            'title' => 'Nouvelle activité',
            'type' => 'task',
            'location' => 'Bureau 1',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_activity_requires_title(): void
    {
        $data = [
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_create_activity_requires_date(): void
    {
        $data = [
            'title' => 'Test',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['activity_date']);
    }

    public function test_create_activity_validates_status(): void
    {
        $data = [
            'title' => 'Test',
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
            'status' => 'invalid_status',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_create_activity_validates_type(): void
    {
        $data = [
            'title' => 'Test',
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
            'type' => 'invalid_type',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_can_create_activity_with_assignee(): void
    {
        $assignee = User::factory()->create();
        $this->group->users()->attach($assignee, ['joined_at' => now()]);

        $data = [
            'title' => 'Activité assignée',
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
            'assigned_to' => $assignee->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertCreated();
        $response->assertJsonPath('data.assignee.id', $assignee->id);
    }

    public function test_can_update_group_activity(): void
    {
        $activity = GroupActivity::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
            'title' => 'Original',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/groups/{$this->group->uuid}/activities/{$activity->uuid}", [
                'title' => 'Mis à jour',
                'status' => 'in_progress',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.title', 'Mis à jour');
        $response->assertJsonPath('data.status', 'in_progress');

        $activity->refresh();
        $this->assertEquals('Mis à jour', $activity->title);
        $this->assertEquals('in_progress', $activity->status);
    }

    public function test_cannot_update_activity_from_different_group(): void
    {
        $otherGroup = Group::factory()->create();
        $activity = GroupActivity::factory()->create([
            'group_id' => $otherGroup->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/groups/{$this->group->uuid}/activities/{$activity->uuid}", [
                'title' => 'Tentative',
            ]);

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_can_delete_group_activity(): void
    {
        $activity = GroupActivity::factory()->create([
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/groups/{$this->group->uuid}/activities/{$activity->uuid}");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('group_activities', ['id' => $activity->id]);
    }

    public function test_cannot_delete_activity_from_different_group(): void
    {
        $otherGroup = Group::factory()->create();
        $activity = GroupActivity::factory()->create([
            'group_id' => $otherGroup->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/groups/{$this->group->uuid}/activities/{$activity->uuid}");

        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_activity_defaults_to_planned_status(): void
    {
        $data = [
            'title' => 'Activité sans statut',
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'planned');
    }

    public function test_activity_defaults_to_task_type(): void
    {
        $data = [
            'title' => 'Activité sans type',
            'activity_date' => now()->addDays(3)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/activities", $data);

        $response->assertCreated();
        $response->assertJsonPath('data.type', 'task');
    }
}
