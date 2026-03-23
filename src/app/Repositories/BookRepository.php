<?php

namespace App\Repositories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Collection;

class BookRepository
{
    /** 全書籍一覧（管理者・共有マスター表示用） */
    public function all(): Collection
    {
        return Book::orderBy('title')->get();
    }

    /** 自店舗の在庫に紐づく書籍一覧 */
    public function allByStore(int $storeId): Collection
    {
        return Book::whereHas('stocks', fn ($q) => $q->where('store_id', $storeId))
            ->orderBy('title')
            ->get();
    }

    public function findOrFail(int $id): Book
    {
        return Book::findOrFail($id);
    }

    public function create(array $data): Book
    {
        return Book::create($data);
    }

    public function update(Book $book, array $data): Book
    {
        $book->update($data);
        return $book->fresh();
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }
}
