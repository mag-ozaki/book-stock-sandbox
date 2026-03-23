<?php

namespace Tests\Unit\Repositories;

use App\Models\Book;
use App\Models\Stock;
use App\Models\Store;
use App\Repositories\StockRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private StockRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StockRepository();
    }

    public function test_all_by_store_returns_only_own_store_stocks(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();

        $ownStock   = Stock::factory()->create(['store_id' => $store->id]);
        $otherStock = Stock::factory()->create(['store_id' => $otherStore->id]);

        $result = $this->repo->allByStore($store->id);

        $this->assertTrue($result->contains('id', $ownStock->id));
        $this->assertFalse($result->contains('id', $otherStock->id));
    }

    public function test_all_by_store_eager_loads_book(): void
    {
        $store = Store::factory()->create();
        Stock::factory()->create(['store_id' => $store->id]);

        $result = $this->repo->allByStore($store->id);

        $this->assertTrue($result->first()->relationLoaded('book'));
    }

    public function test_find_by_store_or_fail_returns_own_store_stock(): void
    {
        $store = Store::factory()->create();
        $stock = Stock::factory()->create(['store_id' => $store->id]);

        $result = $this->repo->findByStoreOrFail($stock->id, $store->id);

        $this->assertEquals($stock->id, $result->id);
    }

    public function test_find_by_store_or_fail_throws_for_other_store_stock(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $stock      = Stock::factory()->create(['store_id' => $otherStore->id]);

        $this->expectException(ModelNotFoundException::class);

        $this->repo->findByStoreOrFail($stock->id, $store->id);
    }

    public function test_create_persists_stock(): void
    {
        $store = Store::factory()->create();
        $book  = Book::factory()->create();
        $data  = ['store_id' => $store->id, 'book_id' => $book->id, 'quantity' => 5];

        $stock = $this->repo->create($data);

        $this->assertDatabaseHas('stocks', ['store_id' => $store->id, 'book_id' => $book->id, 'quantity' => 5]);
        $this->assertEquals(5, $stock->quantity);
    }

    public function test_update_persists_changes(): void
    {
        $stock = Stock::factory()->create(['quantity' => 10]);

        $updated = $this->repo->update($stock, ['quantity' => 99]);

        $this->assertEquals(99, $updated->quantity);
        $this->assertDatabaseHas('stocks', ['id' => $stock->id, 'quantity' => 99]);
    }

    public function test_stock_has_store_relation(): void
    {
        $store = Store::factory()->create();
        $stock = Stock::factory()->create(['store_id' => $store->id]);

        $this->assertEquals($store->id, $stock->store->id);
    }

    public function test_delete_removes_stock(): void
    {
        $stock = Stock::factory()->create();

        $this->repo->delete($stock);

        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }
}
