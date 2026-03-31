<?php

namespace App\Repositories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;

class GenreRepository
{
    public function all(): Collection
    {
        return Genre::orderBy('name')->get();
    }

    public function findOrFail(int $id): Genre
    {
        return Genre::findOrFail($id);
    }

    public function create(array $data): Genre
    {
        return Genre::create($data);
    }

    public function update(Genre $genre, array $data): Genre
    {
        $genre->update($data);
        return $genre->fresh();
    }

    public function delete(Genre $genre): void
    {
        $genre->delete();
    }
}
