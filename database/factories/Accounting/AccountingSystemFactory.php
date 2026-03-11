<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\AccountingSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountingSystemFactory extends Factory
{
    protected $model = AccountingSystem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'code' => $this->faker->unique()->lexify('??'),
            'description' => $this->faker->sentence(),
            'applicable_entities' => ['PME', 'GE'],
            'required_statements' => ['BILAN', 'CR'],
            'revenue_threshold' => $this->faker->randomElement(['< 30M FCFA', '30-100M FCFA', '> 100M FCFA']),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
