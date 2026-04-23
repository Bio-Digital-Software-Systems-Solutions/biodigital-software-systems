<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\GroupTodo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_group(): void
    {
        $group = Group::factory()->create();
        $todo = GroupTodo::factory()->create(['group_id' => $group->id]);

        $this->assertInstanceOf(Group::class, $todo->group);
        $this->assertEquals($group->id, $todo->group->id);
    }

    public function test_belongs_to_assignee(): void
    {
        $user = User::factory()->create();
        $todo = GroupTodo::factory()->create(['assigned_to' => $user->id]);

        $this->assertInstanceOf(User::class, $todo->assignee);
        $this->assertEquals($user->id, $todo->assignee->id);
    }

    public function test_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $todo = GroupTodo::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $todo->creator);
        $this->assertEquals($user->id, $todo->creator->id);
    }

    public function test_complete_sets_status_and_completed_by(): void
    {
        $user = User::factory()->create();
        $todo = GroupTodo::factory()->pending()->create();

        $todo->complete($user->id);

        $todo->refresh();
        $this->assertEquals('completed', $todo->status);
        $this->assertEquals($user->id, $todo->completed_by);
        $this->assertNotNull($todo->completed_at);
    }

    public function test_start_sets_status(): void
    {
        $todo = GroupTodo::factory()->pending()->create();

        $todo->start();

        $todo->refresh();
        $this->assertEquals('in_progress', $todo->status);
    }

    public function test_cancel_sets_status(): void
    {
        $todo = GroupTodo::factory()->pending()->create();

        $todo->cancel();

        $todo->refresh();
        $this->assertEquals('cancelled', $todo->status);
    }

    public function test_reopen_resets_completion(): void
    {
        $user = User::factory()->create();
        $todo = GroupTodo::factory()->completed()->create();

        $todo->reopen();

        $todo->refresh();
        $this->assertEquals('pending', $todo->status);
        $this->assertNull($todo->completed_by);
        $this->assertNull($todo->completed_at);
    }

    public function test_is_overdue_with_past_due_date(): void
    {
        $todo = GroupTodo::factory()->overdue()->create();

        $this->assertTrue($todo->isOverdue());
    }

    public function test_is_not_overdue_when_completed(): void
    {
        $todo = GroupTodo::factory()->create([
            'status' => 'completed',
            'due_date' => now()->subDays(5),
            'completed_at' => now(),
            'completed_by' => User::factory(),
        ]);

        $this->assertFalse($todo->isOverdue());
    }

    public function test_is_not_overdue_without_due_date(): void
    {
        $todo = GroupTodo::factory()->pending()->create(['due_date' => null]);

        $this->assertFalse($todo->isOverdue());
    }

    public function test_scope_completed(): void
    {
        $group = Group::factory()->create();
        GroupTodo::factory()->count(3)->create(['group_id' => $group->id, 'status' => 'completed']);
        GroupTodo::factory()->count(2)->create(['group_id' => $group->id, 'status' => 'pending']);

        $this->assertEquals(3, GroupTodo::completed()->where('group_id', $group->id)->count());
    }

    public function test_scope_pending(): void
    {
        $group = Group::factory()->create();
        GroupTodo::factory()->count(3)->create(['group_id' => $group->id, 'status' => 'completed']);
        GroupTodo::factory()->count(2)->create(['group_id' => $group->id, 'status' => 'pending']);

        $this->assertEquals(2, GroupTodo::pending()->where('group_id', $group->id)->count());
    }

    public function test_scope_overdue(): void
    {
        $group = Group::factory()->create();
        GroupTodo::factory()->overdue()->count(2)->create(['group_id' => $group->id]);
        GroupTodo::factory()->pending()->count(1)->create(['group_id' => $group->id, 'due_date' => now()->addDays(5)]);

        $this->assertEquals(2, GroupTodo::overdue()->where('group_id', $group->id)->count());
    }

    public function test_scope_for_group(): void
    {
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();
        GroupTodo::factory()->count(3)->create(['group_id' => $group1->id]);
        GroupTodo::factory()->count(2)->create(['group_id' => $group2->id]);

        $this->assertEquals(3, GroupTodo::forGroup($group1->id)->count());
        $this->assertEquals(2, GroupTodo::forGroup($group2->id)->count());
    }

    public function test_uuid_is_auto_generated(): void
    {
        $todo = GroupTodo::factory()->create();

        $this->assertNotNull($todo->uuid);
        $this->assertNotEmpty($todo->uuid);
    }

    public function test_route_key_is_uuid(): void
    {
        $todo = new GroupTodo;

        $this->assertEquals('uuid', $todo->getRouteKeyName());
    }
}
