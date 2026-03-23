<?php

namespace Tests\Unit\Repositories;

use App\Models\Store;
use App\Models\StoreUser;
use App\Repositories\StoreUserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StoreUserRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private StoreUserRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new StoreUserRepository();
    }

    public function test_all_returns_all_store_users_with_store_relation(): void
    {
        StoreUser::factory()->create();
        StoreUser::factory()->create();

        $result = $this->repo->all();

        $this->assertGreaterThanOrEqual(2, $result->count());
        $this->assertTrue($result->first()->relationLoaded('store'));
    }

    public function test_all_by_store_returns_only_own_store_users(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();

        $ownUser   = StoreUser::factory()->create(['store_id' => $store->id]);
        $otherUser = StoreUser::factory()->create(['store_id' => $otherStore->id]);

        $result = $this->repo->allByStore($store->id);

        $this->assertTrue($result->contains('id', $ownUser->id));
        $this->assertFalse($result->contains('id', $otherUser->id));
    }

    public function test_find_or_fail_returns_store_user(): void
    {
        $user = StoreUser::factory()->create();

        $result = $this->repo->findOrFail($user->id);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_store_or_fail_returns_own_store_user(): void
    {
        $store = Store::factory()->create();
        $user  = StoreUser::factory()->create(['store_id' => $store->id]);

        $result = $this->repo->findByStoreOrFail($user->id, $store->id);

        $this->assertEquals($user->id, $result->id);
    }

    public function test_find_by_store_or_fail_throws_for_other_store_user(): void
    {
        $store      = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $user       = StoreUser::factory()->create(['store_id' => $otherStore->id]);

        $this->expectException(ModelNotFoundException::class);

        $this->repo->findByStoreOrFail($user->id, $store->id);
    }

    public function test_create_persists_store_user(): void
    {
        $store = Store::factory()->create();
        $data  = [
            'store_id' => $store->id,
            'name'     => 'New User',
            'email'    => 'newuser@example.com',
            'password' => 'password',
            'role'     => 'employee',
        ];

        $user = $this->repo->create($data);

        $this->assertDatabaseHas('store_users', ['email' => 'newuser@example.com']);
        $this->assertEquals('New User', $user->name);
    }

    public function test_update_persists_changes(): void
    {
        $user = StoreUser::factory()->create(['name' => 'Old Name']);

        $updated = $this->repo->update($user, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('store_users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_delete_removes_store_user(): void
    {
        $user = StoreUser::factory()->create();

        $this->repo->delete($user);

        $this->assertDatabaseMissing('store_users', ['id' => $user->id]);
    }
}
