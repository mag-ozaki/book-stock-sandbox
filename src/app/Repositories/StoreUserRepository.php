<?php

namespace App\Repositories;

use App\Models\StoreUser;
use Illuminate\Database\Eloquent\Collection;

class StoreUserRepository
{
    /** admin 向け: 全店舗のユーザー一覧 */
    public function all(): Collection
    {
        return StoreUser::with('store')->orderBy('name')->get();
    }

    /** web 向け: 自店舗のユーザー一覧 */
    public function allByStore(int $storeId): Collection
    {
        return StoreUser::where('store_id', $storeId)->orderBy('name')->get();
    }

    public function findOrFail(int $id): StoreUser
    {
        return StoreUser::findOrFail($id);
    }

    /** web 向け: 自店舗スコープでフェッチ（他店舗は 404） */
    public function findByStoreOrFail(int $id, int $storeId): StoreUser
    {
        return StoreUser::where('id', $id)
            ->where('store_id', $storeId)
            ->firstOrFail();
    }

    public function create(array $data): StoreUser
    {
        return StoreUser::create($data);
    }

    public function update(StoreUser $storeUser, array $data): StoreUser
    {
        $storeUser->update($data);
        return $storeUser->fresh();
    }

    public function delete(StoreUser $storeUser): void
    {
        $storeUser->delete();
    }
}
