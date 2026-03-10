<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Notifications\AppointmentCancellation;
use App\Notifications\AppointmentConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $organizer;

    protected User $participant1;

    protected User $participant2;

    protected User $participant3;

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
        $this->organizer = User::factory()->create();
        $this->organizer->assignRole('project-manager');

        $this->participant1 = User::factory()->create();
        $this->participant1->assignRole('project-manager');

        $this->participant2 = User::factory()->create();
        $this->participant2->assignRole('project-manager');

        $this->participant3 = User::factory()->create();
        $this->participant3->assignRole('project-manager');

        // Create project
        $this->project = Project::factory()->create([
            'project_manager_id' => $this->organizer->id,
        ]);

        // Create status for task
        $status = Status::first() ?? Status::factory()->create();

        // Create task with polymorphic relationship to project
        $this->task = Task::factory()->create([
            'title' => 'Test Task',
            'taskable_type' => Project::class,
            'taskable_id' => $this->project->id,
            'status_id' => $status->id,
        ]);

        Notification::fake();
    }

    /**
     * Helper method to create an appointment with participants
     */
    protected function createAppointmentWithParticipants(): Appointment
    {
        $appointment = Appointment::create([
            'title' => 'Test Appointment',
            'description' => 'Test description',
            'start_datetime' => now()->addDays(1),
            'end_datetime' => now()->addDays(1)->addHours(1),
            'location' => 'Test Location',
            'type' => 'meeting',
            'visibility' => 'public',
            'status' => 'pending',
            'user_id' => $this->organizer->id,
            'appointmentable_type' => Task::class,
            'appointmentable_id' => $this->task->id,
        ]);

        // Add organizer as accepted participant
        $appointment->participants()->attach($this->organizer->id, [
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add other participants with pending status and tokens
        $appointment->participants()->attach($this->participant1->id, [
            'status' => 'pending',
            'confirmation_token' => Str::random(64),
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment->participants()->attach($this->participant2->id, [
            'status' => 'pending',
            'confirmation_token' => Str::random(64),
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment->participants()->attach($this->participant3->id, [
            'status' => 'pending',
            'confirmation_token' => Str::random(64),
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $appointment;
    }

    // ========================================
    // Tests for Confirmation Notifications
    // ========================================

    public function test_confirming_participation_notifies_organizer(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        // Get participant1's token
        $token = $appointment->participants()
            ->wherePivot('user_id', $this->participant1->id)
            ->first()
            ->pivot
            ->confirmation_token;

        // Confirm participation
        $response = $this->get(route('appointments.participant.confirm', [
            'appointment' => $appointment->uuid,
            'token' => $token,
        ]));

        $response->assertStatus(200);

        // Verify organizer was notified
        Notification::assertSentTo(
            $this->organizer,
            AppointmentConfirmation::class,
            fn($notification): bool => $notification->status === 'confirmed' &&
                   $notification->participant->id === $this->participant1->id
        );
    }

    public function test_confirming_participation_notifies_all_other_participants(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        // Get participant1's token
        $token = $appointment->participants()
            ->wherePivot('user_id', $this->participant1->id)
            ->first()
            ->pivot
            ->confirmation_token;

        // Confirm participation
        $response = $this->get(route('appointments.participant.confirm', [
            'appointment' => $appointment->uuid,
            'token' => $token,
        ]));

        $response->assertStatus(200);

        // Verify participant2 and participant3 were notified
        Notification::assertSentTo(
            $this->participant2,
            AppointmentConfirmation::class,
            fn($notification): bool => $notification->status === 'confirmed' &&
                   $notification->participant->id === $this->participant1->id
        );

        Notification::assertSentTo(
            $this->participant3,
            AppointmentConfirmation::class,
            fn($notification): bool => $notification->status === 'confirmed' &&
                   $notification->participant->id === $this->participant1->id
        );
    }

    public function test_confirming_participation_does_not_notify_self(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        // Get participant1's token
        $token = $appointment->participants()
            ->wherePivot('user_id', $this->participant1->id)
            ->first()
            ->pivot
            ->confirmation_token;

        // Confirm participation
        $this->get(route('appointments.participant.confirm', [
            'appointment' => $appointment->uuid,
            'token' => $token,
        ]));

        // Verify participant1 was NOT notified
        Notification::assertNotSentTo(
            $this->participant1,
            AppointmentConfirmation::class
        );
    }

    // ========================================
    // Tests for Cancellation Notifications
    // ========================================

    public function test_cancelling_task_appointment_notifies_all_participants(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        Sanctum::actingAs($this->organizer);

        // Cancel the appointment
        $response = $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200);

        // Verify all participants were notified about cancellation (except organizer)
        Notification::assertSentTo($this->participant1, AppointmentCancellation::class);
        Notification::assertSentTo($this->participant2, AppointmentCancellation::class);
        Notification::assertSentTo($this->participant3, AppointmentCancellation::class);
    }

    public function test_cancelling_appointment_does_not_notify_the_user_who_cancelled(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        Sanctum::actingAs($this->organizer);

        // Cancel the appointment
        $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'cancelled',
        ]);

        // Verify organizer was NOT notified (they cancelled it)
        Notification::assertNotSentTo($this->organizer, AppointmentCancellation::class);
    }

    public function test_cancelling_project_appointment_notifies_all_participants(): void
    {
        // Create project appointment
        $appointment = Appointment::create([
            'title' => 'Project Meeting',
            'description' => 'Test description',
            'start_datetime' => now()->addDays(1),
            'end_datetime' => now()->addDays(1)->addHours(1),
            'type' => 'meeting',
            'visibility' => 'public',
            'status' => 'pending',
            'user_id' => $this->organizer->id,
            'appointmentable_type' => Project::class,
            'appointmentable_id' => $this->project->id,
        ]);

        // Add participants one by one to avoid SQL issues
        $appointment->participants()->attach($this->organizer->id, [
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment->participants()->attach($this->participant1->id, [
            'status' => 'pending',
            'confirmation_token' => Str::random(64),
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment->participants()->attach($this->participant2->id, [
            'status' => 'accepted',
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($this->organizer);

        // Cancel the appointment
        $response = $this->patchJson("/api/projects/{$this->project->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(200);

        // Verify all participants were notified
        Notification::assertSentTo($this->participant1, AppointmentCancellation::class);
        Notification::assertSentTo($this->participant2, AppointmentCancellation::class);
        Notification::assertNotSentTo($this->organizer, AppointmentCancellation::class);
    }

    // ========================================
    // Tests for Invalid Token Scenarios
    // ========================================

    public function test_confirming_with_invalid_token_shows_error(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        $response = $this->get(route('appointments.participant.confirm', [
            'appointment' => $appointment->uuid,
            'token' => 'invalid-token',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Appointments/ConfirmationError'));

        // No confirmation/cancellation notifications should be sent
        Notification::assertNotSentTo($this->participant1, AppointmentConfirmation::class);
        Notification::assertNotSentTo($this->participant1, AppointmentCancellation::class);
    }

    public function test_declining_with_invalid_token_shows_error(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        $response = $this->get(route('appointments.participant.decline', [
            'appointment' => $appointment->uuid,
            'token' => 'invalid-token',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Appointments/ConfirmationError'));

        // No confirmation/cancellation notifications should be sent
        Notification::assertNotSentTo($this->participant1, AppointmentConfirmation::class);
        Notification::assertNotSentTo($this->participant1, AppointmentCancellation::class);
    }

    // ========================================
    // Tests for Already Responded Scenarios
    // ========================================

    public function test_confirming_already_confirmed_shows_already_responded(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        // Get token and mark as already confirmed
        $token = $appointment->participants()
            ->wherePivot('user_id', $this->participant1->id)
            ->first()
            ->pivot
            ->confirmation_token;

        $appointment->participants()->updateExistingPivot($this->participant1->id, [
            'status' => 'accepted',
            'responded_at' => now(),
        ]);

        // Try to confirm again
        $response = $this->get(route('appointments.participant.confirm', [
            'appointment' => $appointment->uuid,
            'token' => $token,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Appointments/ConfirmationAlready'));

        // No confirmation/cancellation notifications should be sent for already-responded participant
        Notification::assertNotSentTo($this->participant1, AppointmentConfirmation::class);
        Notification::assertNotSentTo($this->organizer, AppointmentConfirmation::class);
    }

    public function test_declining_already_declined_shows_already_responded(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        // Get token and mark as already declined
        $token = $appointment->participants()
            ->wherePivot('user_id', $this->participant1->id)
            ->first()
            ->pivot
            ->confirmation_token;

        $appointment->participants()->updateExistingPivot($this->participant1->id, [
            'status' => 'declined',
            'responded_at' => now(),
        ]);

        // Try to decline again via GET (shows the already responded page)
        $response = $this->get(route('appointments.participant.decline', [
            'appointment' => $appointment->uuid,
            'token' => $token,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Appointments/ConfirmationAlready'));

        // No confirmation/cancellation notifications should be sent for already-responded participant
        Notification::assertNotSentTo($this->participant1, AppointmentConfirmation::class);
        Notification::assertNotSentTo($this->organizer, AppointmentConfirmation::class);
    }

    // ========================================
    // Count Verification Tests
    // ========================================

    public function test_correct_number_of_notifications_sent_on_confirm(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        $token = $appointment->participants()
            ->wherePivot('user_id', $this->participant1->id)
            ->first()
            ->pivot
            ->confirmation_token;

        $this->get(route('appointments.participant.confirm', [
            'appointment' => $appointment->uuid,
            'token' => $token,
        ]));

        // Should be 3 notifications: organizer + participant2 + participant3
        // (participant1 confirmed, so they don't receive a notification)
        Notification::assertSentToTimes($this->organizer, AppointmentConfirmation::class, 1);
        Notification::assertSentToTimes($this->participant2, AppointmentConfirmation::class, 1);
        Notification::assertSentToTimes($this->participant3, AppointmentConfirmation::class, 1);
    }

    public function test_correct_number_of_notifications_sent_on_cancellation(): void
    {
        $appointment = $this->createAppointmentWithParticipants();

        Sanctum::actingAs($this->organizer);

        $this->patchJson("/api/tasks/{$this->task->uuid}/appointments/{$appointment->uuid}", [
            'status' => 'cancelled',
        ]);

        // Should be 3 notifications to participant1, participant2, participant3
        // Organizer is the one who cancelled, so they don't receive a notification
        Notification::assertSentToTimes($this->participant1, AppointmentCancellation::class, 1);
        Notification::assertSentToTimes($this->participant2, AppointmentCancellation::class, 1);
        Notification::assertSentToTimes($this->participant3, AppointmentCancellation::class, 1);
    }
}
