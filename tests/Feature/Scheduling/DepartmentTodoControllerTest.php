<?php

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\TodoPriority;
use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\Scheduling\Shift;
use App\Models\Scheduling\WeeklySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create permissions and roles
    Permission::firstOrCreate(['name' => 'view departments']);
    Permission::firstOrCreate(['name' => 'manage departments']);

    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $adminRole->givePermissionTo(['view departments', 'manage departments']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->department = Department::factory()->create();
    $this->department->members()->attach($this->admin);

    $this->schedule = WeeklySchedule::factory()->create([
        'department_id' => $this->department->id,
        'week_start' => now()->startOfWeek(),
        'week_end' => now()->endOfWeek(),
        'status' => 'draft',
    ]);

    $this->shift = Shift::factory()->create([
        'department_id' => $this->department->id,
        'weekly_schedule_id' => $this->schedule->id,
        'date' => now()->format('Y-m-d'),
    ]);
});

describe('DepartmentTodoController', function () {
    describe('index', function () {
        it('lists all todos for a department', function () {
            DepartmentTodo::factory()->count(3)->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($this->admin)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertOk()
                ->assertJsonCount(3, 'todos');
        });

        it('filters todos by status', function () {
            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::COMPLETED,
            ]);

            actingAs($this->admin)
                ->getJson("/departments/{$this->department->uuid}/todos?status=todo")
                ->assertOk()
                ->assertJsonCount(1, 'todos');
        });

        it('filters todos by priority', function () {
            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'priority' => TodoPriority::URGENT,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'priority' => TodoPriority::LOW,
            ]);

            actingAs($this->admin)
                ->getJson("/departments/{$this->department->uuid}/todos?priority=urgent")
                ->assertOk()
                ->assertJsonCount(1, 'todos');
        });

        it('requires authentication', function () {
            get("/departments/{$this->department->uuid}/todos")
                ->assertRedirect('/login');
        });
    });

    describe('store', function () {
        it('creates a new todo', function () {
            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Test Todo',
                    'description' => 'Test description',
                    'priority' => 'high',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            assertDatabaseHas('department_todos', [
                'department_id' => $this->department->id,
                'title' => 'Test Todo',
                'description' => 'Test description',
                'priority' => 'high',
                'created_by' => $this->admin->id,
            ]);
        });

        it('creates a todo linked to a shift', function () {
            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Shift Todo',
                    'shift_id' => $this->shift->uuid,
                    'priority' => 'medium',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            assertDatabaseHas('department_todos', [
                'department_id' => $this->department->id,
                'shift_id' => $this->shift->id,
                'title' => 'Shift Todo',
            ]);
        });

        it('creates a todo with assignee', function () {
            $assignee = User::factory()->create();
            $this->department->members()->attach($assignee);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Assigned Todo',
                    'assigned_to' => $assignee->uuid,
                    'priority' => 'high',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            assertDatabaseHas('department_todos', [
                'department_id' => $this->department->id,
                'assigned_to' => $assignee->id,
                'title' => 'Assigned Todo',
            ]);
        });

        it('creates a todo with due date', function () {
            $dueDate = now()->addDays(3)->format('Y-m-d');

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Due Date Todo',
                    'due_date' => $dueDate,
                    'priority' => 'medium',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            assertDatabaseHas('department_todos', [
                'title' => 'Due Date Todo',
                'due_date' => $dueDate,
            ]);
        });

        it('validates required title', function () {
            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'description' => 'No title',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['title']);
        });

        it('validates priority enum', function () {
            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Test',
                    'priority' => 'invalid',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['priority']);
        });
    });

    describe('show', function () {
        it('shows a todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'title' => 'Show Test Todo',
            ]);

            actingAs($this->admin)
                ->getJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertOk()
                ->assertJsonPath('todo.title', 'Show Test Todo');
        });
    });

    describe('update', function () {
        it('updates a todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'title' => 'Original Title',
            ]);

            actingAs($this->admin)
                ->putJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}", [
                    'title' => 'Updated Title',
                    'priority' => 'urgent',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            assertDatabaseHas('department_todos', [
                'id' => $todo->id,
                'title' => 'Updated Title',
                'priority' => 'urgent',
            ]);
        });
    });

    describe('destroy', function () {
        it('deletes a todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($this->admin)
                ->deleteJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertOk()
                ->assertJson(['success' => true]);

            assertDatabaseMissing('department_todos', [
                'id' => $todo->id,
            ]);
        });
    });

    describe('toggleComplete', function () {
        it('marks a todo as completed', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/toggle-complete")
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->status)->toBe(ShiftTaskStatus::COMPLETED);
            expect($todo->fresh()->completed_by)->toBe($this->admin->id);
            expect($todo->fresh()->completed_at)->not->toBeNull();
        });

        it('reopens a completed todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::COMPLETED,
                'completed_by' => $this->admin->id,
                'completed_at' => now(),
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/toggle-complete")
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->status)->toBe(ShiftTaskStatus::TODO);
            expect($todo->fresh()->completed_by)->toBeNull();
            expect($todo->fresh()->completed_at)->toBeNull();
        });
    });

    describe('updateStatus', function () {
        it('updates todo status to in_progress', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/status", [
                    'status' => 'in_progress',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->status)->toBe(ShiftTaskStatus::IN_PROGRESS);
        });

        it('updates todo status to blocked', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::IN_PROGRESS,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/status", [
                    'status' => 'blocked',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->status)->toBe(ShiftTaskStatus::BLOCKED);
        });

        it('pauses an in_progress todo back to todo status', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::IN_PROGRESS,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/status", [
                    'status' => 'todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->status)->toBe(ShiftTaskStatus::TODO);
        });

        it('reopens a completed todo back to todo status', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::COMPLETED,
                'completed_by' => $this->admin->id,
                'completed_at' => now(),
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/status", [
                    'status' => 'todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->status)->toBe(ShiftTaskStatus::TODO);
            expect($todo->fresh()->completed_by)->toBeNull();
            expect($todo->fresh()->completed_at)->toBeNull();
        });

        it('validates status enum', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/status", [
                    'status' => 'invalid_status',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['status']);
        });
    });

    describe('assign', function () {
        it('assigns a todo to a user', function () {
            $assignee = User::factory()->create();
            $this->department->members()->attach($assignee);

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'assigned_to' => null,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/assign", [
                    'user_uuid' => $assignee->uuid,
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->assigned_to)->toBe($assignee->id);
        });

        it('unassigns a todo', function () {
            $assignee = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'assigned_to' => $assignee->id,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/assign", [
                    'user_uuid' => null,
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            expect($todo->fresh()->assigned_to)->toBeNull();
        });
    });

    describe('forShift', function () {
        it('lists todos for a specific shift', function () {
            // Create todos for the shift
            DepartmentTodo::factory()->count(2)->create([
                'department_id' => $this->department->id,
                'shift_id' => $this->shift->id,
                'created_by' => $this->admin->id,
            ]);

            // Create todo without shift
            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'shift_id' => null,
                'created_by' => $this->admin->id,
            ]);

            actingAs($this->admin)
                ->getJson("/departments/{$this->department->uuid}/todos/shift/{$this->shift->uuid}")
                ->assertOk()
                ->assertJsonCount(2, 'todos');
        });
    });

    describe('bulkUpdate', function () {
        it('bulk updates multiple todos', function () {
            $todos = DepartmentTodo::factory()->count(3)->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/bulk", [
                    'action' => 'complete',
                    'todo_uuids' => $todos->pluck('uuid')->toArray(),
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            foreach ($todos as $todo) {
                expect($todo->fresh()->status)->toBe(ShiftTaskStatus::COMPLETED);
            }
        });

        it('bulk deletes multiple todos', function () {
            $todos = DepartmentTodo::factory()->count(3)->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos/bulk", [
                    'action' => 'delete',
                    'todo_uuids' => $todos->pluck('uuid')->toArray(),
                ])
                ->assertOk()
                ->assertJson(['success' => true]);

            foreach ($todos as $todo) {
                assertDatabaseMissing('department_todos', ['id' => $todo->id]);
            }
        });
    });
});

describe('DepartmentTodo Model', function () {
    it('generates uuid on creation', function () {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->uuid)->not->toBeNull();
        expect(strlen($todo->uuid))->toBe(36);
    });

    it('has department relationship', function () {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->department)->toBeInstanceOf(Department::class);
        expect($todo->department->id)->toBe($this->department->id);
    });

    it('has shift relationship', function () {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'shift_id' => $this->shift->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->shift)->toBeInstanceOf(Shift::class);
        expect($todo->shift->id)->toBe($this->shift->id);
    });

    it('has assignee relationship', function () {
        $assignee = User::factory()->create();

        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'assigned_to' => $assignee->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->assignee)->toBeInstanceOf(User::class);
        expect($todo->assignee->id)->toBe($assignee->id);
    });

    it('has creator relationship', function () {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->creator)->toBeInstanceOf(User::class);
        expect($todo->creator->id)->toBe($this->admin->id);
    });

    it('calculates is_overdue correctly', function () {
        $overdueTodo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'due_date' => now()->subDays(2),
            'status' => ShiftTaskStatus::TODO,
        ]);

        $notOverdueTodo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'due_date' => now()->addDays(2),
            'status' => ShiftTaskStatus::TODO,
        ]);

        $completedTodo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'due_date' => now()->subDays(2),
            'status' => ShiftTaskStatus::COMPLETED,
        ]);

        expect($overdueTodo->is_overdue)->toBeTrue();
        expect($notOverdueTodo->is_overdue)->toBeFalse();
        expect($completedTodo->is_overdue)->toBeFalse();
    });

    it('calculates is_due_today correctly', function () {
        $dueTodayTodo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'due_date' => now(),
            'status' => ShiftTaskStatus::TODO,
        ]);

        $notDueTodayTodo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
            'due_date' => now()->addDays(1),
            'status' => ShiftTaskStatus::TODO,
        ]);

        expect($dueTodayTodo->is_due_today)->toBeTrue();
        expect($notDueTodayTodo->is_due_today)->toBeFalse();
    });

    describe('scopes', function () {
        it('filters by department', function () {
            $otherDepartment = Department::factory()->create();

            DepartmentTodo::factory()->count(2)->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $otherDepartment->id,
                'created_by' => $this->admin->id,
            ]);

            $todos = DepartmentTodo::forDepartment($this->department)->get();

            expect($todos)->toHaveCount(2);
        });

        it('filters by shift', function () {
            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'shift_id' => $this->shift->id,
                'created_by' => $this->admin->id,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'shift_id' => null,
                'created_by' => $this->admin->id,
            ]);

            $shiftTodos = DepartmentTodo::forDepartment($this->department)->forShift($this->shift)->get();
            $noShiftTodos = DepartmentTodo::forDepartment($this->department)->forShift(null)->get();

            expect($shiftTodos)->toHaveCount(1);
            expect($noShiftTodos)->toHaveCount(1);
        });

        it('filters pending todos', function () {
            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::IN_PROGRESS,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::COMPLETED,
            ]);

            $pendingTodos = DepartmentTodo::forDepartment($this->department)->pending()->get();

            expect($pendingTodos)->toHaveCount(2);
        });

        it('filters overdue todos', function () {
            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'due_date' => now()->subDays(2),
                'status' => ShiftTaskStatus::TODO,
            ]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'due_date' => now()->addDays(2),
                'status' => ShiftTaskStatus::TODO,
            ]);

            $overdueTodos = DepartmentTodo::forDepartment($this->department)->overdue()->get();

            expect($overdueTodos)->toHaveCount(1);
        });
    });

    describe('status methods', function () {
        it('completes a todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            $result = $todo->complete($this->admin);

            expect($result)->toBeTrue();
            expect($todo->status)->toBe(ShiftTaskStatus::COMPLETED);
            expect($todo->completed_by)->toBe($this->admin->id);
        });

        it('starts a todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            $result = $todo->start();

            expect($result)->toBeTrue();
            expect($todo->status)->toBe(ShiftTaskStatus::IN_PROGRESS);
        });

        it('blocks a todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::IN_PROGRESS,
            ]);

            $result = $todo->block();

            expect($result)->toBeTrue();
            expect($todo->status)->toBe(ShiftTaskStatus::BLOCKED);
        });

        it('reopens a completed todo', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::COMPLETED,
                'completed_by' => $this->admin->id,
                'completed_at' => now(),
            ]);

            $result = $todo->reopen();

            expect($result)->toBeTrue();
            expect($todo->status)->toBe(ShiftTaskStatus::TODO);
            expect($todo->completed_by)->toBeNull();
            expect($todo->completed_at)->toBeNull();
        });
    });
});

describe('DepartmentTodoController Authorization', function () {
    describe('department member access', function () {
        it('allows department members to view todos', function () {
            $member = User::factory()->create();
            $this->department->members()->attach($member);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($member)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertOk();
        });

        it('allows department members to create todos', function () {
            $member = User::factory()->create();
            $this->department->members()->attach($member);

            actingAs($member)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Member created todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });

        it('allows department members to update todos', function () {
            $member = User::factory()->create();
            $this->department->members()->attach($member);

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($member)
                ->putJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}", [
                    'title' => 'Updated by member',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });

        it('allows department members to delete todos', function () {
            $member = User::factory()->create();
            $this->department->members()->attach($member);

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($member)
                ->deleteJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertOk()
                ->assertJson(['success' => true]);
        });
    });

    describe('head of department access', function () {
        it('allows head of department to view todos even if not a member', function () {
            $head = User::factory()->create();
            $this->department->update(['head_of_department' => $head->id]);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($head)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertOk();
        });

        it('allows head of department to create todos', function () {
            $head = User::factory()->create();
            $this->department->update(['head_of_department' => $head->id]);

            actingAs($head)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Head created todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });

        it('allows head of department to update and delete todos', function () {
            $head = User::factory()->create();
            $this->department->update(['head_of_department' => $head->id]);

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($head)
                ->putJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}", [
                    'title' => 'Updated by head',
                ])
                ->assertOk();

            actingAs($head)
                ->deleteJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertOk();
        });
    });

    describe('admin permission access', function () {
        it('allows users with manage departments permission to view any department todos', function () {
            $adminUser = User::factory()->create();
            $adminUser->givePermissionTo('manage departments');

            // Create another department the admin is NOT a member of
            $otherDepartment = Department::factory()->create();

            DepartmentTodo::factory()->create([
                'department_id' => $otherDepartment->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($adminUser)
                ->getJson("/departments/{$otherDepartment->uuid}/todos")
                ->assertOk();
        });

        it('allows users with manage departments permission to create todos in any department', function () {
            $adminUser = User::factory()->create();
            $adminUser->givePermissionTo('manage departments');

            $otherDepartment = Department::factory()->create();

            actingAs($adminUser)
                ->postJson("/departments/{$otherDepartment->uuid}/todos", [
                    'title' => 'Admin created todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });

        it('allows users with manage departments permission to update todos in any department', function () {
            $adminUser = User::factory()->create();
            $adminUser->givePermissionTo('manage departments');

            $otherDepartment = Department::factory()->create();
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $otherDepartment->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($adminUser)
                ->putJson("/departments/{$otherDepartment->uuid}/todos/{$todo->uuid}", [
                    'title' => 'Updated by admin',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });
    });

    describe('external user denial', function () {
        it('denies non-member users from viewing department todos', function () {
            $outsider = User::factory()->create();

            actingAs($outsider)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertForbidden();
        });

        it('denies non-member users from creating todos', function () {
            $outsider = User::factory()->create();

            actingAs($outsider)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Unauthorized todo',
                ])
                ->assertForbidden();
        });

        it('denies non-member users from updating todos', function () {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->putJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}", [
                    'title' => 'Unauthorized update',
                ])
                ->assertForbidden();
        });

        it('denies non-member users from deleting todos', function () {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->deleteJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertForbidden();
        });

        it('denies non-member users from toggling todo completion', function () {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/toggle-complete")
                ->assertForbidden();
        });

        it('denies non-member users from updating todo status', function () {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/status", [
                    'status' => 'completed',
                ])
                ->assertForbidden();
        });

        it('denies non-member users from bulk updating todos', function () {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->postJson("/departments/{$this->department->uuid}/todos/bulk", [
                    'todo_uuids' => [$todo->uuid],
                    'action' => 'complete',
                ])
                ->assertForbidden();
        });
    });

    describe('view departments permission only', function () {
        it('denies users with only view departments permission from accessing todos', function () {
            $viewOnlyUser = User::factory()->create();
            $viewOnlyUser->givePermissionTo('view departments');

            actingAs($viewOnlyUser)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertForbidden();
        });
    });

    describe('cross-department authorization', function () {
        it('prevents members of one department from accessing another department todos', function () {
            $memberOfOtherDept = User::factory()->create();
            $otherDepartment = Department::factory()->create();
            $otherDepartment->members()->attach($memberOfOtherDept);

            DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            // Try to access the first department's todos while being member of another
            actingAs($memberOfOtherDept)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertForbidden();
        });

        it('prevents members from modifying todos in other departments', function () {
            $memberOfOtherDept = User::factory()->create();
            $otherDepartment = Department::factory()->create();
            $otherDepartment->members()->attach($memberOfOtherDept);

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($memberOfOtherDept)
                ->putJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}", [
                    'title' => 'Cross-department attack',
                ])
                ->assertForbidden();
        });
    });

    describe('unauthenticated access', function () {
        it('requires authentication for all todo endpoints', function () {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            get("/departments/{$this->department->uuid}/todos")
                ->assertRedirect('/login');

            \Pest\Laravel\postJson("/departments/{$this->department->uuid}/todos", ['title' => 'Test'])
                ->assertUnauthorized();

            \Pest\Laravel\putJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}", ['title' => 'Test'])
                ->assertUnauthorized();

            \Pest\Laravel\deleteJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertUnauthorized();
        });
    });
});
