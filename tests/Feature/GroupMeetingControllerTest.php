<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Group;
use App\Models\GroupMeeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\CreatesPermissions;
use Tests\TestCase;

class GroupMeetingControllerTest extends TestCase
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

    public function test_can_list_group_meetings(): void
    {
        $appointment = Appointment::factory()->create([
            'appointmentable_type' => Group::class,
            'appointmentable_id' => $this->group->id,
        ]);

        GroupMeeting::factory()->create([
            'group_id' => $this->group->id,
            'appointment_id' => $appointment->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/groups/{$this->group->uuid}/meetings");

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['uuid', 'is_mandatory', 'notify_all_members', 'appointment'],
            ],
        ]);
    }

    public function test_can_create_group_meeting(): void
    {
        $data = [
            'title' => 'Réunion de groupe',
            'description' => 'Description de la réunion',
            'start_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'location' => 'Salle A',
            'type' => 'meeting',
            'is_mandatory' => true,
            'notify_all_members' => true,
            'notes' => 'Notes de réunion',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/meetings", $data);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('group_meetings', [
            'group_id' => $this->group->id,
            'created_by' => $this->user->id,
            'is_mandatory' => true,
        ]);

        $this->assertDatabaseHas('appointments', [
            'title' => 'Réunion de groupe',
            'location' => 'Salle A',
        ]);
    }

    public function test_create_meeting_requires_title(): void
    {
        $data = [
            'start_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'type' => 'meeting',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/meetings", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_create_meeting_validates_dates(): void
    {
        $data = [
            'title' => 'Test',
            'start_datetime' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'type' => 'meeting',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/meetings", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['end_datetime']);
    }

    public function test_create_meeting_validates_type(): void
    {
        $data = [
            'title' => 'Test',
            'start_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(1)->addHours(2)->format('Y-m-d H:i:s'),
            'type' => 'invalid_type',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/meetings", $data);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_can_delete_group_meeting(): void
    {
        $appointment = Appointment::factory()->create([
            'appointmentable_type' => Group::class,
            'appointmentable_id' => $this->group->id,
        ]);

        $meeting = GroupMeeting::factory()->create([
            'group_id' => $this->group->id,
            'appointment_id' => $appointment->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/groups/{$this->group->uuid}/meetings/{$meeting->uuid}");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_cannot_delete_meeting_from_different_group(): void
    {
        $otherGroup = Group::factory()->create();

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => Group::class,
            'appointmentable_id' => $otherGroup->id,
        ]);

        $meeting = GroupMeeting::factory()->create([
            'group_id' => $otherGroup->id,
            'appointment_id' => $appointment->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/groups/{$this->group->uuid}/meetings/{$meeting->uuid}");

        // Returns 404 (route scoping) or 403 (controller check)
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }

    public function test_create_meeting_with_participants(): void
    {
        $participant = User::factory()->create();

        $data = [
            'title' => 'Réunion avec participants',
            'start_datetime' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_datetime' => now()->addDays(1)->addHours(1)->format('Y-m-d H:i:s'),
            'type' => 'meeting',
            'participant_ids' => [$participant->id],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/groups/{$this->group->uuid}/meetings", $data);

        $response->assertCreated();

        $appointment = Appointment::where('title', 'Réunion avec participants')->first();
        $this->assertNotNull($appointment);
        $this->assertTrue($appointment->participants->contains($participant));
        $this->assertTrue($appointment->participants->contains($this->user));
    }
}
