<?php

use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskCreated;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    // Create permissions
    Permission::firstOrCreate(['name' => 'view tasks']);
    Permission::firstOrCreate(['name' => 'create tasks']);
    Permission::firstOrCreate(['name' => 'edit tasks']);
    Permission::firstOrCreate(['name' => 'delete tasks']);
    Permission::firstOrCreate(['name' => 'view projects']);
    Permission::firstOrCreate(['name' => 'create projects']);
    Permission::firstOrCreate(['name' => 'edit projects']);

    // Create role with permissions
    $role = Role::firstOrCreate(['name' => 'admin']);
    $role->givePermissionTo([
        'view tasks',
        'create tasks',
        'edit tasks',
        'delete tasks',
        'view projects',
        'create projects',
        'edit projects',
    ]);

    $this->status = Status::first() ?? Status::factory()->create(['name' => 'pending']);

    $this->creator = User::factory()->create([
        'first_name' => 'Creator',
        'last_name' => 'User',
    ]);
    $this->creator->assignRole('admin');

    $this->projectManager = User::factory()->create([
        'first_name' => 'Manager',
        'last_name' => 'User',
    ]);

    $this->projectReviewer = User::factory()->create([
        'first_name' => 'Reviewer',
        'last_name' => 'User',
    ]);

    $this->participant1 = User::factory()->create([
        'first_name' => 'Participant',
        'last_name' => 'One',
    ]);

    $this->participant2 = User::factory()->create([
        'first_name' => 'Participant',
        'last_name' => 'Two',
    ]);

    $this->project = Project::factory()->create([
        'name' => 'Test Project',
        'project_manager_id' => $this->projectManager->id,
        'reviewer_id' => $this->projectReviewer->id,
    ]);

    // Add participants to the project
    ProjectParticipant::create([
        'project_id' => $this->project->id,
        'user_id' => $this->participant1->id,
        'role' => 'member',
    ]);

    ProjectParticipant::create([
        'project_id' => $this->project->id,
        'user_id' => $this->participant2->id,
        'role' => 'contributor',
    ]);
});

// ==========================================
// Task Creation Notification Tests
// ==========================================

it('sends notifications to all project members when a task is created for a project', function (): void {
    Notification::fake();

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'New Task for Project',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ];

    $response = $this->post(route('tasks.store'), $taskData);

    $response->assertRedirect(route('tasks.index'));

    // Check that notifications were sent to project manager
    Notification::assertSentTo(
        $this->projectManager,
        TaskCreated::class,
        fn ($notification): bool => $notification->task->title === 'New Task for Project'
            && $notification->project->id === $this->project->id
            && $notification->createdBy->id === $this->creator->id
    );

    // Check that notifications were sent to reviewer
    Notification::assertSentTo(
        $this->projectReviewer,
        TaskCreated::class
    );

    // Check that notifications were sent to participants
    Notification::assertSentTo($this->participant1, TaskCreated::class);
    Notification::assertSentTo($this->participant2, TaskCreated::class);
});

it('does not send notification to the user who created the task', function (): void {
    Notification::fake();

    // Add creator as a participant to the project
    ProjectParticipant::create([
        'project_id' => $this->project->id,
        'user_id' => $this->creator->id,
        'role' => 'member',
    ]);

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task Created by Participant',
        'description' => 'This is a task description with enough characters',
        'priority' => 'high',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // Creator should NOT receive a notification
    Notification::assertNotSentTo($this->creator, TaskCreated::class);

    // But other participants should
    Notification::assertSentTo($this->projectManager, TaskCreated::class);
    Notification::assertSentTo($this->participant1, TaskCreated::class);
});

it('does not send notifications when task is not associated with a project', function (): void {
    Notification::fake();

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task without Project',
        'description' => 'This is a task description with enough characters',
        'priority' => 'low',
        'status_id' => $this->status->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // No notifications should be sent
    Notification::assertNothingSent();
});

it('does not send notifications when task is associated with a program', function (): void {
    Notification::fake();

    $program = \App\Models\Program::factory()->create(['user_id' => $this->creator->id]);

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task for Program',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Program::class,
        'taskable_id' => $program->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // No TaskCreated notifications should be sent (programs don't have this feature)
    Notification::assertNotSentTo($this->projectManager, TaskCreated::class);
});

it('sends notification only once when user has multiple roles in project', function (): void {
    Notification::fake();

    // Make project manager also a participant
    ProjectParticipant::create([
        'project_id' => $this->project->id,
        'user_id' => $this->projectManager->id,
        'role' => 'lead',
    ]);

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task for Multi-Role User',
        'description' => 'This is a task description with enough characters',
        'priority' => 'high',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // Project manager should receive exactly one notification
    Notification::assertSentToTimes($this->projectManager, TaskCreated::class, 1);
});

it('sends notifications to members added via members relationship', function (): void {
    Notification::fake();

    // Add a member via the members relationship (project_members table)
    $member = User::factory()->create([
        'first_name' => 'Team',
        'last_name' => 'Member',
    ]);
    $this->project->members()->attach($member->id, ['is_lead' => false]);

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task for Team Member',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // Member should receive notification
    Notification::assertSentTo($member, TaskCreated::class);
});

it('handles project without manager or reviewer gracefully', function (): void {
    Notification::fake();

    // Create project without manager or reviewer
    $projectWithoutRoles = Project::factory()->create([
        'name' => 'Project Without Roles',
        'project_manager_id' => null,
        'reviewer_id' => null,
    ]);

    // Add a participant
    ProjectParticipant::create([
        'project_id' => $projectWithoutRoles->id,
        'user_id' => $this->participant1->id,
        'role' => 'member',
    ]);

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task for Project Without Roles',
        'description' => 'This is a task description with enough characters',
        'priority' => 'low',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $projectWithoutRoles->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // Participant should still receive notification
    Notification::assertSentTo($this->participant1, TaskCreated::class);
});

it('does not send notifications when project has no members', function (): void {
    Notification::fake();

    // Create project without any members
    $emptyProject = Project::factory()->create([
        'name' => 'Empty Project',
        'project_manager_id' => null,
        'reviewer_id' => null,
    ]);

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task for Empty Project',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $emptyProject->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // No notifications should be sent
    Notification::assertNothingSent();
});

// ==========================================
// Notification Content Tests
// ==========================================

it('notification contains correct data', function (): void {
    $task = Task::factory()->create([
        'title' => 'Test Task Title',
        'description' => 'Task description content',
        'priority' => 'high',
        'due_date' => now()->addDays(7),
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ]);

    $notification = new TaskCreated($task, $this->project, $this->creator);

    // Check database data
    $dbData = $notification->toDatabase($this->participant1);

    expect($dbData['type'])->toBe('task_created');
    expect($dbData['task_id'])->toBe($task->id);
    expect($dbData['task_uuid'])->toBe($task->uuid);
    expect($dbData['task_title'])->toBe('Test Task Title');
    expect($dbData['project_id'])->toBe($this->project->id);
    expect($dbData['project_uuid'])->toBe($this->project->uuid);
    expect($dbData['project_name'])->toBe('Test Project');
    expect($dbData['created_by_id'])->toBe($this->creator->id);
    expect($dbData['created_by_name'])->toContain('Creator User');
    expect($dbData['priority'])->toBe('high');
    expect($dbData['action_url'])->toBe(route('tasks.show', $task->uuid));
});

it('notification mail has correct subject', function (): void {
    $task = Task::factory()->create([
        'title' => 'Test Task',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ]);

    $notification = new TaskCreated($task, $this->project, $this->creator);
    $mailMessage = $notification->toMail($this->participant1);

    expect($mailMessage->subject)->toContain('Test Project');
    expect($mailMessage->subject)->toContain('Nouvelle tâche ajoutée au projet');
});

it('notification uses both mail and database channels', function (): void {
    $task = Task::factory()->create([
        'title' => 'Test Task',
        'status_id' => $this->status->id,
    ]);

    $notification = new TaskCreated($task, $this->project, $this->creator);
    $channels = $notification->via($this->participant1);

    expect($channels)->toContain('mail');
    expect($channels)->toContain('database');
});

it('notification shows correct priority labels', function (): void {
    $priorities = [
        'lowest' => 'Très basse',
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'highest' => 'Très haute',
    ];

    foreach ($priorities as $priority => $expectedLabel) {
        $task = Task::factory()->create([
            'title' => "Priority {$priority} Task",
            'priority' => $priority,
            'status_id' => $this->status->id,
        ]);

        $notification = new TaskCreated($task, $this->project, $this->creator);
        $mailContent = $notification->toMail($this->participant1)->render();

        $this->assertStringContainsString(
            $expectedLabel,
            $mailContent,
            "Priority '{$priority}' should be labeled as '{$expectedLabel}'"
        );
    }
});

it('notification works without creator (system notification)', function (): void {
    $task = Task::factory()->create([
        'title' => 'System Task',
        'status_id' => $this->status->id,
    ]);

    $notification = new TaskCreated($task, $this->project);
    $dbData = $notification->toDatabase($this->participant1);

    expect($dbData['created_by_id'])->toBeNull();
    expect($dbData['created_by_name'])->toBe('Le système');
});

it('notification includes assignee info when task is assigned', function (): void {
    $assignee = User::factory()->create([
        'first_name' => 'Assigned',
        'last_name' => 'Person',
    ]);

    $task = Task::factory()->create([
        'title' => 'Assigned Task',
        'status_id' => $this->status->id,
        'assigned_to' => $assignee->id,
    ]);
    $task->load('assignee');

    $notification = new TaskCreated($task, $this->project, $this->creator);
    $mailContent = $notification->toMail($this->participant1)->render();

    $this->assertStringContainsString('Assigned Person', $mailContent);
});

// ==========================================
// Project getAllNotifiableUsers Tests
// ==========================================

it('getAllNotifiableUsers returns all project users', function (): void {
    $users = $this->project->getAllNotifiableUsers();

    expect($users)->toHaveCount(4); // manager, reviewer, 2 participants
    expect($users->pluck('id'))->toContain($this->projectManager->id);
    expect($users->pluck('id'))->toContain($this->projectReviewer->id);
    expect($users->pluck('id'))->toContain($this->participant1->id);
    expect($users->pluck('id'))->toContain($this->participant2->id);
});

it('getAllNotifiableUsers excludes specified user', function (): void {
    $users = $this->project->getAllNotifiableUsers($this->projectManager->id);

    expect($users)->toHaveCount(3); // reviewer, 2 participants
    expect($users->pluck('id'))->not->toContain($this->projectManager->id);
    expect($users->pluck('id'))->toContain($this->projectReviewer->id);
});

it('getAllNotifiableUsers removes duplicates', function (): void {
    // Add project manager also as a participant
    ProjectParticipant::create([
        'project_id' => $this->project->id,
        'user_id' => $this->projectManager->id,
        'role' => 'lead',
    ]);

    $users = $this->project->getAllNotifiableUsers();

    // Should still be 4 users (no duplicates)
    expect($users)->toHaveCount(4);

    // Manager should appear only once
    $managerCount = $users->filter(fn ($user): bool => $user->id === $this->projectManager->id)->count();
    expect($managerCount)->toBe(1);
});

it('getAllNotifiableUsers includes members from project_members table', function (): void {
    $member = User::factory()->create();
    $this->project->members()->attach($member->id, ['is_lead' => true]);

    $users = $this->project->getAllNotifiableUsers();

    expect($users->pluck('id'))->toContain($member->id);
});

// ==========================================
// Integration Tests
// ==========================================

it('creates task and sends notification in same transaction', function (): void {
    Notification::fake();

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Transaction Task',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // Task should exist in database
    $this->assertDatabaseHas('tasks', [
        'title' => 'Transaction Task',
    ]);

    // Notification should be sent with correct task data
    Notification::assertSentTo(
        $this->projectManager,
        TaskCreated::class,
        fn ($notification): bool => $notification->task->title === 'Transaction Task'
    );
});

it('uses project_id backward compatibility for notifications', function (): void {
    Notification::fake();

    $this->actingAs($this->creator);

    // Using project_id instead of taskable_type/taskable_id
    $taskData = [
        'title' => 'Backward Compatible Task',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'project_id' => $this->project->id,
    ];

    $this->post(route('tasks.store'), $taskData);

    // Notification should still be sent
    Notification::assertSentTo($this->projectManager, TaskCreated::class);
});

it('redirects to project when from_project flag is set', function (): void {
    Notification::fake();

    $this->actingAs($this->creator);

    $taskData = [
        'title' => 'Task from Project Page',
        'description' => 'This is a task description with enough characters',
        'priority' => 'medium',
        'status_id' => $this->status->id,
        'taskable_type' => \App\Models\Project::class,
        'taskable_id' => $this->project->id,
        'from_project' => true,
    ];

    $response = $this->post(route('tasks.store'), $taskData);

    $response->assertRedirect(route('projects.show', $this->project->uuid));

    // Notifications should still be sent
    Notification::assertSentTo($this->projectManager, TaskCreated::class);
});
