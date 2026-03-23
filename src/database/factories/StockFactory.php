<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Stock;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'book_id'  => Book::factory(),
            'quantity' => fake()->numberBetween(0, 100),
        ];
    }
}
