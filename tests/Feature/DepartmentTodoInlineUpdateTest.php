<?php

namespace Tests\Feature;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTodoInlineUpdateTest extends TestCase
{
    use RefreshDatabase;

    public User $user;

    public Department $department;

    public DepartmentTodo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('manage departments');

        $this->department = Department::factory()->create();
        $this->department->users()->attach($this->user);

        $this->todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->user->id,
            'title' => 'Original Title',
            'description' => 'Original description',
            'priority' => 'medium',
            'status' => ShiftTaskStatus::TODO,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ]);
    }

    protected function inlineUpdateUrl(): string
    {
        return "/departments/{$this->department->uuid}/todos/{$this->todo->uuid}/inline-update";
    }

    public function test_inline_update_title(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'title',
                'value' => 'Updated Title',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('todo.title', 'Updated Title');

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_inline_update_description(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'description',
                'value' => 'Updated description text',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'description' => 'Updated description text',
        ]);
    }

    public function test_inline_update_description_can_be_null(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'description',
                'value' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'description' => null,
        ]);
    }

    public function test_inline_update_priority(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'priority',
                'value' => 'urgent',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('todo.priority', 'urgent');

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'priority' => 'urgent',
        ]);
    }

    public function test_inline_update_all_priority_values(): void
    {
        foreach (['low', 'medium', 'high', 'urgent'] as $priority) {
            $response = $this->actingAs($this->user)
                ->patchJson($this->inlineUpdateUrl(), [
                    'field' => 'priority',
                    'value' => $priority,
                ]);

            $response->assertOk();
            $response->assertJsonPath('todo.priority', $priority);
        }
    }

    public function test_inline_update_status_to_in_progress(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'status',
                'value' => 'in_progress',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('todo.status', 'in_progress');

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_inline_update_status_to_completed_sets_metadata(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'status',
                'value' => 'completed',
            ]);

        $response->assertOk();
        $response->assertJsonPath('todo.status', 'completed');

        $this->todo->refresh();
        $this->assertEquals(ShiftTaskStatus::COMPLETED, $this->todo->status);
        $this->assertNotNull($this->todo->completed_at);
        $this->assertEquals($this->user->id, $this->todo->completed_by);
    }

    public function test_inline_update_status_to_blocked(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'status',
                'value' => 'blocked',
            ]);

        $response->assertOk();
        $response->assertJsonPath('todo.status', 'blocked');
    }

    public function test_inline_update_status_to_cancelled(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'status',
                'value' => 'cancelled',
            ]);

        $response->assertOk();
        $response->assertJsonPath('todo.status', 'cancelled');
    }

    public function test_inline_update_status_reopen_from_completed(): void
    {
        $this->todo->complete($this->user);

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'status',
                'value' => 'todo',
            ]);

        $response->assertOk();
        $response->assertJsonPath('todo.status', 'todo');

        $this->todo->refresh();
        $this->assertNull($this->todo->completed_at);
        $this->assertNull($this->todo->completed_by);
    }

    public function test_inline_update_due_date(): void
    {
        $newDate = now()->addDays(14)->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'due_date',
                'value' => $newDate,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->todo->refresh();
        $this->assertEquals($newDate, $this->todo->due_date->format('Y-m-d'));
    }

    public function test_inline_update_due_date_can_be_null(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'due_date',
                'value' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'due_date' => null,
        ]);
    }

    public function test_inline_update_assigned_to(): void
    {
        $otherUser = User::factory()->create();
        $this->department->users()->attach($otherUser);

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'assigned_to',
                'value' => $otherUser->uuid,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('todo.assignee.uuid', (string) $otherUser->uuid);

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'assigned_to' => $otherUser->id,
        ]);
    }

    public function test_inline_update_assigned_to_can_be_null(): void
    {
        $this->todo->update(['assigned_to' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'assigned_to',
                'value' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'assigned_to' => null,
        ]);
    }

    public function test_inline_update_backup_assignees(): void
    {
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();
        $this->department->users()->attach([$user2->id, $user3->id]);

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'backup_assignees',
                'value' => [$user2->uuid, $user3->uuid],
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->todo->refresh();
        $this->assertCount(2, $this->todo->backup_assignees);
        $this->assertContains($user2->id, $this->todo->backup_assignees);
        $this->assertContains($user3->id, $this->todo->backup_assignees);
    }

    public function test_inline_update_backup_assignees_can_be_null(): void
    {
        $user2 = User::factory()->create();
        $this->todo->update(['backup_assignees' => [$user2->id]]);

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'backup_assignees',
                'value' => null,
            ]);

        $response->assertOk();

        $this->todo->refresh();
        $this->assertNull($this->todo->backup_assignees);
    }

    public function test_inline_update_backup_assignees_rejects_invalid_uuid(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'backup_assignees',
                'value' => ['nonexistent-uuid'],
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_estimated_minutes(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'estimated_minutes',
                'value' => 120,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'estimated_minutes' => 120,
        ]);
    }

    public function test_inline_update_estimated_minutes_can_be_null(): void
    {
        $this->todo->update(['estimated_minutes' => 60]);

        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'estimated_minutes',
                'value' => null,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('department_todos', [
            'id' => $this->todo->id,
            'estimated_minutes' => null,
        ]);
    }

    public function test_inline_update_rejects_disallowed_field(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'sort_order',
                'value' => 999,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonPath('message', 'Field not allowed.');
    }

    public function test_inline_update_rejects_invalid_priority(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'priority',
                'value' => 'invalid_priority',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_invalid_status(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'status',
                'value' => 'nonexistent_status',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_empty_title(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'title',
                'value' => '',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_title_too_long(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'title',
                'value' => str_repeat('A', 256),
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_invalid_date_format(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'due_date',
                'value' => 'not-a-date',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_invalid_assigned_to(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'assigned_to',
                'value' => 'nonexistent-uuid',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_rejects_invalid_estimated_minutes(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'estimated_minutes',
                'value' => 0,
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_requires_authentication(): void
    {
        $response = $this->patchJson($this->inlineUpdateUrl(), [
            'field' => 'title',
            'value' => 'Should Not Work',
        ]);

        $response->assertUnauthorized();
    }

    public function test_inline_update_requires_authorization(): void
    {
        $outsideUser = User::factory()->create();
        // User is NOT a member of the department and has no 'manage departments' permission

        $response = $this->actingAs($outsideUser)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'title',
                'value' => 'Should Not Work',
            ]);

        $response->assertForbidden();
    }

    public function test_inline_update_rejects_todo_from_different_department(): void
    {
        $otherDepartment = Department::factory()->create();
        $otherTodo = DepartmentTodo::factory()->create([
            'department_id' => $otherDepartment->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/departments/{$this->department->uuid}/todos/{$otherTodo->uuid}/inline-update", [
                'field' => 'title',
                'value' => 'Should Not Work',
            ]);

        $response->assertNotFound();
    }

    public function test_inline_update_nonexistent_todo(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson("/departments/{$this->department->uuid}/todos/nonexistent-uuid/inline-update", [
                'field' => 'title',
                'value' => 'Test',
            ]);

        $response->assertNotFound();
    }

    public function test_inline_update_returns_todo_structure(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'title',
                'value' => 'New Title',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'todo' => [
                'uuid',
                'title',
                'status',
                'priority',
                'status_label',
                'priority_label',
            ],
        ]);
    }

    public function test_inline_update_requires_field_parameter(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'value' => 'Some value',
            ]);

        $response->assertUnprocessable();
    }

    public function test_inline_update_requires_value_parameter(): void
    {
        $response = $this->actingAs($this->user)
            ->patchJson($this->inlineUpdateUrl(), [
                'field' => 'title',
            ]);

        $response->assertUnprocessable();
    }
}
