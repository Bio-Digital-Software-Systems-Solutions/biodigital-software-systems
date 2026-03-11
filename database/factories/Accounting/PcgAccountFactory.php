<?php

namespace Database\Factories\Accounting;

use App\Models\Accounting\PcgAccount;
use App\Models\Accounting\PcgAccountClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class PcgAccountFactory extends Factory
{
    protected $model = PcgAccount::class;

    public function definition(): array
    {
        return [
            'account_number' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'class_id' => PcgAccountClass::factory(),
            'parent_id' => null,
            'level' => $this->faker->numberBetween(1, 4),
            'normal_balance' => $this->faker->randomElement(['debit', 'credit']),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
