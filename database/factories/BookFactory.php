<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $totalCopies = $this->faker->numberBetween(1, 10);

        return [
            'title' => $this->faker->sentence(3),
            'author' => $this->faker->name(),
            'isbn' => $this->faker->optional()->isbn13(),
            'description' => $this->faker->optional()->paragraph(),
            'rental_price' => $this->faker->optional()->randomFloat(2, 1, 10),
            'max_rental_days' => $this->faker->numberBetween(7, 30),
            'stock_quantity' => $this->faker->numberBetween(1, 10),
            'total_copies' => $totalCopies,
            'available_copies' => $totalCopies, // Initially all copies are available
            'category_id' => null,
            'library_id' => null, // Can be set explicitly in tests if needed
        ];
    }
}
