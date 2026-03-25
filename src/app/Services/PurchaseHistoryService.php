<?php

namespace App\Services;

use App\Models\PurchaseHistory;
use App\Repositories\PurchaseHistoryRepository;
use Illuminate\Database\Eloquent\Collection;

class PurchaseHistoryService
{
    public function __construct(private PurchaseHistoryRepository $repo) {}

    public function listByStore(int $storeId): Collection
    {
        return $this->repo->allByStore($storeId);
    }

    public function findByStore(int $id, int $storeId): PurchaseHistory
    {
        return $this->repo->findByStoreOrFail($id, $storeId);
    }

    public function create(int $storeId, int $storeUserId, array $data): PurchaseHistory
    {
        // store_id / store_user_id はログインユーザーから導出し、リクエスト入力を信用しない
        $data['store_id']      = $storeId;
        $data['store_user_id'] = $storeUserId;

        return $this->repo->create($data);
    }

    public function delete(PurchaseHistory $purchaseHistory): void
    {
        $this->repo->delete($purchaseHistory);
    }
}
