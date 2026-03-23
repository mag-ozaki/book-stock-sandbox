<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\StoreUser;

class StoreUserPolicy
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

    /** owner / employee ともに自店舗の一覧は参照可能。 */
    public function viewAny(StoreUser $user): bool
    {
        return true;
    }

    /** 自店舗のユーザーのみ参照可能。 */
    public function view(StoreUser $user, StoreUser $target): bool
    {
        return $user->store_id === $target->store_id;
    }

    /** owner のみ作成可能。 */
    public function create(StoreUser $user): bool
    {
        return $user->isOwner();
    }

    /** owner かつ自店舗のユーザーのみ更新可能。 */
    public function update(StoreUser $user, StoreUser $target): bool
    {
        return $user->isOwner() && $user->store_id === $target->store_id;
    }

    /** owner かつ自店舗のユーザーのみ削除可能。自分自身は削除不可。 */
    public function delete(StoreUser $user, StoreUser $target): bool
    {
        return $user->isOwner()
            && $user->store_id === $target->store_id
            && $user->id !== $target->id;
    }
}
