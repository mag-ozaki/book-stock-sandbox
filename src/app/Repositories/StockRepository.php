<?php

namespace App\Repositories;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Collection;

class StockRepository
{
    public function allByStore(int $storeId): Collection
    {
        return Stock::with('book')
            ->where('store_id', $storeId)
            ->get();
    }

    /** 自店舗スコープでフェッチ（他店舗は 404） */
    public function findByStoreOrFail(int $id, int $storeId): Stock
    {
        return Stock::where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();
    }

    public function create(array $data): Stock
    {
        return Stock::create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        $stock->update($data);
        return $stock->fresh();
    }

    public function delete(Stock $stock): void
    {
        $stock->delete();
    }
}
