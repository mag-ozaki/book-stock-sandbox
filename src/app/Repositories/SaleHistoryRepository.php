<?php

namespace App\Repositories;

use App\Models\SaleHistory;
use Illuminate\Database\Eloquent\Collection;

class SaleHistoryRepository
{
    public function allByStore(int $storeId): Collection
    {
        return SaleHistory::where('store_id', $storeId)
            ->with('book')
            ->orderByDesc('sold_at')
            ->get();
    }

    /** 自店舗スコープでフェッチ（他店舗は 404） */
    public function findByStoreOrFail(int $id, int $storeId): SaleHistory
    {
        return SaleHistory::where('id', $id)
            ->where('store_id', $storeId)
            ->with('book')
            ->firstOrFail();
    }

    public function create(array $data): SaleHistory
    {
        return SaleHistory::create($data);
    }
}
