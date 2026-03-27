<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SaleHistory;
use App\Models\StoreUser;
use App\Services\SaleHistoryService;
use Inertia\Response;

class SaleHistoryController extends Controller
{
    public function __construct(
        private SaleHistoryService $saleHistoryService,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', SaleHistory::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        return inertia('SaleHistories/Index', [
            'saleHistories' => $this->saleHistoryService->listByStore($user->store_id),
        ]);
    }

    public function show(SaleHistory $sale_history): Response
    {
        // Route Model Binding では store スコープが効かないため、Service で自店舗スコープで再取得する
        /** @var StoreUser $user */
        $user = auth()->user();

        $sale_history = $this->saleHistoryService->findByStore($sale_history->id, $user->store_id);

        $this->authorize('view', $sale_history);

        return inertia('SaleHistories/Show', [
            'saleHistory' => $sale_history,
        ]);
    }
}
