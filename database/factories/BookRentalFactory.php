<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookRental;
use App\Models\Library;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookRentalFactory extends Factory
{
    protected $model = BookRental::class;

    public function definition(): array
    {
        $rentalDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $rentalDays = $this->faker->numberBetween(7, 21);
        $dueDate = (clone $rentalDate)->modify("+{$rentalDays} days");

        return [
            'book_id' => Book::factory(),
            'user_id' => User::factory(),
            'library_id' => Library::factory(),
            'rental_date' => $rentalDate,
            'due_date' => $dueDate,
            'return_date' => null,
            'rental_fee' => $this->faker->randomFloat(2, 5, 50),
            'late_fee' => 0,
            'status' => 'active',
        ];
    }

    public function returned(): static
    {
        return $this->state(function (array $attributes) {
            $returnDate = $this->faker->dateTimeBetween($attributes['rental_date'], 'now');

            return [
                'return_date' => $returnDate,
                'status' => 'returned',
            ];
        });
    }

    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $rentalDate = $this->faker->dateTimeBetween('-2 months', '-1 month');
            $dueDate = (clone $rentalDate)->modify('+7 days');

            return [
                'rental_date' => $rentalDate,
                'due_date' => $dueDate,
                'late_fee' => $this->faker->randomFloat(2, 2, 20),
                'status' => 'active',
            ];
        });
    }
}
