<?php

namespace Database\Factories;

use App\Models\DepartmentKpi;
use App\Models\DepartmentKpiValue;
use App\Models\DepartmentReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DepartmentKpiValue>
 */
class DepartmentKpiValueFactory extends Factory
{
    protected $model = DepartmentKpiValue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kpi_id' => DepartmentKpi::factory(),
            'report_id' => null,
            'value' => fake()->randomFloat(2, 0, 100),
            'recorded_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'recorded_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
            'metadata' => [],
        ];
    }

    /**
     * Set value for a specific KPI.
     */
    public function forKpi(DepartmentKpi $kpi): static
    {
        return $this->state(fn (array $attributes) => [
            'kpi_id' => $kpi->id,
        ]);
    }

    /**
     * Set value for a specific report.
     */
    public function forReport(DepartmentReport $report): static
    {
        return $this->state(fn (array $attributes) => [
            'report_id' => $report->id,
        ]);
    }

    /**
     * Set value.
     */
    public function withValue(float $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $value,
        ]);
    }

    /**
     * Set recorded date.
     */
    public function recordedAt(Carbon $date): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_at' => $date,
        ]);
    }

    /**
     * Set recorder.
     */
    public function recordedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_by' => $user->id,
        ]);
    }
}
