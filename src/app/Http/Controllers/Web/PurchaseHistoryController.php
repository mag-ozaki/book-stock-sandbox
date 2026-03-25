<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\PurchaseHistoryRequest;
use App\Models\PurchaseHistory;
use App\Models\StoreUser;
use App\Repositories\BookRepository;
use App\Services\PurchaseHistoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PurchaseHistoryController extends Controller
{
    public function __construct(
        private PurchaseHistoryService $service,
        private BookRepository $bookRepo,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', PurchaseHistory::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        return inertia('PurchaseHistories/Index', [
            'purchaseHistories' => $this->service->listByStore($user->store_id),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PurchaseHistory::class);

        return inertia('PurchaseHistories/Create', [
            'books' => $this->bookRepo->all(),
        ]);
    }

    public function store(PurchaseHistoryRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseHistory::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        $this->service->create($user->store_id, $user->id, $request->validated());

        return redirect()->route('purchase-histories.index')
            ->with('success', '購入履歴を登録しました。');
    }

    public function show(PurchaseHistory $purchaseHistory): Response
    {
        // Route Model Binding では store スコープが効かないため、Service で自店舗スコープで再取得する
        /** @var StoreUser $user */
        $user = auth()->user();

        $purchaseHistory = $this->service->findByStore($purchaseHistory->id, $user->store_id);

        $this->authorize('view', $purchaseHistory);

        return inertia('PurchaseHistories/Show', [
            'purchaseHistory' => $purchaseHistory,
        ]);
    }

    public function destroy(PurchaseHistory $purchaseHistory): RedirectResponse
    {
        // Route Model Binding では store スコープが効かないため、Service で自店舗スコープで再取得する
        /** @var StoreUser $user */
        $user = auth()->user();

        $purchaseHistory = $this->service->findByStore($purchaseHistory->id, $user->store_id);

        $this->authorize('delete', $purchaseHistory);

        $this->service->delete($purchaseHistory);

        return redirect()->route('purchase-histories.index')
            ->with('success', '購入履歴を削除しました。');
    }
}
