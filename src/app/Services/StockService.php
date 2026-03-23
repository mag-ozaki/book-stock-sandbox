<?php

namespace App\Services;

use App\Models\Stock;
use App\Repositories\StockRepository;
use Illuminate\Database\Eloquent\Collection;

class StockService
{
    public function __construct(private StockRepository $repo) {}

    public function listByStore(int $storeId): Collection
    {
        return $this->repo->allByStore($storeId);
    }

    public function create(int $storeId, array $data): Stock
    {
        // store_id はログインユーザーから導出し、リクエスト入力を信用しない
        $data['store_id'] = $storeId;

        return $this->repo->create($data);
    }

    public function update(Stock $stock, array $data): Stock
    {
        return $this->repo->update($stock, $data);
    }

    public function delete(Stock $stock): void
    {
        $this->repo->delete($stock);
    }
}
