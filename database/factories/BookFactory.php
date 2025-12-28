<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'isbn' => $this->faker->unique()->isbn13(),
            'author_id' => Author::factory(),
            'publisher_id' => Publisher::factory(),
            'category_id' => Category::factory(),
            'published_year' => $this->faker->year(),
            'copies_total' => $this->faker->numberBetween(1, 20),
            'copies_available' => $this->faker->numberBetween(0, 20),
        ];
    }
}
