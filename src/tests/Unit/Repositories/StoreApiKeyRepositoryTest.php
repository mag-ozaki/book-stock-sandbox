<?php

namespace Tests\Unit\Repositories;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreApiKey;
use App\Repositories\StoreApiKeyRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreApiKeyRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private StoreApiKeyRepository $repo;
    private Admin $admin;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo  = new StoreApiKeyRepository();
        $this->admin = Admin::factory()->create();
        $this->store = Store::factory()->create();
    }

    public function test_list_by_store_returns_only_own_store_api_keys(): void
    {
        $otherStore = Store::factory()->create();

        StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'name'       => 'レジ1',
        ]);
        StoreApiKey::factory()->create([
            'store_id'   => $otherStore->id,
            'created_by' => $this->admin->id,
            'name'       => 'レジ他店',
        ]);

        $result = $this->repo->listByStore($this->store->id);

        $this->assertCount(1, $result);
        $this->assertEquals('レジ1', $result->first()->name);
    }

    public function test_list_by_store_returns_ordered_by_created_at_desc(): void
    {
        $first = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'name'       => '古いキー',
            'created_at' => now()->subMinutes(10),
        ]);
        $second = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'name'       => '新しいキー',
            'created_at' => now(),
        ]);

        $result = $this->repo->listByStore($this->store->id);

        // 最新が先頭
        $this->assertEquals($second->id, $result->first()->id);
    }

    public function test_create_persists_api_key(): void
    {
        $plain   = 'plain-test-key-1234567890abcdef1234';
        $keyHash = hash('sha256', $plain);

        $result = $this->repo->create([
            'store_id'   => $this->store->id,
            'name'       => 'レジ1',
            'key_hash'   => $keyHash,
            'created_by' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('store_api_keys', [
            'store_id' => $this->store->id,
            'name'     => 'レジ1',
        ]);
        $this->assertEquals('レジ1', $result->name);
    }

    public function test_update_persists_changes(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'is_active'  => true,
        ]);

        $updated = $this->repo->update($apiKey, ['is_active' => false]);

        $this->assertFalse($updated->is_active);
        $this->assertDatabaseHas('store_api_keys', [
            'id'        => $apiKey->id,
            'is_active' => false,
        ]);
    }

    public function test_delete_removes_api_key(): void
    {
        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
        ]);

        $this->repo->delete($apiKey);

        $this->assertDatabaseMissing('store_api_keys', ['id' => $apiKey->id]);
    }

    public function test_find_by_key_hash_returns_matching_key(): void
    {
        $plain   = 'findable-key-1234567890abcdef12345';
        $keyHash = hash('sha256', $plain);

        $apiKey = StoreApiKey::factory()->create([
            'store_id'   => $this->store->id,
            'created_by' => $this->admin->id,
            'key_hash'   => $keyHash,
        ]);

        $result = $this->repo->findByKeyHash($keyHash);

        $this->assertNotNull($result);
        $this->assertEquals($apiKey->id, $result->id);
    }

    public function test_find_by_key_hash_returns_null_when_not_found(): void
    {
        $result = $this->repo->findByKeyHash('nonexistent-hash');

        $this->assertNull($result);
    }
}
