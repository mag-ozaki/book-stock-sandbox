<?php

namespace App\Repositories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository
{
    public function all(): Collection
    {
        return Store::orderBy('name')->get();
    }

    public function findOrFail(int $id): Store
    {
        return Store::findOrFail($id);
    }

    public function create(array $data): Store
    {
        return Store::create($data);
    }

    public function update(Store $store, array $data): Store
    {
        $store->update($data);
        return $store->fresh();
    }

    public function delete(Store $store): void
    {
        $store->delete();
    }
}
