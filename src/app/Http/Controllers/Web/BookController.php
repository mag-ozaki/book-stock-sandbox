<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\BookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct(private BookService $service) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Book::class);

        return inertia('Books/Index', [
            'books' => $this->service->listAll(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Book::class);

        return inertia('Books/Create');
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $this->authorize('create', Book::class);

        $this->service->create($request->validated());

        return redirect()->route('books.index')
            ->with('success', '書籍を登録しました。');
    }

    public function edit(Book $book): Response
    {
        $this->authorize('update', $book);

        return inertia('Books/Edit', [
            'book' => $book,
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $this->service->update($book, $request->validated());

        return redirect()->route('books.index')
            ->with('success', '書籍情報を更新しました。');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $this->service->delete($book);

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
