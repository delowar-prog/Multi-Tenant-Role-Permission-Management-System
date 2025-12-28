<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Borrow>
 */
class BorrowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $borrowDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $dueDate = (clone $borrowDate)->modify('+14 days');

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'return_date' => $this->faker->boolean ? $this->faker->dateTimeBetween($borrowDate, $dueDate) : null,
            'status' => $this->faker->randomElement(['borrowed', 'returned', 'overdue']),
        ];
    }
}
