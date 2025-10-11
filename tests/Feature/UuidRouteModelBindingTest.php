<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Group;
use App\Models\Stock;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UuidRouteModelBindingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function task_can_be_found_by_uuid()
    {
        $task = Task::factory()->make();
        $task->program_id = null; // Avoid Program dependency
        $task->save();

        $this->assertNotNull($task->uuid);
        $this->assertEquals('uuid', $task->getRouteKeyName());

        // Test that the route uses UUID
        $route = route('tasks.show', $task);
        $this->assertStringContainsString($task->uuid, $route);
        // Verify the route ends with UUID, not numeric ID
        $this->assertStringEndsWith($task->uuid, $route);
    }

    /** @test */
    public function department_can_be_found_by_uuid()
    {
        $department = Department::factory()->create();

        $this->assertNotNull($department->uuid);
        $this->assertEquals('uuid', $department->getRouteKeyName());

        // Test that the route uses UUID
        $route = route('departments.show', $department);
        $this->assertStringContainsString($department->uuid, $route);
        // Verify the route ends with UUID, not numeric ID
        $this->assertStringEndsWith($department->uuid, $route);
    }

    /** @test */
    public function stock_can_be_found_by_uuid()
    {
        $stock = Stock::factory()->create();

        $this->assertNotNull($stock->uuid);
        $this->assertEquals('uuid', $stock->getRouteKeyName());

        // Test that the route uses UUID
        $route = route('stocks.show', $stock);
        $this->assertStringContainsString($stock->uuid, $route);
        // Verify the route ends with UUID, not numeric ID
        $this->assertStringEndsWith($stock->uuid, $route);
    }

    /** @test */
    public function group_can_be_found_by_uuid()
    {
        $group = Group::factory()->create();

        $this->assertNotNull($group->uuid);
        $this->assertEquals('uuid', $group->getRouteKeyName());

        // Test that the route uses UUID
        $route = route('groups.show', $group);
        $this->assertStringContainsString($group->uuid, $route);
        // Verify the route ends with UUID, not numeric ID
        $this->assertStringEndsWith($group->uuid, $route);
    }

    /** @test */
    public function task_uuid_is_included_in_json_serialization()
    {
        $task = Task::factory()->make();
        $task->program_id = null; // Avoid Program dependency
        $task->save();

        $json = $task->toArray();

        $this->assertArrayHasKey('uuid', $json);
        $this->assertEquals($task->uuid, $json['uuid']);
    }

    /** @test */
    public function department_uuid_is_included_in_json_serialization()
    {
        $department = Department::factory()->create();

        $json = $department->toArray();

        $this->assertArrayHasKey('uuid', $json);
        $this->assertEquals($department->uuid, $json['uuid']);
    }

    /** @test */
    public function stock_uuid_is_included_in_json_serialization()
    {
        $stock = Stock::factory()->create();

        $json = $stock->toArray();

        $this->assertArrayHasKey('uuid', $json);
        $this->assertEquals($stock->uuid, $json['uuid']);
    }

    /** @test */
    public function group_uuid_is_included_in_json_serialization()
    {
        $group = Group::factory()->create();

        $json = $group->toArray();

        $this->assertArrayHasKey('uuid', $json);
        $this->assertEquals($group->uuid, $json['uuid']);
    }

    /** @test */
    public function task_uuid_is_unique()
    {
        $task1 = Task::factory()->make();
        $task1->program_id = null; // Avoid Program dependency
        $task1->save();

        $task2 = Task::factory()->make();
        $task2->program_id = null; // Avoid Program dependency
        $task2->save();

        $this->assertNotEquals($task1->uuid, $task2->uuid);
    }

    /** @test */
    public function department_uuid_is_unique()
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $this->assertNotEquals($department1->uuid, $department2->uuid);
    }

    /** @test */
    public function stock_uuid_is_unique()
    {
        $stock1 = Stock::factory()->create();
        $stock2 = Stock::factory()->create();

        $this->assertNotEquals($stock1->uuid, $stock2->uuid);
    }

    /** @test */
    public function group_uuid_is_unique()
    {
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        $this->assertNotEquals($group1->uuid, $group2->uuid);
    }

    /** @test */
    public function task_uuid_is_valid_uuid_format()
    {
        $task = Task::factory()->make();
        $task->program_id = null; // Avoid Program dependency
        $task->save();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $task->uuid
        );
    }

    /** @test */
    public function department_uuid_is_valid_uuid_format()
    {
        $department = Department::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $department->uuid
        );
    }

    /** @test */
    public function stock_uuid_is_valid_uuid_format()
    {
        $stock = Stock::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $stock->uuid
        );
    }

    /** @test */
    public function group_uuid_is_valid_uuid_format()
    {
        $group = Group::factory()->create();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $group->uuid
        );
    }
}
