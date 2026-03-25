<?php

namespace Tests\Unit\Services;

use App\Models\PurchaseHistory;
use App\Repositories\PurchaseHistoryRepository;
use App\Services\PurchaseHistoryService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PurchaseHistoryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_list_by_store_delegates_to_repository(): void
    {
        $histories = new Collection();

        $repo = Mockery::mock(PurchaseHistoryRepository::class);
        $repo->shouldReceive('allByStore')->with(1)->once()->andReturn($histories);

        $this->assertSame($histories, (new PurchaseHistoryService($repo))->listByStore(1));
    }

    public function test_find_by_store_delegates_to_repository(): void
    {
        $ph = new PurchaseHistory();

        $repo = Mockery::mock(PurchaseHistoryRepository::class);
        $repo->shouldReceive('findByStoreOrFail')->with(10, 1)->once()->andReturn($ph);

        $this->assertSame($ph, (new PurchaseHistoryService($repo))->findByStore(10, 1));
    }

    public function test_create_injects_store_id_and_store_user_id_from_arguments(): void
    {
        $ph          = new PurchaseHistory();
        $storeId     = 5;
        $storeUserId = 3;
        $input       = ['book_id' => 1, 'quantity' => 2, 'purchased_at' => '2025-01-15'];
        $expected    = array_merge($input, ['store_id' => $storeId, 'store_user_id' => $storeUserId]);

        $repo = Mockery::mock(PurchaseHistoryRepository::class);
        $repo->shouldReceive('create')->with($expected)->once()->andReturn($ph);

        $this->assertSame($ph, (new PurchaseHistoryService($repo))->create($storeId, $storeUserId, $input));
    }

    public function test_create_overwrites_injected_ids_in_input(): void
    {
        /** store_id / store_user_id をリクエスト入力から受け取っても、認証ユーザーの値で上書きされることを確認 */
        $ph          = new PurchaseHistory();
        $storeId     = 5;
        $storeUserId = 3;
        $input       = ['book_id' => 1, 'quantity' => 2, 'purchased_at' => '2025-01-15', 'store_id' => 99, 'store_user_id' => 99];
        $expected    = ['book_id' => 1, 'quantity' => 2, 'purchased_at' => '2025-01-15', 'store_id' => $storeId, 'store_user_id' => $storeUserId];

        $repo = Mockery::mock(PurchaseHistoryRepository::class);
        $repo->shouldReceive('create')->with($expected)->once()->andReturn($ph);

        (new PurchaseHistoryService($repo))->create($storeId, $storeUserId, $input);
    }

    public function test_delete_delegates_to_repository(): void
    {
        $ph = new PurchaseHistory();

        $repo = Mockery::mock(PurchaseHistoryRepository::class);
        $repo->shouldReceive('delete')->with($ph)->once();

        (new PurchaseHistoryService($repo))->delete($ph);
    }
}
