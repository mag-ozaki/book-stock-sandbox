<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\PurchaseHistory;
use App\Models\StoreUser;

class PurchaseHistoryPolicy
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

    /** 自店舗の購入履歴一覧は参照可能（owner / employee ともに可）。 */
    public function viewAny(StoreUser $user): bool
    {
        return true;
    }

    /** 自店舗の購入履歴のみ参照可能。 */
    public function view(StoreUser $user, PurchaseHistory $purchaseHistory): bool
    {
        return $user->store_id === $purchaseHistory->store_id;
    }

    /** 購入履歴の登録は認証済み store_user であれば可能（store_id / store_user_id はサーバー側で付与）。 */
    public function create(StoreUser $user): bool
    {
        return true;
    }

    /** 削除は owner のみ、かつ自店舗のレコードのみ。 */
    public function delete(StoreUser $user, PurchaseHistory $purchaseHistory): bool
    {
        return $user->isOwner() && $user->store_id === $purchaseHistory->store_id;
    }
}
