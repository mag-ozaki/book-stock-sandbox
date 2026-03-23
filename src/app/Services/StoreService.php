<?php

namespace App\Services;

use App\Models\Store;
use App\Repositories\StoreRepository;
use Illuminate\Database\Eloquent\Collection;

class StoreService
{
    public function __construct(private StoreRepository $repo) {}

    public function list(): Collection
    {
        return $this->repo->all();
    }

    public function create(array $data): Store
    {
        return $this->repo->create($data);
    }

    public function update(Store $store, array $data): Store
    {
        return $this->repo->update($store, $data);
    }

    public function delete(Store $store): void
    {
        $this->repo->delete($store);
    }
}
