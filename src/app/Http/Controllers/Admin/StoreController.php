<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreRequest;
use App\Models\Store;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class StoreController extends Controller
{
    public function __construct(private StoreService $service) {}

    public function index(): Response
    {
        $this->authorize('viewAny', Store::class);

        return inertia('Admin/Stores/Index', [
            'stores' => $this->service->list(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Store::class);

        return inertia('Admin/Stores/Create');
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $this->authorize('create', Store::class);

        $this->service->create($request->validated());

        return redirect()->route('admin.stores.index')
            ->with('success', '店舗を作成しました。');
    }

    public function edit(Store $store): Response
    {
        $this->authorize('update', $store);

        return inertia('Admin/Stores/Edit', [
            'store' => $store,
        ]);
    }

    public function update(StoreRequest $request, Store $store): RedirectResponse
    {
        $this->authorize('update', $store);

        $this->service->update($store, $request->validated());

        return redirect()->route('admin.stores.index')
            ->with('success', '店舗情報を更新しました。');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('delete', $store);

        $this->service->delete($store);

        return redirect()->route('admin.stores.index')
            ->with('success', '店舗を削除しました。');
    }
}
