<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\IfrsAccountClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class IfrsAccountClassFactory extends Factory
{
    protected $model = IfrsAccountClass::class;

    public function definition(): array
    {
        return [
            'class_number' => $this->faker->unique()->numberBetween(1, 5),
            'name' => $this->faker->words(4, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['assets', 'liabilities', 'equity', 'revenue', 'expenses']),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
