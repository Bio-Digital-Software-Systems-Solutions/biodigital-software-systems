<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\PcgAccountClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class PcgAccountClassFactory extends Factory
{
    protected $model = PcgAccountClass::class;

    public function definition(): array
    {
        return [
            'class_number' => $this->faker->unique()->numberBetween(1, 8),
            'name' => $this->faker->words(4, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['bilan', 'gestion']),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
