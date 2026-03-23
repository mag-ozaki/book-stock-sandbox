<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Book;
use App\Models\StoreUser;

class BookPolicy
{
    /**
     * Admin はすべて許可。StoreUser は個別メソッドで判定。
     *
     * books は共有マスターのため owner / employee ともに CRUD 可能。
     * 「自店舗で扱う books」の絞り込みはクエリ層（Repository）で行う。
     */
    public function before(Admin|StoreUser $user, string $ability): ?bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        return null;
    }

    public function viewAny(StoreUser $user): bool
    {
        return true;
    }

    public function view(StoreUser $user, Book $book): bool
    {
        return true;
    }

    public function create(StoreUser $user): bool
    {
        return true;
    }

    public function update(StoreUser $user, Book $book): bool
    {
        return true;
    }

    public function delete(StoreUser $user, Book $book): bool
    {
        return true;
    }
}
