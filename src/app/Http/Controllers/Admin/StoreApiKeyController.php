<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StoreApiKeyRequest;
use App\Http\Requests\Admin\StoreApiKeyToggleRequest;
use App\Models\Store;
use App\Models\StoreApiKey;
use App\Services\StoreApiKeyService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class StoreApiKeyController extends Controller
{
    public function __construct(private StoreApiKeyService $service) {}

    public function index(Store $store): Response
    {
        $this->authorize('viewAny', StoreApiKey::class);

        $apiKeys = $this->service->listByStore($store->id);

        return inertia('Admin/StoreApiKeys/Index', [
            'store'          => $store->only('id', 'name'),
            'apiKeys'        => $apiKeys->map(fn (StoreApiKey $k) => [
                'id'           => $k->id,
                'name'         => $k->name,
                'allowed_ips'  => $k->allowed_ips,
                'is_active'    => $k->is_active,
                'last_used_at' => $k->last_used_at?->toIso8601String(),
                'expires_at'   => $k->expires_at?->toIso8601String(),
                'created_at'   => $k->created_at->toIso8601String(),
            ]),
            'newlyIssuedKey' => session('newly_issued_key'),
        ]);
    }

    public function store(StoreApiKeyRequest $request, Store $store): RedirectResponse
    {
        $this->authorize('create', StoreApiKey::class);

        $result = $this->service->issue(
            $store->id,
            auth('admin')->id(),
            $request->validated()
        );

        session()->flash('newly_issued_key', $result['plain']);

        return redirect()->route('admin.stores.api-keys.index', $store)
            ->with('success', 'APIキーを発行しました。発行されたキーは一度しか表示されません。');
    }

    public function update(StoreApiKeyToggleRequest $request, Store $store, StoreApiKey $apiKey): RedirectResponse
    {
        $this->authorize('update', $apiKey);

        if ($store->id !== $apiKey->store_id) {
            abort(403);
        }

        $this->service->toggle($apiKey, $request->validated()['is_active']);

        $message = $request->validated()['is_active']
            ? 'APIキーを有効化しました。'
            : 'APIキーを無効化しました。';

        return redirect()->route('admin.stores.api-keys.index', $store)
            ->with('success', $message);
    }

    public function destroy(Store $store, StoreApiKey $apiKey): RedirectResponse
    {
        $this->authorize('delete', $apiKey);

        if ($store->id !== $apiKey->store_id) {
            abort(403);
        }

        $this->service->delete($apiKey);

        return redirect()->route('admin.stores.api-keys.index', $store)
            ->with('success', 'APIキーを削除しました。');
    }
}
