<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Genre;
use App\Models\StoreUser;

class GenrePolicy
{
    /**
     * Admin はすべて許可。StoreUser は個別メソッドで判定。
     *
     * genres は books と同じ共有マスターのため owner / employee ともに CRUD 可能。
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

    public function view(StoreUser $user, Genre $genre): bool
    {
        return true;
    }

    public function create(StoreUser $user): bool
    {
        return true;
    }

    public function update(StoreUser $user, Genre $genre): bool
    {
        return true;
    }

    public function delete(StoreUser $user, Genre $genre): bool
    {
        return true;
    }
}
