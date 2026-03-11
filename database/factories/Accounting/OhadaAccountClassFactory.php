<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\OhadaAccountClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class OhadaAccountClassFactory extends Factory
{
    protected $model = OhadaAccountClass::class;

    public function definition(): array
    {
        return [
            'class_number' => $this->faker->unique()->numberBetween(1, 9),
            'name' => $this->faker->words(4, true),
            'description' => $this->faker->sentence(),
            'category' => $this->faker->randomElement(['bilan', 'gestion', 'hors_bilan']),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
