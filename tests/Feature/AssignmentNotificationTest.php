<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Project;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use App\Notifications\DepartmentTodoAssigned;
use App\Notifications\ProjectManagerAssigned;
use App\Notifications\TaskAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $assignee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::firstOrCreate(['name' => 'view projects']);
        Permission::firstOrCreate(['name' => 'create projects']);
        Permission::firstOrCreate(['name' => 'edit projects']);
        Permission::firstOrCreate(['name' => 'delete projects']);
        Permission::firstOrCreate(['name' => 'manage tasks']);
        Permission::firstOrCreate(['name' => 'view departments']);
        Permission::firstOrCreate(['name' => 'edit departments']);

        // Create role with permissions
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo([
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
            'manage tasks',
            'view departments',
            'edit departments',
        ]);

        // Create users
        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
        ]);
        $this->admin->assignRole('admin');

        $this->assignee = User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
        ]);
        $this->assignee->assignRole('admin');
    }

    // ==========================================
    // Task Assignment Notification Tests
    // ==========================================

    /** @test */
    public function it_sends_notification_when_task_is_created_with_assignee(): void
    {
        Notification::fake();

        $status = Status::first() ?? Status::factory()->create();

        $this->actingAs($this->admin);

        Task::factory()->create([
            'title' => 'New Task',
            'assigned_to' => $this->assignee->id,
            'status_id' => $status->id,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            TaskAssigned::class,
            function ($notification) {
                return $notification->task->title === 'New Task'
                    && $notification->assignedBy->id === $this->admin->id;
            }
        );
    }

    /** @test */
    public function it_sends_notification_when_task_assignment_changes(): void
    {
        Notification::fake();

        $status = Status::first() ?? Status::factory()->create();

        $this->actingAs($this->admin);

        $task = Task::factory()->create([
            'title' => 'Existing Task',
            'assigned_to' => null,
            'status_id' => $status->id,
        ]);

        // Clear notifications from creation
        Notification::fake();

        $task->update(['assigned_to' => $this->assignee->id]);

        Notification::assertSentTo(
            $this->assignee,
            TaskAssigned::class,
            function ($notification) use ($task) {
                return $notification->task->id === $task->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notification_when_user_assigns_task_to_themselves(): void
    {
        Notification::fake();

        $status = Status::first() ?? Status::factory()->create();

        $this->actingAs($this->admin);

        Task::factory()->create([
            'title' => 'Self Assigned Task',
            'assigned_to' => $this->admin->id,
            'status_id' => $status->id,
        ]);

        Notification::assertNotSentTo($this->admin, TaskAssigned::class);
    }

    /** @test */
    public function task_assignment_notification_contains_correct_data(): void
    {
        $status = Status::first() ?? Status::factory()->create();
        $project = Project::factory()->create(['name' => 'Test Project']);

        $task = Task::factory()->create([
            'title' => 'Task with Project',
            'description' => 'Task description',
            'project_id' => $project->id,
            'priority' => 'high',
            'status_id' => $status->id,
            'assigned_to' => $this->assignee->id,
        ]);

        $notification = new TaskAssigned($task, $this->admin);

        // Check database data
        $dbData = $notification->toDatabase($this->assignee);
        $this->assertEquals('task_assigned', $dbData['type']);
        $this->assertEquals($task->id, $dbData['task_id']);
        $this->assertEquals($task->uuid, $dbData['task_uuid']);
        $this->assertEquals('Task with Project', $dbData['task_title']);
        $this->assertEquals($project->id, $dbData['project_id']);
        $this->assertEquals('Test Project', $dbData['project_name']);
        $this->assertEquals($this->admin->id, $dbData['assigned_by_id']);
        $this->assertStringContainsString('Admin User', $dbData['assigned_by_name']);

        // Check mail data
        $mailMessage = $notification->toMail($this->assignee);
        $this->assertStringContainsString('Task with Project', $mailMessage->subject);

        // Check channels
        $channels = $notification->via($this->assignee);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    /** @test */
    public function task_notification_shows_correct_priority_labels(): void
    {
        $status = Status::first() ?? Status::factory()->create();

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
                'status_id' => $status->id,
                'assigned_to' => $this->assignee->id,
            ]);

            $notification = new TaskAssigned($task, $this->admin);
            $mailContent = $notification->toMail($this->assignee)->render();

            $this->assertStringContainsString(
                $expectedLabel,
                $mailContent,
                "Priority '{$priority}' should be labeled as '{$expectedLabel}'"
            );
        }
    }

    // ==========================================
    // Project Manager Assignment Notification Tests
    // ==========================================

    /** @test */
    public function it_sends_notification_when_project_manager_is_assigned_on_creation(): void
    {
        Notification::fake();

        $this->actingAs($this->admin);

        Project::factory()->create([
            'name' => 'New Project',
            'project_manager_id' => $this->assignee->id,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            ProjectManagerAssigned::class,
            function ($notification) {
                return $notification->project->name === 'New Project'
                    && $notification->role === 'manager'
                    && $notification->assignedBy->id === $this->admin->id;
            }
        );
    }

    /** @test */
    public function it_sends_notification_when_project_manager_changes(): void
    {
        Notification::fake();

        $this->actingAs($this->admin);

        $project = Project::factory()->create([
            'name' => 'Existing Project',
            'project_manager_id' => null,
        ]);

        // Clear notifications from creation
        Notification::fake();

        $project->update(['project_manager_id' => $this->assignee->id]);

        Notification::assertSentTo(
            $this->assignee,
            ProjectManagerAssigned::class,
            function ($notification) {
                return $notification->role === 'manager';
            }
        );
    }

    /** @test */
    public function it_sends_notification_when_reviewer_is_assigned(): void
    {
        Notification::fake();

        $this->actingAs($this->admin);

        Project::factory()->create([
            'name' => 'Project with Reviewer',
            'reviewer_id' => $this->assignee->id,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            ProjectManagerAssigned::class,
            function ($notification) {
                return $notification->role === 'reviewer';
            }
        );
    }

    /** @test */
    public function it_sends_both_notifications_when_manager_and_reviewer_are_different_users(): void
    {
        Notification::fake();

        $reviewer = User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Martin',
        ]);

        $this->actingAs($this->admin);

        Project::factory()->create([
            'name' => 'Project with Both Roles',
            'project_manager_id' => $this->assignee->id,
            'reviewer_id' => $reviewer->id,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            ProjectManagerAssigned::class,
            fn ($notification) => $notification->role === 'manager'
        );

        Notification::assertSentTo(
            $reviewer,
            ProjectManagerAssigned::class,
            fn ($notification) => $notification->role === 'reviewer'
        );
    }

    /** @test */
    public function it_does_not_send_notification_when_user_assigns_themselves_as_project_manager(): void
    {
        Notification::fake();

        $this->actingAs($this->admin);

        Project::factory()->create([
            'name' => 'Self Managed Project',
            'project_manager_id' => $this->admin->id,
        ]);

        Notification::assertNotSentTo($this->admin, ProjectManagerAssigned::class);
    }

    /** @test */
    public function project_manager_notification_contains_correct_data(): void
    {
        $project = Project::factory()->create([
            'name' => 'Test Project',
            'description' => 'Project description',
            'project_manager_id' => $this->assignee->id,
        ]);

        $notification = new ProjectManagerAssigned($project, 'manager', $this->admin);

        // Check database data
        $dbData = $notification->toDatabase($this->assignee);
        $this->assertEquals('project_manager_assigned', $dbData['type']);
        $this->assertEquals($project->id, $dbData['project_id']);
        $this->assertEquals($project->uuid, $dbData['project_uuid']);
        $this->assertEquals('Test Project', $dbData['project_name']);
        $this->assertEquals('manager', $dbData['role']);
        $this->assertEquals('Chef de projet', $dbData['role_label']);
        $this->assertEquals($this->admin->id, $dbData['assigned_by_id']);

        // Check mail data
        $mailMessage = $notification->toMail($this->assignee);
        $this->assertStringContainsString('Test Project', $mailMessage->subject);
        $this->assertStringContainsString('Chef de projet', $mailMessage->subject);

        // Check channels
        $channels = $notification->via($this->assignee);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    /** @test */
    public function project_notification_shows_correct_role_labels(): void
    {
        $project = Project::factory()->create();

        $roles = [
            'manager' => 'Chef de projet',
            'reviewer' => 'Réviseur',
        ];

        foreach ($roles as $role => $expectedLabel) {
            $notification = new ProjectManagerAssigned($project, $role, $this->admin);
            $dbData = $notification->toDatabase($this->assignee);

            $this->assertEquals(
                $expectedLabel,
                $dbData['role_label'],
                "Role '{$role}' should be labeled as '{$expectedLabel}'"
            );
        }
    }

    // ==========================================
    // Department Todo Assignment Notification Tests
    // ==========================================

    /** @test */
    public function it_sends_notification_when_department_todo_is_created_with_assignee(): void
    {
        Notification::fake();

        $department = Department::factory()->create(['name' => 'IT Department']);

        $this->actingAs($this->admin);

        DepartmentTodo::factory()->create([
            'title' => 'New Todo',
            'department_id' => $department->id,
            'assigned_to' => $this->assignee->id,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            DepartmentTodoAssigned::class,
            function ($notification) {
                return $notification->todo->title === 'New Todo'
                    && $notification->assignedBy->id === $this->admin->id;
            }
        );
    }

    /** @test */
    public function it_sends_notification_when_department_todo_assignment_changes(): void
    {
        Notification::fake();

        $department = Department::factory()->create();

        $this->actingAs($this->admin);

        $todo = DepartmentTodo::factory()->create([
            'title' => 'Existing Todo',
            'department_id' => $department->id,
            'assigned_to' => null,
        ]);

        // Clear notifications from creation
        Notification::fake();

        $todo->update(['assigned_to' => $this->assignee->id]);

        Notification::assertSentTo(
            $this->assignee,
            DepartmentTodoAssigned::class,
            function ($notification) use ($todo) {
                return $notification->todo->id === $todo->id;
            }
        );
    }

    /** @test */
    public function it_does_not_send_notification_when_user_assigns_todo_to_themselves(): void
    {
        Notification::fake();

        $department = Department::factory()->create();

        $this->actingAs($this->admin);

        DepartmentTodo::factory()->create([
            'title' => 'Self Assigned Todo',
            'department_id' => $department->id,
            'assigned_to' => $this->admin->id,
        ]);

        Notification::assertNotSentTo($this->admin, DepartmentTodoAssigned::class);
    }

    /** @test */
    public function department_todo_notification_contains_correct_data(): void
    {
        $department = Department::factory()->create(['name' => 'HR Department']);

        $todo = DepartmentTodo::factory()->create([
            'title' => 'Review Applications',
            'description' => 'Review pending job applications',
            'department_id' => $department->id,
            'assigned_to' => $this->assignee->id,
        ]);

        $notification = new DepartmentTodoAssigned($todo, $this->admin);

        // Check database data
        $dbData = $notification->toDatabase($this->assignee);
        $this->assertEquals('department_todo_assigned', $dbData['type']);
        $this->assertEquals($todo->id, $dbData['todo_id']);
        $this->assertEquals($todo->uuid, $dbData['todo_uuid']);
        $this->assertEquals('Review Applications', $dbData['todo_title']);
        $this->assertEquals($department->id, $dbData['department_id']);
        $this->assertEquals('HR Department', $dbData['department_name']);
        $this->assertEquals($this->admin->id, $dbData['assigned_by_id']);
        $this->assertStringContainsString('Admin User', $dbData['assigned_by_name']);

        // Check mail data
        $mailMessage = $notification->toMail($this->assignee);
        $this->assertStringContainsString('Review Applications', $mailMessage->subject);

        // Check channels
        $channels = $notification->via($this->assignee);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    /** @test */
    public function department_todo_notification_works_without_authenticated_user(): void
    {
        Notification::fake();

        $department = Department::factory()->create();

        // No authenticated user - system assignment
        DepartmentTodo::factory()->create([
            'title' => 'System Assigned Todo',
            'department_id' => $department->id,
            'assigned_to' => $this->assignee->id,
        ]);

        Notification::assertSentTo(
            $this->assignee,
            DepartmentTodoAssigned::class,
            function ($notification) {
                return $notification->assignedBy === null;
            }
        );
    }

    // ==========================================
    // Edge Cases Tests
    // ==========================================

    /** @test */
    public function it_does_not_send_notification_when_assignment_is_removed(): void
    {
        Notification::fake();

        $status = Status::first() ?? Status::factory()->create();

        $this->actingAs($this->admin);

        $task = Task::factory()->create([
            'title' => 'Task to unassign',
            'assigned_to' => $this->assignee->id,
            'status_id' => $status->id,
        ]);

        // Clear notifications from creation
        Notification::fake();

        // Remove assignment
        $task->update(['assigned_to' => null]);

        Notification::assertNotSentTo($this->assignee, TaskAssigned::class);
    }

    /** @test */
    public function it_sends_notification_when_assignment_changes_to_different_user(): void
    {
        $status = Status::first() ?? Status::factory()->create();
        $newAssignee = User::factory()->create();

        $this->actingAs($this->admin);

        // Create task without assignment (to avoid notification on creation)
        $task = Task::factory()->create([
            'title' => 'Task to reassign',
            'assigned_to' => null,
            'status_id' => $status->id,
        ]);

        // Now fake notifications to capture the assignment change
        Notification::fake();

        // Change assignment to new user
        $task->update(['assigned_to' => $newAssignee->id]);

        // Should notify new assignee
        Notification::assertSentTo($newAssignee, TaskAssigned::class);
    }
}
