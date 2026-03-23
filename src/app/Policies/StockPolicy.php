<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Stock;
use App\Models\StoreUser;

class StockPolicy
{
    /**
     * Admin はすべて許可。StoreUser は個別メソッドで判定。
     * owner / employee ともに自店舗の stocks を CRUD 可能。
     */
    public function before(Admin|StoreUser $user, string $ability): ?bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return null;
    }

    /** 自店舗の在庫一覧は参照可能。 */
    public function viewAny(StoreUser $user): bool
    {
        return true;
    }

    /** 自店舗の在庫のみ参照可能。 */
    public function view(StoreUser $user, Stock $stock): bool
    {
        return $user->store_id === $stock->store_id;
    }

    /** 在庫登録は認証済み store_user であれば可能（store_id はサーバー側で付与）。 */
    public function create(StoreUser $user): bool
    {
        return true;
    }

    /** 自店舗の在庫のみ更新可能。 */
    public function update(StoreUser $user, Stock $stock): bool
    {
        return $user->store_id === $stock->store_id;
    }

    /** 自店舗の在庫のみ削除可能。 */
    public function delete(StoreUser $user, Stock $stock): bool
    {
        return $user->store_id === $stock->store_id;
    }
}
