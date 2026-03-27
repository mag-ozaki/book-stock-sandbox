<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\StoreApiKey;
use App\Models\StoreUser;

class StoreApiKeyPolicy
{
    /**
     * Admin は全操作を許可。StoreUser（owner / employee）は全操作を拒否。
     *
     * StoreApiKeyPolicy に到達するのは Admin または StoreUser のいずれかのみのため、
     * nullable でない bool を返す（StorePolicy の ?bool とは異なる）。
     */
    public function before(Admin|StoreUser $user, string $ability): bool
    {
        return $user instanceof Admin;
    }

    // 以下は before() が true を返した Admin のみ到達する

    public function viewAny(Admin $user): bool
    {
        return true;
    }

    public function create(Admin $user): bool
    {
        return true;
    }

    public function update(Admin $user, StoreApiKey $apiKey): bool
    {
        return true;
    }

    public function delete(Admin $user, StoreApiKey $apiKey): bool
    {
        return true;
    }
}
