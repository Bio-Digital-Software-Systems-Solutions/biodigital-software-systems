<?php

namespace Database\Factories;

use App\Models\DepartmentForm;
use App\Models\Department;
use App\Enums\Form\FormStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentForm>
 */
class DepartmentFormFactory extends Factory
{
    protected $model = DepartmentForm::class;

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
            'name' => $this->faker->words(3, true) . ' Form',
            'description' => $this->faker->paragraph(),
            'status' => FormStatus::DRAFT,
            'is_multi_step' => false,
            'settings' => [],
            'validation_rules' => [],
            'conditional_logic' => [],
            'success_message' => 'Thank you for your submission!',
            'is_template' => false,
            'version' => 1,
        ];
    }

    /**
     * Indicate that the form is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FormStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the form is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FormStatus::ARCHIVED,
        ]);
    }

    /**
     * Indicate that the form is a template.
     */
    public function template(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_template' => true,
        ]);
    }

    /**
     * Indicate that the form is multi-step.
     */
    public function multiStep(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_multi_step' => true,
        ]);
    }
}
