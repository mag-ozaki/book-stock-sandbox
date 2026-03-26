<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StockRequest;
use App\Models\Book;
use App\Models\Stock;
use App\Models\StoreUser;
use App\Repositories\BookRepository;
use App\Repositories\StockRepository;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Response;

class StockController extends Controller
{
    public function __construct(
        private StockService $service,
        private StockRepository $repo,
        private BookRepository $bookRepo,
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Stock::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        return inertia('Stocks/Index', [
            'stocks' => $this->service->listByStore($user->store_id),
        ]);
    }

    public function export(): HttpResponse
    {
        $this->authorize('viewAny', Stock::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        $csv = $this->service->exportByStore($user->store_id);
        $filename = 'stocks_' . now()->format('Ymd') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Stock::class);

        return inertia('Stocks/Create', [
            'books' => $this->bookRepo->all(),
        ]);
    }

    public function store(StockRequest $request): RedirectResponse
    {
        $this->authorize('create', Stock::class);

        /** @var StoreUser $user */
        $user = auth()->user();

        $this->service->create($user->store_id, $request->validated());

        return redirect()->route('stocks.index')
            ->with('success', '在庫を登録しました。');
    }

    public function edit(Stock $stock): Response
    {
        $this->authorize('update', $stock);

        return inertia('Stocks/Edit', [
            'stock' => $stock->load('book'),
        ]);
    }

    public function update(StockRequest $request, Stock $stock): RedirectResponse
    {
        $this->authorize('update', $stock);

        $this->service->update($stock, $request->only('quantity'));

        return redirect()->route('stocks.index')
            ->with('success', '在庫を更新しました。');
    }

    public function destroy(Stock $stock): RedirectResponse
    {
        $this->authorize('delete', $stock);

        $this->service->delete($stock);

        return redirect()->route('stocks.index')
            ->with('success', '在庫を削除しました。');
    }
}
