<?php

namespace Tests\Unit\Services;

use App\Models\SaleHistory;
use App\Repositories\SaleHistoryRepository;
use App\Services\SaleHistoryService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SaleHistoryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_by_store_delegates_to_repository(): void
    {
        $histories = new Collection();

        $repo = Mockery::mock(SaleHistoryRepository::class);
        $repo->shouldReceive('allByStore')->with(1)->once()->andReturn($histories);

        $this->assertSame($histories, (new SaleHistoryService($repo))->listByStore(1));
    }

    public function test_find_by_store_delegates_to_repository(): void
    {
        $history = new SaleHistory();

        $repo = Mockery::mock(SaleHistoryRepository::class);
        $repo->shouldReceive('findByStoreOrFail')->with(10, 1)->once()->andReturn($history);

        $this->assertSame($history, (new SaleHistoryService($repo))->findByStore(10, 1));
    }

    public function test_create_injects_store_id_from_argument(): void
    {
        $history = new SaleHistory();
        $storeId = 5;
        $input   = ['book_id' => 1, 'quantity' => 2, 'sold_at' => '2026-03-27T10:30:00+09:00'];
        $expected = array_merge($input, ['store_id' => $storeId]);

        $repo = Mockery::mock(SaleHistoryRepository::class);
        $repo->shouldReceive('create')->with($expected)->once()->andReturn($history);

        $this->assertSame($history, (new SaleHistoryService($repo))->create($storeId, $input));
    }

    public function test_create_overwrites_store_id_in_input(): void
    {
        $history = new SaleHistory();
        $storeId = 5;
        $input   = ['book_id' => 1, 'quantity' => 2, 'sold_at' => '2026-03-27T10:30:00+09:00', 'store_id' => 99];
        $expected = ['book_id' => 1, 'quantity' => 2, 'sold_at' => '2026-03-27T10:30:00+09:00', 'store_id' => $storeId];

        $repo = Mockery::mock(SaleHistoryRepository::class);
        $repo->shouldReceive('create')->with($expected)->once()->andReturn($history);

        (new SaleHistoryService($repo))->create($storeId, $input);
    }
}
