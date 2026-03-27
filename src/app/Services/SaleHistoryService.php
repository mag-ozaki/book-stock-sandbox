<?php

namespace App\Services;

use App\Models\SaleHistory;
use App\Repositories\SaleHistoryRepository;
use Illuminate\Database\Eloquent\Collection;

class SaleHistoryService
{
    public function __construct(private SaleHistoryRepository $repo) {}

    public function listByStore(int $storeId): Collection
    {
        return $this->repo->allByStore($storeId);
    }

    public function findByStore(int $id, int $storeId): SaleHistory
    {
        return $this->repo->findByStoreOrFail($id, $storeId);
    }

    /**
     * 販売履歴を作成する。
     * store_id は認証済みの URL パラメータから注入し、リクエスト入力を信用しない。
     */
    public function create(int $storeId, array $data): SaleHistory
    {
        return $this->repo->create(array_merge($data, ['store_id' => $storeId]));
    }
}
