<?php

namespace Tests\Unit\Services;

use App\Models\Store;
use App\Repositories\StoreRepository;
use App\Services\StoreService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class StoreServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_delegates_to_repository(): void
    {
        $stores = new Collection();

        $repo = Mockery::mock(StoreRepository::class);
        $repo->shouldReceive('all')->once()->andReturn($stores);

        $this->assertSame($stores, (new StoreService($repo))->list());
    }

    public function test_create_delegates_to_repository(): void
    {
        $store = new Store();
        $data  = ['name' => 'Test Store'];

        $repo = Mockery::mock(StoreRepository::class);
        $repo->shouldReceive('create')->with($data)->once()->andReturn($store);

        $this->assertSame($store, (new StoreService($repo))->create($data));
    }

    public function test_update_delegates_to_repository(): void
    {
        $store   = new Store();
        $updated = new Store();
        $data    = ['name' => 'Updated'];

        $repo = Mockery::mock(StoreRepository::class);
        $repo->shouldReceive('update')->with($store, $data)->once()->andReturn($updated);

        $this->assertSame($updated, (new StoreService($repo))->update($store, $data));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $store = new Store();

        $repo = Mockery::mock(StoreRepository::class);
        $repo->shouldReceive('delete')->with($store)->once();

        (new StoreService($repo))->delete($store);
    }
}
