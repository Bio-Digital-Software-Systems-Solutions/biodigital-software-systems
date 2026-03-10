<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Project;
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

class ProjectAppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view projects']);
        Permission::create(['name' => 'create projects']);
        Permission::create(['name' => 'edit projects']);
        Permission::create(['name' => 'delete projects']);

        // Create role with permissions
        $role = Role::create(['name' => 'project-manager']);
        $role->givePermissionTo(['view projects', 'create projects', 'edit projects', 'delete projects']);

        // Create users
        $this->user = User::factory()->create();
        $this->user->assignRole('project-manager');

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('project-manager');

        // Create project
        $this->project = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_list_project_appointments(): void
    {
        Sanctum::actingAs($this->user);

        // Create appointments for the project
        Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Team Meeting',
        ]);

        Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Sprint Review',
        ]);

        $response = $this->getJson("/api/projects/{$this->project->uuid}/appointments");

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
    public function it_can_create_a_project_appointment(): void
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Project Kickoff Meeting',
            'description' => 'Initial meeting to discuss project scope',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'location' => 'Conference Room A',
            'type' => 'individual',
            'visibility' => 'public',
            'max_participants' => 10,
            'participants' => [$this->otherUser->id],
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous créé avec succès.',
            ])
            ->assertJsonPath('data.title', 'Project Kickoff Meeting');

        $this->assertDatabaseHas('appointments', [
            'title' => 'Project Kickoff Meeting',
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ]);
    }

    /** @test */
    public function it_can_create_a_task_appointment(): void
    {
        Sanctum::actingAs($this->user);

        // Create a task for the project
        $task = Task::factory()->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $this->project->id,
            'project_id' => $this->project->id,
        ]);

        $appointmentData = [
            'title' => 'Task Review',
            'description' => 'Review task progress',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'group',
            'visibility' => 'private',
            'appointmentable_type' => \App\Models\Task::class,
            'appointmentable_id' => $task->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.title', 'Task Review');

        $this->assertDatabaseHas('appointments', [
            'title' => 'Task Review',
            'appointmentable_type' => \App\Models\Task::class,
            'appointmentable_id' => $task->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_appointment(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start_datetime', 'end_datetime', 'type', 'visibility', 'appointmentable_type', 'appointmentable_id']);
    }

    /** @test */
    public function it_validates_end_datetime_is_after_start_datetime(): void
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Invalid Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->format('Y-m-d\TH:i:s'), // Before start
            'type' => 'individual',
            'visibility' => 'public',
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_datetime']);
    }

    /** @test */
    public function it_can_update_a_project_appointment(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'confirmed',
        ];

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous mis à jour avec succès.',
            ])
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'uuid' => $appointment->uuid,
            'title' => 'Updated Title',
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_can_delete_a_project_appointment(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rendez-vous supprimé avec succès.',
            ]);

        $this->assertDatabaseMissing('appointments', [
            'uuid' => $appointment->uuid,
        ]);
    }

    /** @test */
    public function it_cannot_delete_appointment_from_another_project(): void
    {
        Sanctum::actingAs($this->user);

        // Create another project
        $anotherProject = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);

        // Create appointment for another project
        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $anotherProject->id,
            'user_id' => $this->user->id,
        ]);

        // Try to delete from wrong project
        $response = $this->deleteJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_appointments_for_a_specific_month(): void
    {
        Sanctum::actingAs($this->user);

        // Create appointments in different months
        Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'start_datetime' => now()->startOfMonth()->addDays(5),
            'end_datetime' => now()->startOfMonth()->addDays(5)->addHour(),
        ]);

        Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'start_datetime' => now()->addMonth()->startOfMonth()->addDays(5),
            'end_datetime' => now()->addMonth()->startOfMonth()->addDays(5)->addHour(),
        ]);

        $response = $this->getJson("/api/projects/{$this->project->uuid}/appointments/month?" . http_build_query([
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_adds_organizer_as_confirmed_participant(): void
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Self Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'individual',
            'visibility' => 'public',
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(201);

        $appointment = Appointment::latest()->first();
        $this->assertTrue($appointment->participants->contains($this->user));
    }

    /** @test */
    public function it_includes_task_appointments_in_project_list(): void
    {
        Sanctum::actingAs($this->user);

        // Create a task for the project
        $task = Task::factory()->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $this->project->id,
            'project_id' => $this->project->id,
        ]);

        // Create project appointment
        Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'title' => 'Project Meeting',
        ]);

        // Create task appointment
        Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Task::class,
            'appointmentable_id' => $task->id,
            'user_id' => $this->user->id,
            'title' => 'Task Meeting',
        ]);

        $response = $this->getJson("/api/projects/{$this->project->uuid}/appointments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $titles = collect($response->json('data'))->pluck('title')->toArray();
        $this->assertContains('Project Meeting', $titles);
        $this->assertContains('Task Meeting', $titles);
    }

    /** @test */
    public function it_rejects_task_appointment_for_task_not_belonging_to_project(): void
    {
        Sanctum::actingAs($this->user);

        // Create another project and its task
        $anotherProject = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);

        $task = Task::factory()->create([
            'taskable_type' => \App\Models\Project::class,
            'taskable_id' => $anotherProject->id,
            'project_id' => $anotherProject->id,
        ]);

        $appointmentData = [
            'title' => 'Invalid Task Appointment',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'group',
            'visibility' => 'public',
            'appointmentable_type' => \App\Models\Task::class,
            'appointmentable_id' => $task->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'La tâche n\'appartient pas à ce projet.',
            ]);
    }

    /** @test */
    public function it_requires_authentication(): void
    {
        $response = $this->getJson("/api/projects/{$this->project->uuid}/appointments");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_appointment_type(): void
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Test',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'invalid_type',
            'visibility' => 'public',
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function it_validates_visibility(): void
    {
        Sanctum::actingAs($this->user);

        $appointmentData = [
            'title' => 'Test',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'individual',
            'visibility' => 'invalid_visibility',
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visibility']);
    }

    /** @test */
    public function it_can_update_appointment_participants(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        // Add initial participant
        $appointment->participants()->attach($this->otherUser->id, ['status' => 'pending']);

        // Create new user
        $newUser = User::factory()->create();

        $updateData = [
            'participants' => [$newUser->id],
        ];

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Check new participant is added
        $this->assertTrue($appointment->fresh()->participants->contains($newUser));
    }

    /** @test */
    public function it_can_update_appointment_status(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'confirmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('appointments', [
            'uuid' => $appointment->uuid,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_can_cancel_appointment(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @test */
    public function it_can_complete_appointment(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    /** @test */
    public function it_can_update_appointment_datetime(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $newStartDatetime = now()->addDays(5)->format('Y-m-d\TH:i:s');
        $newEndDatetime = now()->addDays(5)->addHours(2)->format('Y-m-d\TH:i:s');

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", [
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
    public function it_returns_appointment_with_participants_in_response(): void
    {
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        // Add participants
        $appointment->participants()->attach($this->user->id, ['status' => 'accepted']);
        $appointment->participants()->attach($this->otherUser->id, ['status' => 'pending']);

        $response = $this->getJson("/api/projects/{$this->project->uuid}/appointments");

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
    public function it_sends_invitation_email_to_participants_on_create(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $participant = User::factory()->create();

        $appointmentData = [
            'title' => 'Team Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'meeting',
            'visibility' => 'public',
            'participants' => [$participant->id],
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(201);

        // Assert invitation was sent to participant
        Notification::assertSentTo($participant, AppointmentInvitation::class);

        // Assert organizer did NOT receive invitation
        Notification::assertNotSentTo($this->user, AppointmentInvitation::class);
    }

    /** @test */
    public function it_sends_invitation_email_to_new_participants_on_update(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
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

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Assert invitation was sent only to NEW participant
        Notification::assertSentTo($newParticipant, AppointmentInvitation::class);

        // Assert existing participant did NOT receive a new invitation
        Notification::assertNotSentTo($existingParticipant, AppointmentInvitation::class);
    }

    /** @test */
    public function it_sends_update_notification_to_existing_participants_when_details_change(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
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

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Assert update notification was sent to existing participant
        Notification::assertSentTo($participant, AppointmentUpdated::class);
    }

    /** @test */
    public function it_does_not_send_update_notification_when_no_significant_changes(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $appointment = Appointment::factory()->create([
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
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

        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", $updateData);

        $response->assertStatus(200);

        // Assert NO update notification was sent (description changes don't trigger notifications)
        Notification::assertNotSentTo($participant, AppointmentUpdated::class);
    }

    /** @test */
    public function it_stores_confirmation_token_for_new_participants(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $participant = User::factory()->create();

        $appointmentData = [
            'title' => 'Team Meeting',
            'start_datetime' => now()->addDay()->format('Y-m-d\TH:i:s'),
            'end_datetime' => now()->addDay()->addHour()->format('Y-m-d\TH:i:s'),
            'type' => 'meeting',
            'visibility' => 'public',
            'participants' => [$participant->id],
            'appointmentable_type' => \App\Models\Project::class,
            'appointmentable_id' => $this->project->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->uuid}/appointments", $appointmentData);

        $response->assertStatus(201);

        // Check that confirmation token was stored in pivot table
        $appointment = Appointment::latest()->first();
        $pivot = $appointment->participants()->where('user_id', $participant->id)->first()->pivot;

        $this->assertNotNull($pivot->confirmation_token);
        $this->assertEquals(64, strlen($pivot->confirmation_token));
        $this->assertNotNull($pivot->invited_at);
    }
}
