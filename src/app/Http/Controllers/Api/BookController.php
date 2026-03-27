<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Store;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(private BookService $bookService) {}

    /**
     * @param Store $store Route Model Binding。AuthenticateStoreApiKey が store_id 整合をチェックするために必要
     */
    public function show(Request $request, Store $store, string $janCode): JsonResponse
    {
        $book = $this->bookService->findByJanCode($janCode);

        if ($book === null) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        return (new BookResource($book))->response();
    }
}
