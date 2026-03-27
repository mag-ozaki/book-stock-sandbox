<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\PurchaseHistory;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_book_has_many_stocks(): void
    {
        $book  = Book::factory()->create();
        $store = Store::factory()->create();

        Stock::factory()->create(['book_id' => $book->id, 'store_id' => $store->id]);

        $this->assertCount(1, $book->stocks);
    }

    public function test_book_has_many_purchase_histories(): void
    {
        $book      = Book::factory()->create();
        $store     = Store::factory()->create();
        $storeUser = StoreUser::factory()->create(['store_id' => $store->id]);

        PurchaseHistory::factory()->create([
            'book_id'       => $book->id,
            'store_id'      => $store->id,
            'store_user_id' => $storeUser->id,
        ]);

        $this->assertCount(1, $book->purchaseHistories);
    }
}
