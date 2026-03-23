<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Store;
use App\Models\StoreUser;

class StorePolicy
{
    /**
     * Admin はすべて許可。StoreUser は店舗管理不可。
     */
    public function before(Admin|StoreUser $user, string $ability): ?bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return false;
    }

    public function viewAny(Admin $user): bool
    {
        return true;
    }

    public function view(Admin $user, Store $store): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        return true;
    }

    public function update(Admin $user, Store $store): bool
    {
        return true;
    }

    public function delete(Admin $user, Store $store): bool
    {
        return true;
    }
}
