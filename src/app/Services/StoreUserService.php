<?php

namespace App\Services;

use App\Models\StoreUser;
use App\Repositories\StoreUserRepository;
use Illuminate\Database\Eloquent\Collection;

class StoreUserService
{
    public function __construct(private StoreUserRepository $repo) {}

    public function listAll(): Collection
    {
        return $this->repo->all();
    }

    public function listByStore(int $storeId): Collection
    {
        return $this->repo->allByStore($storeId);
    }

    public function create(array $data): StoreUser
    {
        // password は StoreUser モデルの hashed cast が自動ハッシュする
        return $this->repo->create($data);
    }

    public function update(StoreUser $storeUser, array $data): StoreUser
    {
        // password が空の場合は更新しない
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $this->repo->update($storeUser, $data);
    }

    public function delete(StoreUser $storeUser): void
    {
        $this->repo->delete($storeUser);
    }
}
