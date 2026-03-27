<?php

namespace App\Http\Middleware;

use App\Services\StoreApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateStoreApiKey
{
    public function __construct(private StoreApiKeyService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if ($plain === null) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $apiKey = $this->service->authenticate($plain);
        if ($apiKey === null) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        // Store Model Binding が解決済みであることを前提とする
        $store = $request->route('store');
        if ($store === null || $store->id !== $apiKey->store_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($apiKey->allowed_ips !== null && ! in_array($request->ip(), $apiKey->allowed_ips, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->attributes->set('authenticated_api_key', $apiKey);

        return $next($request);
    }
}
