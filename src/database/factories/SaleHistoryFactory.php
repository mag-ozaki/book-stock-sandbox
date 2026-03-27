<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\SaleHistory;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleHistory>
 */
class SaleHistoryFactory extends Factory
{
    protected $model = SaleHistory::class;

    public function definition(): array
    {
        return [
            'store_id'        => Store::factory(),
            'book_id'         => Book::factory(),
            'quantity'        => fake()->numberBetween(1, 20),
            'sold_at'         => fake()->dateTimeBetween('-1 year', 'now'),
            'pos_terminal_id' => fake()->optional()->regexify('[A-Z]+-[0-9]+'),
        ];
    }
}
