<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 200);
        $minQuantity = fake()->numberBetween(5, 50);

        return [
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->regexify('[A-Z]{2,3}-[0-9]{3,4}'),
            'description' => fake()->optional()->paragraph(),
            'quantity' => $quantity,
            'minimum_quantity' => $minQuantity,
            'unit_price' => fake()->randomFloat(2, 1, 1000),
            'supplier' => fake()->optional()->company(),
            'supplier_contact' => fake()->optional()->safeEmail(),
            'expiry_date' => fake()->optional()->dateTimeBetween('+1 month', '+2 years'),
            'location' => fake()->optional()->words(2, true),
            'is_active' => fake()->boolean(90),
            'category_id' => Category::factory(),
        ];
    }

    /**
     * Indicate that the stock is low (below minimum quantity).
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes): array {
            $minQuantity = fake()->numberBetween(10, 20);

            return [
                'quantity' => fake()->numberBetween(0, $minQuantity - 1),
                'minimum_quantity' => $minQuantity,
            ];
        });
    }

    /**
     * Indicate that the stock is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => 0,
        ]);
    }

    /**
     * Indicate that the stock is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expiry_date' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the stock is near expiry.
     */
    public function nearExpiry(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expiry_date' => fake()->dateTimeBetween('now', '+30 days'),
        ]);
    }

    /**
     * Indicate that the stock is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
