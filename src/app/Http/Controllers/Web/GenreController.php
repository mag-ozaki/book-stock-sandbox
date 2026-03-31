<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\GenreRequest;
use App\Models\Genre;
use App\Services\GenreService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class GenreController extends Controller
{
    public function __construct(private GenreService $service) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Genre::class);

        return inertia('Genres/Index', [
            'genres' => $this->service->listAll(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Genre::class);

        return inertia('Genres/Create');
    }

    public function store(GenreRequest $request): RedirectResponse
    {
        $this->authorize('create', Genre::class);

        $this->service->create($request->validated());

        return redirect()->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    public function edit(Genre $genre): Response
    {
        $this->authorize('update', $genre);

        return inertia('Genres/Edit', [
            'genre' => $genre,
        ]);
    }

    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        $this->authorize('update', $genre);

        $this->service->update($genre, $request->validated());

        return redirect()->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $this->authorize('delete', $genre);

        $this->service->delete($genre);

        return redirect()->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}
