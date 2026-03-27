<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'jan_code'  => fake()->unique()->numerify('978##########') . fake()->numerify('1920#########'),
            'title'     => fake()->sentence(3),
            'author'    => fake()->name(),
            'publisher' => fake()->company(),
            'price'     => fake()->numberBetween(500, 5000),
        ];
    }
}
