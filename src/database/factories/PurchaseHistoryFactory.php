<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\PurchaseHistory;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseHistory>
 */
class PurchaseHistoryFactory extends Factory
{
    protected $model = PurchaseHistory::class;

    public function definition(): array
    {
        return [
            'store_id'      => Store::factory(),
            'store_user_id' => StoreUser::factory(),
            'book_id'       => Book::factory(),
            'quantity'      => fake()->numberBetween(1, 50),
            'purchased_at'  => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'note'          => fake()->optional()->sentence(),
        ];
    }
}
