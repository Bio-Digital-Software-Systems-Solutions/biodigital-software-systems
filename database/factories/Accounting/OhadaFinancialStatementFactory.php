<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\AccountingSystem;
use App\Models\Accounting\OhadaFinancialStatement;
use Illuminate\Database\Eloquent\Factories\Factory;

class OhadaFinancialStatementFactory extends Factory
{
    protected $model = OhadaFinancialStatement::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'code' => $this->faker->unique()->lexify('????'),
            'description' => $this->faker->sentence(),
            'accounting_system_id' => AccountingSystem::factory(),
            'structure' => null,
            'is_required' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
