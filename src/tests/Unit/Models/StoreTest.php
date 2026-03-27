<?php

namespace Tests\Unit\Models;

use App\Models\Admin;
use App\Models\Book;
use App\Models\PurchaseHistory;
use App\Models\Stock;
use App\Models\Store;
use App\Models\StoreApiKey;
use App\Models\StoreUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::factory()->create();
    }

    public function test_store_has_many_purchase_histories(): void
    {
        $book      = Book::factory()->create();
        $storeUser = StoreUser::factory()->create(['store_id' => $this->store->id]);

        PurchaseHistory::factory()->create([
            'book_id'       => $book->id,
            'store_id'      => $this->store->id,
            'store_user_id' => $storeUser->id,
        ]);

        $this->assertCount(1, $this->store->purchaseHistories);
    }

    public function test_store_has_many_api_keys(): void
    {
        $admin = Admin::factory()->create();

        StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $admin->id,
        ]);

        $this->assertCount(1, $this->store->apiKeys);
    }
}
