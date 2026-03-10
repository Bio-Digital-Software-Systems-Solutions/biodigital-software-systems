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

beforeEach(function (): void {
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

describe('DepartmentTodoController', function (): void {
    describe('index', function (): void {
        it('lists all todos for a department', function (): void {
            DepartmentTodo::factory()->count(3)->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($this->admin)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertOk()
                ->assertJsonCount(3, 'todos');
        });

        it('filters todos by status', function (): void {
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

        it('filters todos by priority', function (): void {
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

        it('requires authentication', function (): void {
            get("/departments/{$this->department->uuid}/todos")
                ->assertRedirect('/login');
        });
    });

    describe('store', function (): void {
        it('creates a new todo', function (): void {
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

        it('creates a todo linked to a shift', function (): void {
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

        it('creates a todo with assignee', function (): void {
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

        it('creates a todo with due date', function (): void {
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

        it('validates required title', function (): void {
            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'description' => 'No title',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['title']);
        });

        it('validates priority enum', function (): void {
            actingAs($this->admin)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Test',
                    'priority' => 'invalid',
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['priority']);
        });
    });

    describe('show', function (): void {
        it('shows a todo', function (): void {
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

    describe('update', function (): void {
        it('updates a todo', function (): void {
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

    describe('destroy', function (): void {
        it('deletes a todo', function (): void {
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

    describe('toggleComplete', function (): void {
        it('marks a todo as completed', function (): void {
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

        it('reopens a completed todo', function (): void {
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

    describe('updateStatus', function (): void {
        it('updates todo status to in_progress', function (): void {
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

        it('updates todo status to blocked', function (): void {
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

        it('pauses an in_progress todo back to todo status', function (): void {
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

        it('reopens a completed todo back to todo status', function (): void {
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

        it('validates status enum', function (): void {
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

    describe('assign', function (): void {
        it('assigns a todo to a user', function (): void {
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

        it('unassigns a todo', function (): void {
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

    describe('forShift', function (): void {
        it('lists todos for a specific shift', function (): void {
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

    describe('bulkUpdate', function (): void {
        it('bulk updates multiple todos', function (): void {
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

        it('bulk deletes multiple todos', function (): void {
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

describe('DepartmentTodo Model', function (): void {
    it('generates uuid on creation', function (): void {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->uuid)->not->toBeNull();
        expect(strlen($todo->uuid))->toBe(36);
    });

    it('has department relationship', function (): void {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->department)->toBeInstanceOf(Department::class);
        expect($todo->department->id)->toBe($this->department->id);
    });

    it('has shift relationship', function (): void {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'shift_id' => $this->shift->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->shift)->toBeInstanceOf(Shift::class);
        expect($todo->shift->id)->toBe($this->shift->id);
    });

    it('has assignee relationship', function (): void {
        $assignee = User::factory()->create();

        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'assigned_to' => $assignee->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->assignee)->toBeInstanceOf(User::class);
        expect($todo->assignee->id)->toBe($assignee->id);
    });

    it('has creator relationship', function (): void {
        $todo = DepartmentTodo::factory()->create([
            'department_id' => $this->department->id,
            'created_by' => $this->admin->id,
        ]);

        expect($todo->creator)->toBeInstanceOf(User::class);
        expect($todo->creator->id)->toBe($this->admin->id);
    });

    it('calculates is_overdue correctly', function (): void {
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

    it('calculates is_due_today correctly', function (): void {
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

    describe('scopes', function (): void {
        it('filters by department', function (): void {
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

        it('filters by shift', function (): void {
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

        it('filters pending todos', function (): void {
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

        it('filters overdue todos', function (): void {
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

    describe('status methods', function (): void {
        it('completes a todo', function (): void {
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

        it('starts a todo', function (): void {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::TODO,
            ]);

            $result = $todo->start();

            expect($result)->toBeTrue();
            expect($todo->status)->toBe(ShiftTaskStatus::IN_PROGRESS);
        });

        it('blocks a todo', function (): void {
            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
                'status' => ShiftTaskStatus::IN_PROGRESS,
            ]);

            $result = $todo->block();

            expect($result)->toBeTrue();
            expect($todo->status)->toBe(ShiftTaskStatus::BLOCKED);
        });

        it('reopens a completed todo', function (): void {
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

describe('DepartmentTodoController Authorization', function (): void {
    describe('department member access', function (): void {
        it('allows department members to view todos', function (): void {
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

        it('allows department members to create todos', function (): void {
            $member = User::factory()->create();
            $this->department->members()->attach($member);

            actingAs($member)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Member created todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });

        it('allows department members to update todos', function (): void {
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

        it('allows department members to delete todos', function (): void {
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

    describe('head of department access', function (): void {
        it('allows head of department to view todos even if not a member', function (): void {
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

        it('allows head of department to create todos', function (): void {
            $head = User::factory()->create();
            $this->department->update(['head_of_department' => $head->id]);

            actingAs($head)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Head created todo',
                ])
                ->assertOk()
                ->assertJson(['success' => true]);
        });

        it('allows head of department to update and delete todos', function (): void {
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

    describe('admin permission access', function (): void {
        it('allows users with manage departments permission to view any department todos', function (): void {
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

        it('allows users with manage departments permission to create todos in any department', function (): void {
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

        it('allows users with manage departments permission to update todos in any department', function (): void {
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

    describe('external user denial', function (): void {
        it('denies non-member users from viewing department todos', function (): void {
            $outsider = User::factory()->create();

            actingAs($outsider)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertForbidden();
        });

        it('denies non-member users from creating todos', function (): void {
            $outsider = User::factory()->create();

            actingAs($outsider)
                ->postJson("/departments/{$this->department->uuid}/todos", [
                    'title' => 'Unauthorized todo',
                ])
                ->assertForbidden();
        });

        it('denies non-member users from updating todos', function (): void {
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

        it('denies non-member users from deleting todos', function (): void {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->deleteJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}")
                ->assertForbidden();
        });

        it('denies non-member users from toggling todo completion', function (): void {
            $outsider = User::factory()->create();

            $todo = DepartmentTodo::factory()->create([
                'department_id' => $this->department->id,
                'created_by' => $this->admin->id,
            ]);

            actingAs($outsider)
                ->postJson("/departments/{$this->department->uuid}/todos/{$todo->uuid}/toggle-complete")
                ->assertForbidden();
        });

        it('denies non-member users from updating todo status', function (): void {
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

        it('denies non-member users from bulk updating todos', function (): void {
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

    describe('view departments permission only', function (): void {
        it('denies users with only view departments permission from accessing todos', function (): void {
            $viewOnlyUser = User::factory()->create();
            $viewOnlyUser->givePermissionTo('view departments');

            actingAs($viewOnlyUser)
                ->getJson("/departments/{$this->department->uuid}/todos")
                ->assertForbidden();
        });
    });

    describe('cross-department authorization', function (): void {
        it('prevents members of one department from accessing another department todos', function (): void {
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

        it('prevents members from modifying todos in other departments', function (): void {
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

    describe('unauthenticated access', function (): void {
        it('requires authentication for all todo endpoints', function (): void {
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
