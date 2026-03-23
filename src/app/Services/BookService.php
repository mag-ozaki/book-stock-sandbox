<?php

namespace App\Services;

use App\Models\Book;
use App\Repositories\BookRepository;
use Illuminate\Database\Eloquent\Collection;

class BookService
{
    public function __construct(private BookRepository $repo) {}

    public function listAll(): Collection
    {
        return $this->repo->all();
    }

    public function listByStore(int $storeId): Collection
    {
        return $this->repo->allByStore($storeId);
    }

    public function create(array $data): Book
    {
        return $this->repo->create($data);
    }

    public function update(Book $book, array $data): Book
    {
        return $this->repo->update($book, $data);
    }

    public function delete(Book $book): void
    {
        $this->repo->delete($book);
    }
}
