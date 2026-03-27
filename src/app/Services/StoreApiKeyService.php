<?php

namespace App\Services;

use App\Models\StoreApiKey;
use App\Repositories\StoreApiKeyRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class StoreApiKeyService
{
    public function __construct(private StoreApiKeyRepository $repo) {}

    public function listByStore(int $storeId): Collection
    {
        return $this->repo->listByStore($storeId);
    }

    /**
     * APIキーを発行する。
     * 平文キーを生成・SHA-256 ハッシュ化して保存し、平文とモデルを返す。
     *
     * @return array{plain: string, model: StoreApiKey}
     */
    public function issue(int $storeId, int $adminId, array $data): array
    {
        $plain    = Str::random(40);
        $keyHash  = hash('sha256', $plain);

        $model = $this->repo->create([
            'store_id'    => $storeId,
            'name'        => $data['name'],
            'key_hash'    => $keyHash,
            'allowed_ips' => $data['allowed_ips'] ?? null,
            'expires_at'  => $data['expires_at'] ?? null,
            'created_by'  => $adminId,
        ]);

        return ['plain' => $plain, 'model' => $model];
    }

    /** is_active を切り替える */
    public function toggle(StoreApiKey $apiKey, bool $isActive): StoreApiKey
    {
        return $this->repo->update($apiKey, ['is_active' => $isActive]);
    }

    public function delete(StoreApiKey $apiKey): void
    {
        $this->repo->delete($apiKey);
    }

    /**
     * 平文キーを受け取り、有効な StoreApiKey を返す。
     * is_active が false または有効期限切れの場合は null を返す。
     * 認証成功時に last_used_at を更新する。
     */
    public function authenticate(string $plainKey): ?StoreApiKey
    {
        $hash   = hash('sha256', $plainKey);
        $apiKey = $this->repo->findByKeyHash($hash);

        if ($apiKey === null) {
            return null;
        }

        if (! $apiKey->is_active) {
            return null;
        }

        if ($apiKey->expires_at !== null && $apiKey->expires_at->isPast()) {
            return null;
        }

        $this->repo->update($apiKey, ['last_used_at' => now()]);

        return $apiKey->fresh();
    }
}
