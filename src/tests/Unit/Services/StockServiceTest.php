<?php

namespace Tests\Unit\Services;

use App\Models\Stock;
use App\Repositories\StockRepository;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_by_store_delegates_to_repository(): void
    {
        $stocks = new Collection();

        $repo = Mockery::mock(StockRepository::class);
        $repo->shouldReceive('allByStore')->with(1)->once()->andReturn($stocks);

        $this->assertSame($stocks, (new StockService($repo))->listByStore(1));
    }

    public function test_create_injects_store_id_from_argument(): void
    {
        $stock       = new Stock();
        $storeId     = 5;
        $input       = ['book_id' => 1, 'quantity' => 10];
        $expected    = array_merge($input, ['store_id' => $storeId]);

        $repo = Mockery::mock(StockRepository::class);
        $repo->shouldReceive('create')->with($expected)->once()->andReturn($stock);

        $this->assertSame($stock, (new StockService($repo))->create($storeId, $input));
    }

    public function test_create_overwrites_injected_store_id_in_input(): void
    {
        /** store_id をリクエスト入力から受け取っても、認証ユーザーの store_id で上書きされることを確認 */
        $stock    = new Stock();
        $storeId  = 5;
        $input    = ['book_id' => 1, 'quantity' => 10, 'store_id' => 99];
        $expected = ['book_id' => 1, 'quantity' => 10, 'store_id' => $storeId];

        $repo = Mockery::mock(StockRepository::class);
        $repo->shouldReceive('create')->with($expected)->once()->andReturn($stock);

        (new StockService($repo))->create($storeId, $input);
    }

    public function test_update_delegates_to_repository(): void
    {
        $stock   = new Stock();
        $updated = new Stock();
        $data    = ['quantity' => 20];

        $repo = Mockery::mock(StockRepository::class);
        $repo->shouldReceive('update')->with($stock, $data)->once()->andReturn($updated);

        $this->assertSame($updated, (new StockService($repo))->update($stock, $data));
    }

    public function test_delete_delegates_to_repository(): void
    {
        $stock = new Stock();

        $repo = Mockery::mock(StockRepository::class);
        $repo->shouldReceive('delete')->with($stock)->once();

        (new StockService($repo))->delete($stock);
    }
}
