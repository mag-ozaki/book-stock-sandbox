# 書籍単価照会 API 仕様

## 機能概要

POS端末から JANコード（26桁）を送信し、該当書籍の単価と書籍情報を JSON で返す読み取り専用 API。
認証は `store_api_keys` テーブルの Bearer トークンで行う（`api-key-spec.md` 記載の認証フローと共通）。

本機能は `routes/api.php` の初回作成を伴う。販売履歴 API（POST /api/stores/{store}/sale-histories）と同一ミドルウェアスタック・同一プレフィックスに収容する。

---

## エンドポイント

```
GET /api/stores/{store}/books/{jan_code}
```

### パラメータ

| パラメータ | 種別 | 型 | 説明 |
|---|---|---|---|
| store | パス | integer | 店舗 ID（Route Model Binding → Store モデル） |
| jan_code | パス | string(26) | JANコード（上段13桁+下段13桁の26桁数字） |

`{jan_code}` にはルート制約 `->where('jan_code', '\d{26}')` を付与する。26桁数字以外のリクエストはルートレベルで 404 を返し、コントローラーに到達しない。

### リクエストヘッダー

```
Authorization: Bearer <api_key_plain>
```

### 成功レスポンス `200 OK`

```json
{
  "data": {
    "id": 5,
    "jan_code": "97840000000001920000000000",
    "title": "Laravel入門",
    "author": "山田 太郎",
    "publisher": "技術書院",
    "price": 3080
  }
}
```

### エラーレスポンス

| HTTP ステータス | 原因 |
|---|---|
| 401 | Bearer トークンなし・キーが存在しない・is_active=false・expires_at 超過 |
| 403 | URL の store_id とキーの store_id が不一致、または IP 制限による拒否 |
| 404 | store が存在しない、または指定 JANコードの書籍が登録されていない |

---

## bootstrap/app.php 変更

`withRouting` に `api:` パラメータを追加する。`then:` クロージャは admin ルート用に存続させる。

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',   // 追加
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    then: function () {
        Route::middleware('web')
            ->group(base_path('routes/admin.php'));
    },
)
```

`api:` パラメータを使うことで、Laravel は自動的に `api` ミドルウェアグループ（`throttle:api`, `SubstituteBindings`）を `routes/api.php` 全体に適用する。

また `withMiddleware` に `api.key` エイリアスを登録する（routes/api.php 内では FQCN 直接指定も可）。

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
    ]);
    $middleware->alias([
        'api.key' => \App\Http\Middleware\AuthenticateStoreApiKey::class,
    ]);
})
```

---

## routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware([
    \App\Http\Middleware\RestrictPosIpAddress::class,
    \App\Http\Middleware\AuthenticateStoreApiKey::class,
])->prefix('stores/{store}')->group(function () {

    // 販売履歴記録（機能4）
    Route::post('/sale-histories', [\App\Http\Controllers\Api\SaleHistoryController::class, 'store'])
        ->name('api.stores.sale-histories.store');

    // 書籍単価照会（機能3）
    Route::get('/books/{jan_code}', [\App\Http\Controllers\Api\BookController::class, 'show'])
        ->where('jan_code', '\d{26}')
        ->name('api.stores.books.show');
});
```

**設計注記**:
- `prefix('stores/{store}')` でネストし、将来の API 追加（在庫照会など）も同じグループに収容できる構造とする
- `{store}` の Route Model Binding は `api` ミドルウェアグループの `SubstituteBindings` が処理する
- `RestrictPosIpAddress` → `AuthenticateStoreApiKey` の順序とする
- 機能4（販売履歴記録）の Controller はまだ存在しないが、ルートはプレースホルダーとして含める

---

## Middleware: `AuthenticateStoreApiKey`

ファイル: `app/Http/Middleware/AuthenticateStoreApiKey.php`

**処理フロー**:
1. `$request->bearerToken()` で平文キーを取得。なければ 401
2. `StoreApiKeyService::authenticate($plain)` でキーを検証。null なら 401（`is_active=false` / `expires_at` 超過 / 存在しない場合すべて null）
3. `$request->route('store')->id` と `$apiKey->store_id` を比較。不一致なら 403
4. `$apiKey->allowed_ips !== null` の場合、`$request->ip()` が配列に含まれなければ 403
5. `$request->attributes->set('authenticated_api_key', $apiKey)` で後続へ渡す
6. `$next($request)`

**実装サンプル**:

```php
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

        if ($apiKey->allowed_ips !== null && !in_array($request->ip(), $apiKey->allowed_ips, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->attributes->set('authenticated_api_key', $apiKey);

        return $next($request);
    }
}
```

---

## Middleware: `RestrictPosIpAddress`

ファイル: `app/Http/Middleware/RestrictPosIpAddress.php`

本機能では IP 制限は `AuthenticateStoreApiKey` の `allowed_ips` チェックで担保するため、`RestrictPosIpAddress` はパススルーのみのスタブとして実装する。将来、グローバルな IP ホワイトリスト（管理者が一括設定する用途）が必要になった場合に実装を追加する。

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictPosIpAddress
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
```

---

## BookResource

ファイル: `app/Http/Resources/BookResource.php`

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'jan_code'  => $this->jan_code,
            'title'     => $this->title,
            'author'    => $this->author,
            'publisher' => $this->publisher,
            'price'     => $this->price,
        ];
    }
}
```

`created_at` / `updated_at` は POS ユースケースで不要なため除外する。
Inertia 用の props 整形には使用しない（既存の Inertia コントローラーへの変更は不要）。

レスポンスは `{"data": {...}}` でラップされる（Laravel JsonResource のデフォルト）。`withoutWrapping()` は呼ばない。

---

## Controller: `Api\BookController`

ファイル: `app/Http/Controllers/Api/BookController.php`

```php
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
```

**設計注記**:
- `Store $store` は Route Model Binding（存在しない store_id は自動 404）
- `string $janCode` は Route 制約で 26 桁数字であることが保証済み
- `BookService::findByJanCode()` は実装済み
- Policy による認可は行わない。APIキー認証はミドルウェアで完結しており、`books` は共有マスターのため Store スコープ制御も不要
- `$store` 変数は直接使わないが、`AuthenticateStoreApiKey` が `$request->route('store')` を参照するために必要

---

## テスト方針

ファイル: `tests/Feature/Api/BookPriceApiTest.php`

クラス: `BookPriceApiTest extends TestCase`
トレイト: `DatabaseTransactions`

### 前提セットアップ

```php
protected function setUp(): void
{
    parent::setUp();
    $this->store  = Store::factory()->create();
    $this->book   = Book::factory()->create(); // jan_code は Factory で26桁生成
    $this->admin  = Admin::factory()->create();
    $this->plain  = Str::random(40);
    $this->apiKey = StoreApiKey::factory()->create([
        'store_id'   => $this->store->id,
        'key_hash'   => hash('sha256', $this->plain),
        'is_active'  => true,
        'expires_at' => null,
        'allowed_ips'=> null,
        'created_by' => $this->admin->id,
    ]);
}
```

### テストケース一覧

| テストケース | 期待レスポンス | 確認ポイント |
|---|---|---|
| 有効なキーで既存 JANコードを照会 | 200 | `data.id`, `data.price`, `data.jan_code` が正しい値 |
| price が null の書籍 | 200 | `data.price` が `null` |
| 存在しない JANコード | 404 | `message` キーが存在する |
| 25桁の JANコード | 404 | ルート制約により書籍照会まで到達しない |
| 数字以外を含む JANコード | 404 | ルート制約による |
| Bearer トークンなし | 401 | |
| 無効化済みキー（is_active=false） | 401 | |
| 有効期限切れキー（expires_at が過去） | 401 | |
| 別店舗のキーで照会 | 403 | キーの store_id と URL の store_id が不一致 |
| IP制限あり・許可外 IP から照会 | 403 | `withServerVariables(['REMOTE_ADDR' => '9.9.9.9'])` を使用 |
| IP制限あり・許可 IP から照会 | 200 | |
| 存在しない store_id | 404 | Store Model Binding による自動 404 |

### IP 制限テストの実装方針

```php
$this->withServerVariables(['REMOTE_ADDR' => '9.9.9.9'])
     ->withHeader('Authorization', 'Bearer '.$this->plain)
     ->getJson("/api/stores/{$this->store->id}/books/{$this->book->jan_code}");
```

---

## 実装タスク

### バックエンド

- [ ] `bootstrap/app.php`: `withRouting` に `api: __DIR__.'/../routes/api.php'` を追加
- [ ] `bootstrap/app.php`: `withMiddleware` に `api.key` エイリアスを登録
- [ ] Middleware: `app/Http/Middleware/AuthenticateStoreApiKey.php` を新規作成
- [ ] Middleware: `app/Http/Middleware/RestrictPosIpAddress.php` をスタブとして新規作成
- [ ] `routes/api.php` を新規作成（書籍単価照会・販売履歴のルートを含む）
- [ ] Resource: `app/Http/Resources/BookResource.php` を新規作成（6フィールド）
- [ ] Controller: `app/Http/Controllers/Api/BookController.php` を新規作成（`show` アクション）

### テスト

- [ ] Feature: `tests/Feature/Api/BookPriceApiTest.php` を新規作成（12ケース）

### フロントエンド

なし（JSON API のため Vue ページは不要）

---

## 既存コードとの依存関係

本機能の実装に際して変更が不要なコンポーネント:

| コンポーネント | 理由 |
|---|---|
| `BookService::findByJanCode()` | `jan-code-spec.md` で実装済み |
| `BookRepository::findByJanCode()` | `jan-code-spec.md` で実装済み |
| `StoreApiKeyService::authenticate()` | 実装済み |
| `StoreApiKeyRepository::findByKeyHash()` | 実装済み |
| `StoreApiKey` モデル | 実装済み |
| `Store` モデル | 実装済み |
| `Book` モデル | 実装済み |

本機能は「配線（bootstrap + routes）+ Middleware + Resource + Controller」の追加のみで完結する。
