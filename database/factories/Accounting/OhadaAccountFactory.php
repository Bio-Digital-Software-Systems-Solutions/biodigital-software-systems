<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\OhadaAccount;
use App\Models\Accounting\OhadaAccountClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class OhadaAccountFactory extends Factory
{
    protected $model = OhadaAccount::class;

    public function definition(): array
    {
        return [
            'account_number' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'class_id' => OhadaAccountClass::factory(),
            'parent_id' => null,
            'level' => $this->faker->numberBetween(1, 4),
            'normal_balance' => $this->faker->randomElement(['debit', 'credit']),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
