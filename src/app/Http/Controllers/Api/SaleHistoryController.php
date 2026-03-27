<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaleHistoryRequest;
use App\Http\Resources\SaleHistoryResource;
use App\Models\Store;
use App\Services\BookService;
use App\Services\SaleHistoryService;
use Illuminate\Http\JsonResponse;

class SaleHistoryController extends Controller
{
    public function __construct(
        private BookService $bookService,
        private SaleHistoryService $saleHistoryService,
    ) {}

    /**
     * POSターミナルからの販売データを受信して記録する。
     *
     * @param Store $store Route Model Binding。AuthenticateStoreApiKey が store_id 整合をチェックするために必要
     */
    public function store(SaleHistoryRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();

        $book = $this->bookService->findByJanCode($validated['jan_code']);
        if ($book === null) {
            return response()->json([
                'message' => 'The selected jan_code is invalid.',
                'errors'  => ['jan_code' => ['指定されたJANコードの書籍が登録されていません。']],
            ], 422);
        }

        $saleHistory = $this->saleHistoryService->create($store->id, [
            'book_id'         => $book->id,
            'quantity'        => $validated['quantity'],
            'sold_at'         => $validated['sold_at'] ?? now(),
            'pos_terminal_id' => $validated['pos_terminal_id'] ?? null,
        ]);

        return (new SaleHistoryResource($saleHistory->load('book')))->response()->setStatusCode(201);
    }
}
