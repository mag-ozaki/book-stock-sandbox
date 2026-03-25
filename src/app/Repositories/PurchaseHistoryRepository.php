<?php

namespace App\Repositories;

use App\Models\PurchaseHistory;
use Illuminate\Database\Eloquent\Collection;

class PurchaseHistoryRepository
{
    public function allByStore(int $storeId): Collection
    {
        return PurchaseHistory::with(['book', 'storeUser'])
            ->where('store_id', $storeId)
            ->orderByDesc('purchased_at')
            ->get();
    }

    /** 自店舗スコープでフェッチ（他店舗は 404） */
    public function findByStoreOrFail(int $id, int $storeId): PurchaseHistory
    {
        return PurchaseHistory::with(['book', 'storeUser'])
            ->where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();
    }

    public function create(array $data): PurchaseHistory
    {
        return PurchaseHistory::create($data);
    }

    public function delete(PurchaseHistory $purchaseHistory): void
    {
        $purchaseHistory->delete();
    }
}
