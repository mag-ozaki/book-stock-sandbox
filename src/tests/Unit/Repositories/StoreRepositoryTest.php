<?php

namespace Tests\Unit\Repositories;

use App\Models\Store;
use App\Repositories\StoreRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private StoreRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StoreRepository();
    }

    public function test_all_returns_stores_ordered_by_name(): void
    {
        Store::factory()->create(['name' => 'Z Store']);
        Store::factory()->create(['name' => 'A Store']);
        Store::factory()->create(['name' => 'M Store']);

        $result = $this->repo->all();

        $this->assertEquals('A Store', $result->first()->name);
        $this->assertEquals('Z Store', $result->last()->name);
    }

    public function test_find_or_fail_returns_store(): void
    {
        $store = Store::factory()->create();

        $result = $this->repo->findOrFail($store->id);

        $this->assertEquals($store->id, $result->id);
    }

    public function test_create_persists_store(): void
    {
        $data = ['name' => 'New Store', 'address' => 'Tokyo', 'phone' => '03-0000-0000'];

        $store = $this->repo->create($data);

        $this->assertDatabaseHas('stores', ['name' => 'New Store']);
        $this->assertEquals('New Store', $store->name);
    }

    public function test_update_persists_changes(): void
    {
        $store = Store::factory()->create(['name' => 'Old Name']);

        $updated = $this->repo->update($store, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('stores', ['id' => $store->id, 'name' => 'New Name']);
    }

    public function test_store_has_store_users_relation(): void
    {
        $store = Store::factory()->create();
        \App\Models\StoreUser::factory()->create(['store_id' => $store->id]);

        $this->assertCount(1, $store->storeUsers);
    }

    public function test_store_has_stocks_relation(): void
    {
        $store = Store::factory()->create();
        \App\Models\Stock::factory()->create(['store_id' => $store->id]);

        $this->assertCount(1, $store->stocks);
    }

    public function test_delete_removes_store(): void
    {
        $store = Store::factory()->create();

        $this->repo->delete($store);

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }
}
