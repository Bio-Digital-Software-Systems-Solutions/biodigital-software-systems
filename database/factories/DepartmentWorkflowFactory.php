<?php

namespace Database\Factories;

use App\Models\DepartmentWorkflow;
use App\Models\Department;
use App\Enums\Workflow\WorkflowStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentWorkflow>
 */
class DepartmentWorkflowFactory extends Factory
{
    protected $model = DepartmentWorkflow::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'department_id' => Department::factory(),
            'created_by' => null,
            'name' => $this->faker->words(3, true) . ' Workflow',
            'description' => $this->faker->paragraph(),
            'status' => WorkflowStatus::DRAFT,
            'version' => 1,
            'settings' => [],
        ];
    }

    /**
     * Indicate that the workflow is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::ACTIVE,
        ]);
    }

    /**
     * Indicate that the workflow is deprecated.
     */
    public function deprecated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkflowStatus::DEPRECATED,
        ]);
    }
}
