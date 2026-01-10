<?php

namespace Tests\Feature\Api;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ProjectParticipantAdded;
use App\Notifications\TaskParticipantAdded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $participant;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => 'view projects']);
        Permission::firstOrCreate(['name' => 'create projects']);
        Permission::firstOrCreate(['name' => 'edit projects']);
        Permission::firstOrCreate(['name' => 'delete projects']);
        Permission::firstOrCreate(['name' => 'manage tasks']);

        // Create role with permissions
        $role = Role::firstOrCreate(['name' => 'project-manager']);
        $role->givePermissionTo(['view projects', 'create projects', 'edit projects', 'delete projects', 'manage tasks']);

        // Create users
        $this->user = User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
        $this->user->assignRole('project-manager');

        $this->participant = User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Martin',
        ]);
        $this->participant->assignRole('project-manager');

        // Create project
        $this->project = Project::factory()->create([
            'name' => 'Test Project',
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

    // ==========================================
    // Project Participant Notification Tests
    // ==========================================

    /** @test */
    public function it_sends_notification_when_adding_participant_to_project()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/projects/{$this->project->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'member',
        ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            $this->participant,
            ProjectParticipantAdded::class,
            function ($notification) {
                return $notification->project->id === $this->project->id
                    && $notification->role === 'member'
                    && $notification->addedBy->id === $this->user->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notification_when_user_adds_themselves_to_project()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/projects/{$this->project->uuid}/participants", [
            'user_id' => $this->user->id,
            'role' => 'member',
        ]);

        $response->assertStatus(201);

        Notification::assertNotSentTo($this->user, ProjectParticipantAdded::class);
    }

    /** @test */
    public function it_does_not_send_notification_when_updating_existing_participant_role()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        // First add the participant
        $this->postJson("/api/projects/{$this->project->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'member',
        ]);

        Notification::assertSentToTimes($this->participant, ProjectParticipantAdded::class, 1);

        // Now update the role
        $response = $this->postJson("/api/projects/{$this->project->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'contributor',
        ]);

        $response->assertStatus(201);

        // Should still be 1 notification (not 2)
        Notification::assertSentToTimes($this->participant, ProjectParticipantAdded::class, 1);
    }

    /** @test */
    public function project_participant_notification_contains_correct_data()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $this->postJson("/api/projects/{$this->project->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'contributor',
        ]);

        Notification::assertSentTo(
            $this->participant,
            ProjectParticipantAdded::class,
            function ($notification, $channels) {
                // Check channels
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);

                // Check notification data
                $dbData = $notification->toDatabase($this->participant);
                $this->assertEquals('project_participant_added', $dbData['type']);
                $this->assertEquals($this->project->id, $dbData['project_id']);
                $this->assertEquals($this->project->uuid, $dbData['project_uuid']);
                $this->assertEquals($this->project->name, $dbData['project_name']);
                $this->assertEquals('contributor', $dbData['role']);
                $this->assertEquals('Contributeur', $dbData['role_label']);
                $this->assertEquals($this->user->id, $dbData['added_by_id']);
                $this->assertStringContainsString('Jean Dupont', $dbData['added_by_name']);

                return true;
            }
        );
    }

    /** @test */
    public function project_participant_notification_mail_has_correct_subject()
    {
        $notification = new ProjectParticipantAdded($this->project, 'member', $this->user);
        $mailMessage = $notification->toMail($this->participant);

        $this->assertStringContainsString($this->project->name, $mailMessage->subject);
    }

    // ==========================================
    // Task Participant Notification Tests
    // ==========================================

    /** @test */
    public function it_sends_notification_when_adding_participant_to_task()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'assignee',
        ]);

        $response->assertStatus(201);

        Notification::assertSentTo(
            $this->participant,
            TaskParticipantAdded::class,
            function ($notification) {
                return $notification->task->id === $this->task->id
                    && $notification->role === 'assignee'
                    && $notification->addedBy->id === $this->user->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notification_when_user_adds_themselves_to_task()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/tasks/{$this->task->uuid}/participants", [
            'user_id' => $this->user->id,
            'role' => 'assignee',
        ]);

        $response->assertStatus(201);

        Notification::assertNotSentTo($this->user, TaskParticipantAdded::class);
    }

    /** @test */
    public function it_does_not_send_duplicate_notification_for_existing_task_participant()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        // First add the participant
        $this->postJson("/api/tasks/{$this->task->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'assignee',
        ]);

        // Try to add again (should fail with 422)
        $response = $this->postJson("/api/tasks/{$this->task->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'reviewer',
        ]);

        $response->assertStatus(422);

        // Should only have 1 notification
        Notification::assertSentToTimes($this->participant, TaskParticipantAdded::class, 1);
    }

    /** @test */
    public function task_participant_notification_contains_correct_data()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $this->postJson("/api/tasks/{$this->task->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'reviewer',
        ]);

        Notification::assertSentTo(
            $this->participant,
            TaskParticipantAdded::class,
            function ($notification, $channels) {
                // Check channels
                $this->assertContains('mail', $channels);
                $this->assertContains('database', $channels);

                // Check notification data
                $dbData = $notification->toDatabase($this->participant);
                $this->assertEquals('task_participant_added', $dbData['type']);
                $this->assertEquals($this->task->id, $dbData['task_id']);
                $this->assertEquals($this->task->uuid, $dbData['task_uuid']);
                $this->assertEquals($this->task->title, $dbData['task_title']);
                $this->assertEquals('reviewer', $dbData['role']);
                $this->assertEquals('Réviseur', $dbData['role_label']);
                $this->assertEquals($this->user->id, $dbData['added_by_id']);
                $this->assertStringContainsString('Jean Dupont', $dbData['added_by_name']);

                return true;
            }
        );
    }

    /** @test */
    public function task_participant_notification_mail_has_correct_subject()
    {
        $notification = new TaskParticipantAdded($this->task, 'assignee', $this->user);
        $mailMessage = $notification->toMail($this->participant);

        $this->assertStringContainsString($this->task->title, $mailMessage->subject);
    }

    /** @test */
    public function task_notification_includes_project_info_when_available()
    {
        Notification::fake();
        Sanctum::actingAs($this->user);

        $this->postJson("/api/tasks/{$this->task->uuid}/participants", [
            'user_id' => $this->participant->id,
            'role' => 'collaborator',
        ]);

        Notification::assertSentTo(
            $this->participant,
            TaskParticipantAdded::class,
            function ($notification) {
                $dbData = $notification->toDatabase($this->participant);
                $this->assertEquals($this->project->id, $dbData['project_id']);
                $this->assertEquals($this->project->name, $dbData['project_name']);

                return true;
            }
        );
    }

    // ==========================================
    // Role Label Tests
    // ==========================================

    /** @test */
    public function project_notification_translates_all_role_labels_correctly()
    {
        $testCases = [
            'member' => 'Membre',
            'contributor' => 'Contributeur',
            'observer' => 'Observateur',
            'lead' => 'Responsable',
            'custom_role' => 'Custom_role',
        ];

        foreach ($testCases as $role => $expectedLabel) {
            $notification = new ProjectParticipantAdded($this->project, $role, $this->user);
            $dbData = $notification->toDatabase($this->participant);
            $this->assertEquals($expectedLabel, $dbData['role_label'], "Role '$role' should be translated to '$expectedLabel'");
        }
    }

    /** @test */
    public function task_notification_translates_all_role_labels_correctly()
    {
        $testCases = [
            'assignee' => 'Assigné',
            'reviewer' => 'Réviseur',
            'collaborator' => 'Collaborateur',
            'observer' => 'Observateur',
            'custom_role' => 'Custom_role',
        ];

        foreach ($testCases as $role => $expectedLabel) {
            $notification = new TaskParticipantAdded($this->task, $role, $this->user);
            $dbData = $notification->toDatabase($this->participant);
            $this->assertEquals($expectedLabel, $dbData['role_label'], "Role '$role' should be translated to '$expectedLabel'");
        }
    }
}
