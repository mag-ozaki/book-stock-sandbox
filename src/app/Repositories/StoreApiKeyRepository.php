<?php

namespace App\Repositories;

use App\Models\StoreApiKey;
use Illuminate\Database\Eloquent\Collection;

class StoreApiKeyRepository
{
    /** 店舗の APIキー一覧（created_at 降順） */
    public function listByStore(int $storeId): Collection
    {
        return StoreApiKey::where('store_id', $storeId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): StoreApiKey
    {
        return StoreApiKey::create($data);
    }

    public function update(StoreApiKey $apiKey, array $data): StoreApiKey
    {
        $apiKey->update($data);
        return $apiKey->fresh();
    }

    public function delete(StoreApiKey $apiKey): void
    {
        $apiKey->delete();
    }

    /** key_hash で検索（認証用） */
    public function findByKeyHash(string $hash): ?StoreApiKey
    {
        return StoreApiKey::where('key_hash', $hash)->first();
    }
}
