<?php

namespace Database\Factories;

use App\Models\DepartmentNeed;
use App\Models\Department;
use App\Enums\Need\NeedStatus;
use App\Enums\Need\NeedCategory;
use App\Enums\Need\NeedPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentNeed>
 */
class DepartmentNeedFactory extends Factory
{
    protected $model = DepartmentNeed::class;

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
            'requester_id' => null,
            'assigned_to' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'justification' => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(NeedCategory::cases()),
            'priority' => $this->faker->randomElement(NeedPriority::cases()),
            'status' => NeedStatus::DRAFT,
            'estimated_cost' => $this->faker->randomFloat(2, 100, 10000),
            'approved_budget' => null,
            'actual_cost' => null,
            'currency' => 'EUR',
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit' => $this->faker->randomElement(['pièce', 'lot', 'unité', null]),
            'specifications' => null,
            'vendor_info' => null,
            'needed_by' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
        ];
    }

    /**
     * Indicate that the need is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NeedStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate that the need is pending approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NeedStatus::UNDER_REVIEW,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate that the need is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NeedStatus::APPROVED,
            'submitted_at' => now()->subDays(2),
            'approved_at' => now(),
            'approved_budget' => $attributes['estimated_cost'] ?? $this->faker->randomFloat(2, 100, 10000),
        ]);
    }

    /**
     * Indicate that the need is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NeedStatus::REJECTED,
            'submitted_at' => now()->subDays(2),
            'rejected_at' => now(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the need is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => NeedStatus::COMPLETED,
            'submitted_at' => now()->subWeek(),
            'approved_at' => now()->subDays(5),
            'completed_at' => now(),
            'actual_cost' => $attributes['estimated_cost'] ?? $this->faker->randomFloat(2, 100, 10000),
        ]);
    }

    /**
     * Set the need as high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => NeedPriority::HIGH,
        ]);
    }

    /**
     * Set the need as critical priority.
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => NeedPriority::CRITICAL,
        ]);
    }

    /**
     * Set the need category to equipment.
     */
    public function equipment(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => NeedCategory::EQUIPMENT,
        ]);
    }

    /**
     * Set the need category to service.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => NeedCategory::SERVICES,
        ]);
    }

    /**
     * Set the need category to supply.
     */
    public function supply(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => NeedCategory::SUPPLIES,
        ]);
    }
}
