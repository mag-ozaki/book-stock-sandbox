<?php

namespace App\Services;

use App\Models\Genre;
use App\Repositories\GenreRepository;
use Illuminate\Database\Eloquent\Collection;

class GenreService
{
    public function __construct(private GenreRepository $repo) {}

    public function listAll(): Collection
    {
        return $this->repo->all();
    }

    public function create(array $data): Genre
    {
        return $this->repo->create($data);
    }

    public function update(Genre $genre, array $data): Genre
    {
        return $this->repo->update($genre, $data);
    }

    public function delete(Genre $genre): void
    {
        $this->repo->delete($genre);
    }
}
