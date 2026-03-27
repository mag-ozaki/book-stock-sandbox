<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\SaleHistory;
use App\Models\StoreUser;

class SaleHistoryPolicy
{
    /**
     * Admin はすべて許可。StoreUser は個別メソッドで判定。
     */
    public function before(Admin|StoreUser $user, string $ability): ?bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return null;
    }

    /**
     * owner / employee ともに自店舗の販売履歴一覧を参照可能。
     */
    public function viewAny(StoreUser $user): bool
    {
        return true;
    }

    /**
     * 自店舗の販売履歴のみ参照可能。
     */
    public function view(StoreUser $user, SaleHistory $saleHistory): bool
    {
        return $user->store_id === $saleHistory->store_id;
    }
}
