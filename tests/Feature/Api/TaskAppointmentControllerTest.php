<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AppointmentInvitation;
use App\Notifications\AppointmentUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskAppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view projects']);
        Permission::create(['name' => 'create projects']);
        Permission::create(['name' => 'edit projects']);
        Permission::create(['name' => 'delete projects']);
        Permission::create(['name' => 'manage tasks']);

        // Create role with permissions
        $role = Role::create(['name' => 'project-manager']);
        $role->givePermissionTo(['view projects', 'create projects', 'edit projects', 'delete projects', 'manage tasks']);

        // Create users
        $this->user = User::factory()->create();
        $this->user->assignRole('project-manager');

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('project-manager');

        // Create project
        $this->project = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);

        // Create status for task
        $status = Status::first() ?? Status::factory()->create();

        // Create task
        $this->task = Task::factory()->create([
            'title' => 'Test Task',
            'project_id' => $this->project->id,
            'status_id' => $status->id,
        ]);
    }

    /** @test */
    public function it_can_list_task_appointments()
    {
        Sanctum::actingAs($this->user);

        // Create appointments for the task
        $appointment1 = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'title' => 'Task Review Meeting',
        ]);

        $appointment2 = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'title' => 'Task Progress Update',
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->uuid}/appointments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'start_datetime',
                        'end_datetime',
                        'status',
                        'type',
                        'visibility',
                        'organizer',
                        'participants_count',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_create_a_task_appointment()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Task Kickoff Meeting',
            'description' => 'Initial meeting to discuss task scope',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'location' => 'Conference Room A',
            'type' => 'individual',
            'visibility' => 'public',
            'max_participants' => 10,
            'participants' => [$this->otherUser->id],
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous créé avec succès.',
            ])
            ->assertJsonPath('data.title', 'Task Kickoff Meeting');

        $this->assertDatabaseHas('appointments', [
            'title' => 'Task Kickoff Meeting',
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_task_appointment()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start_datetime', 'end_datetime', 'type', 'visibility']);
    }

    /** @test */
    public function it_validates_end_datetime_is_after_start_datetime()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Invalid Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->format('Y-m-d\TH:i:s'), // Before start
            'type' => 'individual',
            'visibility' => 'public',
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_datetime']);
    }

    /** @test */
    public function it_can_update_a_task_appointment()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'confirmed',
        ];

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous mis à jour avec succès.',
            ])
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'title' => 'Updated Title',
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_can_delete_a_task_appointment()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous supprimé avec succès.',
            ]);

        $this->assertDatabaseMissing('appointments', [
            'id' => $appointment->id,
        ]);
    }

    /** @test */
    public function it_cannot_delete_appointment_from_another_task()
    {
        Sanctum::actingAs($this->user);

        // Create another task
        $status = Status::first() ?? Status::factory()->create();
        $anotherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status_id' => $status->id,
        ]);

        // Create appointment for another task
        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $anotherTask->id,
            'user_id' => $this->user->id,
        ]);

        // Try to delete from wrong task
        $response = $this->deleteJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_appointments_for_a_specific_month()
    {
        Sanctum::actingAs($this->user);

        // Create appointments in different months
        $thisMonthAppointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'start_datetime' => now()->startOfMonth()->addDays(5),
            'end_datetime' => now()->startOfMonth()->addDays(5)->addHour(),
        ]);

        $nextMonthAppointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'start_datetime' => now()->addMonth()->startOfMonth()->addDays(5),
            'end_datetime' => now()->addMonth()->startOfMonth()->addDays(5)->addHour(),
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->uuid}/appointments/month?" . http_build_query([
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_adds_organizer_as_confirmed_participant()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Self Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'individual',
            'visibility' => 'public',
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();
        $this->assertTrue($appointment->participants->contains($this->user));
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson("/api/tasks/{$this->task->uuid}/appointments");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_appointment_type()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Test',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'invalid_type',
            'visibility' => 'public',
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_validates_visibility()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Test',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'individual',
            'visibility' => 'invalid_visibility',
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visibility']);
    }

    /** @test */
    public function it_can_update_appointment_participants()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        // Add initial participant
        $appointment->participants()->attach($this->otherUser->id, ['status' => 'pending']);

        // Create new user
        $newUser = User::factory()->create();

        $updateData = [
            'participants' => [$newUser->id],
        ];

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Check new participant is added
        $this->assertTrue($appointment->fresh()->participants->contains($newUser));
    }

    /** @test */
    public function it_can_update_appointment_status()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_can_cancel_appointment()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function it_can_complete_appointment()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    /** @test */
    public function it_can_update_appointment_datetime()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $newStartDatetime = now()->addDays(5)->format('Y-m-d\TH:i:s');
        $newEndDatetime = now()->addDays(5)->addHours(2)->format('Y-m-d\TH:i:s');

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'start_datetime' => $newStartDatetime,
            'end_datetime' => $newEndDatetime,
        ]);

        $response->assertStatus(200);

        $appointment->refresh();
        $this->assertEquals(
            now()->addDays(5)->format('Y-m-d H:i'),
            $appointment->start_datetime->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_returns_appointment_with_participants_in_response()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        // Add participants
        $appointment->participants()->attach($this->user->id, ['status' => 'accepted']);
        $appointment->participants()->attach($this->otherUser->id, ['status' => 'pending']);

        $response = $this->getJson("/api/tasks/{$this->task->uuid}/appointments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'uuid',
                        'title',
                        'participants',
                        'participants_count',
                    ],
                ],
            ]);

        $appointmentData = collect($response->json('data'))->firstWhere('id', $appointment->id);
        $this->assertCount(2, $appointmentData['participants']);
    }

    /** @test */
    public function it_sends_invitation_email_to_participants_on_create()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $participant = User::factory()->create();

        $appointmentData = [
            'title' => 'Task Review Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'meeting',
            'visibility' => 'public',
            'participants' => [$participant->id],
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(201);

        // Assert invitation was sent to participant
        Notification::assertSentTo($participant, AppointmentInvitation::class);

        // Assert organizer did NOT receive invitation
        Notification::assertNotSentTo($this->user, AppointmentInvitation::class);
    }

    /** @test */
    public function it_sends_invitation_email_to_new_participants_on_update()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        // Add initial participant
        $existingParticipant = User::factory()->create();
        $appointment->participants()->attach($existingParticipant->id, [
            'status' => 'accepted',
            'confirmation_token' => 'existing_token',
        ]);

        // New participant to add
        $newParticipant = User::factory()->create();

        $updateData = [
            'participants' => [$existingParticipant->id, $newParticipant->id],
        ];

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Assert invitation was sent only to NEW participant
        Notification::assertSentTo($newParticipant, AppointmentInvitation::class);

        // Assert existing participant did NOT receive a new invitation
        Notification::assertNotSentTo($existingParticipant, AppointmentInvitation::class);
    }

    /** @test */
    public function it_sends_update_notification_to_existing_participants_when_details_change()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'start_datetime' => now()->addDay(),
            'end_datetime' => now()->addDay()->addHour(),
        ]);

        // Add participant
        $participant = User::factory()->create();
        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
            'confirmation_token' => 'test_token',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'start_datetime' => now()->addDays(2)->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDays(2)->addHour()->format('Y-m-d\TH:i:s'),
        ];

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Assert update notification was sent to existing participant
        Notification::assertSentTo($participant, AppointmentUpdated::class);
    }

    /** @test */
    public function it_does_not_send_update_notification_when_no_significant_changes()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'description' => 'Original Description',
        ]);

        // Add participant
        $participant = User::factory()->create();
        $appointment->participants()->attach($participant->id, [
            'status' => 'accepted',
            'confirmation_token' => 'test_token',
        ]);

        // Only update description (not tracked for notifications)
        $updateData = [
            'description' => 'Updated Description',
        ];

        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Assert NO update notification was sent (description changes don't trigger notifications)
        Notification::assertNotSentTo($participant, AppointmentUpdated::class);
    }

    /** @test */
    public function it_stores_confirmation_token_for_new_participants()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $participant = User::factory()->create();

        $appointmentData = [
            'title' => 'Task Review Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'meeting',
            'visibility' => 'public',
            'participants' => [$participant->id],
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(201);

        // Check that confirmation token was stored in pivot table
        $appointment = Appointment::latest()->first();
        $pivot = $appointment->participants()->where('user_id', $participant->id)->first()->pivot;

        $this->assertNotNull($pivot->confirmation_token);
        $this->assertEquals(64, strlen($pivot->confirmation_token));
        $this->assertNotNull($pivot->invited_at);
    }

    /** @test */
    public function it_returns_appointmentable_info_in_response()
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'title' => 'Task Meeting',
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->uuid}/appointments");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.appointmentable_type', 'Task')
            ->assertJsonPath('data.0.appointmentable.id', $this->task->id);
    }

    /** @test */
    public function it_only_returns_appointments_for_specific_task()
    {
        Sanctum::actingAs($this->user);

        // Create another task
        $status = Status::first() ?? Status::factory()->create();
        $anotherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status_id' => $status->id,
        ]);

        // Create appointment for this task
        $thisTaskAppointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $this->task->id,
            'user_id' => $this->user->id,
            'title' => 'This Task Meeting',
        ]);

        // Create appointment for another task
        $anotherTaskAppointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $anotherTask->id,
            'user_id' => $this->user->id,
            'title' => 'Another Task Meeting',
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->uuid}/appointments");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'This Task Meeting');
    }

    /** @test */
    public function it_cannot_update_appointment_from_another_task()
    {
        Sanctum::actingAs($this->user);

        // Create another task
        $status = Status::first() ?? Status::factory()->create();
        $anotherTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'status_id' => $status->id,
        ]);

        // Create appointment for another task
        $appointment = Appointment::factory()->create([
            'appointmentable_type' => 'App\\Models\\Task',
            'appointmentable_id' => $anotherTask->id,
            'user_id' => $this->user->id,
            'title' => 'Another Task Meeting',
        ]);

        $updateData = [
            'title' => 'Trying to update from wrong task',
        ];

        // Try to update from wrong task
        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_max_participants()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Test Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'group',
            'visibility' => 'public',
            'max_participants' => 0, // Invalid - must be at least 1
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['max_participants']);
    }

    /** @test */
    public function it_accepts_all_valid_appointment_types()
    {
        Sanctum::actingAs($this->user);

        $validTypes = ['individual', 'group', 'consultation', 'meeting'];

        foreach ($validTypes as $type) {
            $appointmentData = [
                'title' => "Test {$type} appointment",
                'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
                'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
                'type' => $type,
                'visibility' => 'public',
            ];

            $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('appointments', 4);
    }

    /** @test */
    public function it_can_create_appointment_with_location()
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'On-site Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'meeting',
            'visibility' => 'public',
            'location' => 'Conference Room B, Building 1, Floor 3',
        ];

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/appointments", $appointmentData);

        $response->assertStatus(201)
            ->assertJsonPath('data.location', 'Conference Room B, Building 1, Floor 3');

        $this->assertDatabaseHas('appointments', [
            'title' => 'On-site Meeting',
            'location' => 'Conference Room B, Building 1, Floor 3',
        ]);
    }
}
