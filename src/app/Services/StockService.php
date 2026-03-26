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

    public function exportByStore(int $storeId): string
    {
        $stocks = $this->repo->allByStore($storeId);

        $rows = [];
        $rows[] = ['ISBN', 'タイトル', '著者', '出版社', '価格', '在庫数'];

        foreach ($stocks as $stock) {
            $rows[] = [
                $stock->book->isbn ?? '',
                $stock->book->title ?? '',
                $stock->book->author ?? '',
                $stock->book->publisher ?? '',
                $stock->book->price ?? '',
                $stock->quantity,
            ];
        }

        $handle = fopen('php://temp', 'r+');
        // BOM付きUTF-8（Excel対応）
        fwrite($handle, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
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
