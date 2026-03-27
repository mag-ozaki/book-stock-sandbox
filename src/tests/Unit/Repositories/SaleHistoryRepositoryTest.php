<?php

namespace Tests\Unit\Repositories;

use App\Models\Book;
use App\Models\SaleHistory;
use App\Models\Store;
use App\Repositories\SaleHistoryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaleHistoryRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private SaleHistoryRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new SaleHistoryRepository();
    }

    public function test_all_by_store_returns_only_own_store_histories(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $book       = Book::factory()->create();

        $ownHistory   = SaleHistory::factory()->create(['store_id' => $store->id, 'book_id' => $book->id]);
        $otherHistory = SaleHistory::factory()->create(['store_id' => $otherStore->id, 'book_id' => $book->id]);

        $result = $this->repo->allByStore($store->id);

        $this->assertTrue($result->contains('id', $ownHistory->id));
        $this->assertFalse($result->contains('id', $otherHistory->id));
    }

    public function test_all_by_store_returns_in_sold_at_desc_order(): void
    {
        $store = Store::factory()->create();
        $book  = Book::factory()->create();

        $older = SaleHistory::factory()->create([
            'store_id' => $store->id,
            'book_id'  => $book->id,
            'sold_at'  => now()->subDays(2),
        ]);
        $newer = SaleHistory::factory()->create([
            'store_id' => $store->id,
            'book_id'  => $book->id,
            'sold_at'  => now()->subDay(),
        ]);

        $result = $this->repo->allByStore($store->id);

        $this->assertEquals($newer->id, $result->first()->id);
        $this->assertEquals($older->id, $result->last()->id);
    }

    public function test_all_by_store_eager_loads_book(): void
    {
        $store = Store::factory()->create();
        $book  = Book::factory()->create();

        SaleHistory::factory()->create(['store_id' => $store->id, 'book_id' => $book->id]);

        $result = $this->repo->allByStore($store->id);

        $this->assertTrue($result->first()->relationLoaded('book'));
    }

    public function test_find_by_store_or_fail_returns_own_store_history(): void
    {
        $store   = Store::factory()->create();
        $book    = Book::factory()->create();
        $history = SaleHistory::factory()->create(['store_id' => $store->id, 'book_id' => $book->id]);

        $result = $this->repo->findByStoreOrFail($history->id, $store->id);

        $this->assertEquals($history->id, $result->id);
    }

    public function test_find_by_store_or_fail_throws_for_other_store_history(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $book       = Book::factory()->create();
        $history    = SaleHistory::factory()->create(['store_id' => $otherStore->id, 'book_id' => $book->id]);

        $this->expectException(ModelNotFoundException::class);

        $this->repo->findByStoreOrFail($history->id, $store->id);
    }

    public function test_create_persists_sale_history(): void
    {
        $store = Store::factory()->create();
        $book  = Book::factory()->create();
        $data  = [
            'store_id' => $store->id,
            'book_id'  => $book->id,
            'quantity' => 3,
            'sold_at'  => now(),
        ];

        $history = $this->repo->create($data);

        $this->assertDatabaseHas('sale_histories', [
            'store_id' => $store->id,
            'book_id'  => $book->id,
            'quantity' => 3,
        ]);
        $this->assertEquals(3, $history->quantity);
    }
}
