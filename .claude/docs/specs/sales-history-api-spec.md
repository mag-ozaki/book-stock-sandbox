# 販売履歴API（SaleHistory）機能仕様

## 機能概要

本屋が顧客に書籍を販売した際の売上履歴を管理する機能。
POSターミナル（店内PC）から HTTP POST で販売データを受信し、記録する。
オーナー・従業員は管理画面から自店舗の販売履歴を参照できる。
admin は管理画面からAPIキーの発行・無効化を行う。

### 用語の定義（混同防止）

| 物理テーブル名 | 意味 |
|---|---|
| `purchase_histories` | 書籍の**仕入れ**履歴（既存機能） |
| `sale_histories` | 書籍の**販売**（顧客への売上）履歴（本機能） |

### `purchase_histories` リネームについて

`purchase_histories` は仕入れ履歴として実装済み（Migration・Model・Repository・Service・Controller・Vue・テストすべて完成）。
リネームコストが高く、`sale_histories` と明示的に命名することで混同を回避できるため、リネームは行わない。

---

## 影響範囲

| 区分 | 対象 |
|---|---|
| DB | `sale_histories` テーブル新規作成 |
| DB | `store_api_keys` テーブル新規作成 |
| Model | `SaleHistory` 新規作成 |
| Model | `StoreApiKey` 新規作成 |
| Model | `Store` に `saleHistories()`, `apiKeys()` リレーション追加 |
| Migration | `create_sale_histories_table` 新規作成 |
| Migration | `create_store_api_keys_table` 新規作成 |
| Repository | `SaleHistoryRepository` 新規作成 |
| Repository | `StoreApiKeyRepository` 新規作成 |
| Service | `SaleHistoryService` 新規作成 |
| Service | `StoreApiKeyService` 新規作成 |
| Controller | `Api/SaleHistoryController` 新規作成（POS向けAPI） |
| Controller | `Web/SaleHistoryController` 新規作成（管理画面閲覧） |
| Controller | `Admin/StoreApiKeyController` 新規作成（APIキー管理） |
| Form Request | `Api/SaleHistoryRequest` 新規作成 |
| Form Request | `Admin/StoreApiKeyRequest` 新規作成 |
| Policy | `SaleHistoryPolicy` 新規作成 |
| Policy | `StoreApiKeyPolicy` 新規作成 |
| Middleware | `App\Http\Middleware\AuthenticateStoreApiKey` 新規作成 |
| Middleware | `App\Http\Middleware\RestrictPosIpAddress` 新規作成（将来用スタブ） |
| Routes | `routes/api.php` に POS エンドポイント追加 |
| Routes | `routes/web.php` に販売履歴閲覧ルート追加 |
| Routes | `routes/admin.php` に APIキー管理ルート追加 |
| Vue Pages | `SaleHistories/Index.vue`, `SaleHistories/Show.vue` 新規作成 |
| Vue Pages | `Admin/StoreApiKeys/Index.vue` 新規作成 |
| Factory | `SaleHistoryFactory`, `StoreApiKeyFactory` 新規作成 |

---

## DB スキーマ

### テーブル: `sale_histories`

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| store_id | bigint | NOT NULL, FK → stores.id | 販売した店舗 |
| book_id | bigint | NOT NULL, FK → books.id | 販売した書籍 |
| quantity | integer | NOT NULL, min=1 | 販売冊数 |
| sold_at | timestamp | NOT NULL | 販売日時（POS端末の時刻） |
| pos_terminal_id | string(100) | nullable | POS端末識別子（任意） |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**外部キー制約**:
- `store_id` → `stores.id` (CASCADE DELETE)
- `book_id` → `books.id` (RESTRICT)

**インデックス**:
- `(store_id, sold_at)` 複合インデックス（一覧検索の高速化）

**設計注記**:
- `store_user_id` は持たない。POS端末からの送信であり、特定の担当者に紐付かない（`purchase_histories` と異なる点）。
- 販売履歴は不変データとして扱う。更新・編集は不可。

### テーブル: `store_api_keys`

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| store_id | bigint | NOT NULL, FK → stores.id | 紐付く店舗 |
| name | string(100) | NOT NULL | キーの識別名（例: "レジ1"） |
| key_hash | string(64) | NOT NULL, UNIQUE | SHA-256 ハッシュ値（平文は発行時のみ返す） |
| allowed_ips | json | nullable | 許可IPアドレスのリスト（null = IP制限なし） |
| is_active | boolean | NOT NULL, default=true | 有効フラグ |
| last_used_at | timestamp | nullable | 最終使用日時 |
| expires_at | timestamp | nullable | 有効期限（null = 無期限） |
| created_by | bigint | NOT NULL, FK → admins.id | 発行した admin |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**外部キー制約**:
- `store_id` → `stores.id` (CASCADE DELETE)
- `created_by` → `admins.id` (RESTRICT)

**インデックス**:
- `key_hash` UNIQUE インデックス（認証時の検索）
- `store_id` インデックス

**APIキー設計の判断理由**:
`stores.api_key` として単一カラムに持つ案もあったが、以下の理由で別テーブルを採用する:
- 将来的なキーローテーション（旧キーを残しつつ新キー発行）に対応できる
- POS端末ごとに異なるキーを発行してアクセス制御できる
- `allowed_ips` を端末単位で設定できる
- `last_used_at` / `expires_at` 管理が stores テーブルの責務から外れる

---

## リレーション

```
SaleHistory belongsTo Store
SaleHistory belongsTo Book

Store hasMany SaleHistory
Store hasMany StoreApiKey
Book hasMany SaleHistory

StoreApiKey belongsTo Store
StoreApiKey belongsTo Admin (created_by)
```

---

## セキュリティ設計

### 認証フロー（POS端末 → API）

```
POST /api/stores/{store}/sale-histories
Authorization: Bearer <api_key_plain>
```

1. `AuthenticateStoreApiKey` Middleware:
   - Bearer トークンを SHA-256 ハッシュ化し `store_api_keys.key_hash` と照合
   - 該当キーが存在しない場合は `401` を返す
   - `is_active = false` の場合は `401` を返す
   - `expires_at` が過去の場合は `401` を返す
   - URLの `{store}` と `store_api_keys.store_id` が一致しない場合は `403` を返す（他店舗への不正送信を防ぐ）
   - キーの `allowed_ips` が null でなく、リクエストIPが含まれない場合は `403` を返す
   - 認証成功時に `last_used_at` を更新する

### 設計注記: IP制限の実装位置

`allowed_ips` は APIキーに紐付くため、IPチェックはキー特定後（`AuthenticateStoreApiKey` 内）に行う。
`RestrictPosIpAddress` Middleware は将来的なグローバルIPブロックリスト用途のスタブとして用意する。

### APIキーのライフサイクル

- 発行: admin が管理画面から発行。平文キーは発行時のレスポンス（セッションフラッシュ）にのみ含める（以降参照不可）
- 保存: SHA-256 ハッシュのみ DB に保存。`hash('sha256', $plainKey)` で生成
- 無効化: `is_active = false` に更新（物理削除はしない。`last_used_at` を監査に使うため）

---

## APIエンドポイント仕様

### `POST /api/stores/{store}/sale-histories`

**目的**: POSターミナルから販売データを送信する

**Middleware**: `AuthenticateStoreApiKey`, `RestrictPosIpAddress`

**リクエストヘッダー**:
```
Content-Type: application/json
Authorization: Bearer <api_key_plain>
```

**リクエストボディ**:
```json
{
  "book_id": 42,
  "quantity": 2,
  "sold_at": "2026-03-27T10:30:00+09:00",
  "pos_terminal_id": "REGISTER-1"
}
```

| フィールド | 型 | 必須 | バリデーション |
|---|---|---|---|
| book_id | integer | 必須 | exists:books,id |
| quantity | integer | 必須 | min:1, max:9999 |
| sold_at | string (ISO 8601) | 必須 | date |
| pos_terminal_id | string | 任意 | max:100 |

**成功レスポンス** `201 Created`:
```json
{
  "data": {
    "id": 123,
    "store_id": 1,
    "book_id": 42,
    "quantity": 2,
    "sold_at": "2026-03-27T10:30:00+09:00",
    "pos_terminal_id": "REGISTER-1"
  }
}
```

**エラーレスポンス**:

| HTTP ステータス | 原因 |
|---|---|
| 401 | APIキーが無効・存在しない・期限切れ |
| 403 | IPアドレス制限により拒否、またはキーの store_id と URL の store が不一致 |
| 404 | 指定された store が存在しない |
| 422 | バリデーションエラー |

---

## 管理画面設計

### Admin: APIキー管理

#### `GET /admin/stores/{store}/api-keys`

Route名: `admin.stores.api-keys.index`

Props:
```
store: { id: number, name: string }
apiKeys: Array<{
  id: number,
  name: string,
  allowed_ips: string[] | null,
  is_active: boolean,
  last_used_at: string | null,
  expires_at: string | null,
  created_at: string
}>
newlyIssuedKey: string | null   // 直前の発行時のみセッションフラッシュから渡す
```

#### `POST /admin/stores/{store}/api-keys`

Route名: `admin.stores.api-keys.store`

リクエストボディ:
```
name: string (required, max:100)
allowed_ips: string[] | null (optional, 各要素は valid IP)
expires_at: string | null (optional, ISO 8601 date)
```

レスポンス: `302` redirect to `admin.stores.api-keys.index`（セッションフラッシュに `newly_issued_key` を含める）

#### `PATCH /admin/stores/{store}/api-keys/{api_key}`

Route名: `admin.stores.api-keys.update`

リクエストボディ: `is_active: boolean (required)`

レスポンス: `302` redirect to `admin.stores.api-keys.index`

#### `DELETE /admin/stores/{store}/api-keys/{api_key}`

Route名: `admin.stores.api-keys.destroy`

レスポンス: `302` redirect to `admin.stores.api-keys.index`

---

### Web（owner / employee）: 販売履歴閲覧

#### `GET /sale-histories`

Route名: `sale-histories.index`

Props:
```
saleHistories: Array<{
  id: number,
  book: { id: number, title: string, author: string },
  quantity: number,
  sold_at: string,
  pos_terminal_id: string | null
}>
```

#### `GET /sale-histories/{sale_history}`

Route名: `sale-histories.show`

Props:
```
saleHistory: {
  id: number,
  book: { id: number, title: string, author: string, price: number },
  quantity: number,
  sold_at: string,
  pos_terminal_id: string | null,
  created_at: string
}
```

---

## Route 定義

```php
// routes/api.php
Route::middleware([
    \App\Http\Middleware\RestrictPosIpAddress::class,
    \App\Http\Middleware\AuthenticateStoreApiKey::class,
])->group(function () {
    Route::post('/stores/{store}/sale-histories', [\App\Http\Controllers\Api\SaleHistoryController::class, 'store'])
        ->name('api.stores.sale-histories.store');
});
```

```php
// routes/web.php（auth:web グループ内に追加）
Route::resource('sale-histories', \App\Http\Controllers\Web\SaleHistoryController::class)
    ->only(['index', 'show'])
    ->parameters(['sale-histories' => 'sale_history']);
```

```php
// routes/admin.php（auth:admin グループ内に追加）
Route::resource('stores.api-keys', \App\Http\Controllers\Admin\StoreApiKeyController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['api-keys' => 'api_key']);
```

---

## Policy 設計

### `SaleHistoryPolicy`

```php
// Admin は before() で全許可
// owner / employee ともに自店舗の販売履歴を参照可能
public function viewAny(StoreUser $user): bool
{
    return true;
}

// 自店舗の販売履歴のみ参照可能
public function view(StoreUser $user, SaleHistory $saleHistory): bool
{
    return $user->store_id === $saleHistory->store_id;
}

// 作成は Api Middleware で認証済みの場合のみ（Web ユーザーからの直接作成は不可）
// → Api/SaleHistoryController では Policy を通さず Middleware で認可する
```

### `StoreApiKeyPolicy`

```php
// Admin のみ操作可能。StoreUser（owner / employee）はすべて false。
public function before(Admin|StoreUser $user, string $ability): bool
{
    return $user instanceof Admin;
}
```

### Policy 権限一覧

| アクション | Admin | owner（自店舗） | owner（他店舗） | employee（自店舗） | employee（他店舗） |
|---|---|---|---|---|---|
| SaleHistory viewAny | ✓ | ✓ | ✓ | ✓ | ✓ |
| SaleHistory view | ✓ | ✓ | ✗ | ✓ | ✗ |
| StoreApiKey（全アクション） | ✓ | ✗ | ✗ | ✗ | ✗ |

---

## Middleware 設計

### `AuthenticateStoreApiKey`

```php
public function handle(Request $request, Closure $next): Response
{
    $plain = $request->bearerToken();
    if (! $plain) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    $apiKey = $this->service->authenticate($plain);
    if (! $apiKey) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    // URL の {store} と API キーの store_id の一致確認
    $store = $request->route('store');
    if ($store && (int) $store->id !== $apiKey->store_id) {
        return response()->json(['message' => 'Forbidden.'], 403);
    }

    // IP 制限チェック（allowed_ips が設定されている場合のみ）
    if ($apiKey->allowed_ips !== null) {
        $clientIp = $request->ip();
        if (! in_array($clientIp, $apiKey->allowed_ips, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
    }

    // 後続のコントローラーで参照できるようリクエストに保持
    $request->attributes->set('authenticated_api_key', $apiKey);

    return $next($request);
}
```

### `RestrictPosIpAddress`

将来的なグローバルIPブロックリスト用途のスタブ。現時点では `return $next($request)` のみ。

---

## Service 設計

### `SaleHistoryService`

| メソッド | 説明 |
|---|---|
| `listByStore(int $storeId): Collection` | 自店舗の販売履歴一覧（sold_at 降順） |
| `findByStore(int $id, int $storeId): SaleHistory` | 自店舗スコープで取得（他店舗は 404） |
| `create(int $storeId, array $data): SaleHistory` | storeId はルートから取得。リクエスト入力を信用しない。 |

### `StoreApiKeyService`

| メソッド | 説明 |
|---|---|
| `listByStore(int $storeId): Collection` | 店舗のAPIキー一覧 |
| `issue(int $storeId, int $adminId, array $data): array` | 平文キーを生成しハッシュ化して保存。`['plain' => string, 'model' => StoreApiKey]` を返す |
| `toggle(StoreApiKey $apiKey, bool $isActive): StoreApiKey` | is_active の更新 |
| `delete(StoreApiKey $apiKey): void` | 物理削除 |
| `authenticate(string $plainKey): ?StoreApiKey` | key_hash で照合し有効なキーを返す。無効なら null |

---

## テスト方針

### Factory

```php
// SaleHistoryFactory
[
    'quantity'        => fake()->numberBetween(1, 20),
    'sold_at'         => fake()->dateTimeBetween('-1 year', 'now'),
    'pos_terminal_id' => fake()->optional()->regexify('[A-Z]+-[0-9]+'),
]

// StoreApiKeyFactory
[
    'name'         => fake()->words(2, true),
    'key_hash'     => hash('sha256', fake()->uuid()),
    'allowed_ips'  => null,
    'is_active'    => true,
    'last_used_at' => null,
    'expires_at'   => null,
]
```

### Feature テスト: `tests/Feature/Api/SaleHistoryTest.php`

| テストケース | 期待レスポンス |
|---|---|
| 有効なAPIキーで正しい店舗に POST | 201 |
| APIキーなしで POST | 401 |
| 無効化済みAPIキーで POST | 401 |
| 別店舗のAPIキーで POST | 403 |
| IP制限あり・許可外IPから POST | 403 |
| IP制限あり・許可IPから POST | 201 |
| 存在しない book_id で POST | 422 |
| quantity=0 で POST | 422 |

### Feature テスト: `tests/Feature/Web/SaleHistoryTest.php`

| テストケース | guard | 期待レスポンス |
|---|---|---|
| 未認証で index にアクセス | なし | 302 |
| owner で自店舗 index にアクセス | web | 200 |
| employee で自店舗 index にアクセス | web | 200 |
| owner で他店舗の履歴 show にアクセス | web | 404 |

### Feature テスト: `tests/Feature/Admin/StoreApiKeyTest.php`

| テストケース | 期待レスポンス |
|---|---|
| admin で index にアクセス | 200 |
| admin でAPIキー発行 | 302（newly_issued_key がセッションに存在） |
| admin でAPIキー無効化 | 302 |
| admin でAPIキー削除 | 302 |
| 未認証で index にアクセス | 302 |
| owner で index にアクセス | 403 |

### Policy Unit テスト: `tests/Unit/Policies/SaleHistoryPolicyTest.php`

| アクション | Admin | owner（自店舗） | owner（他店舗） | employee（自店舗） | employee（他店舗） |
|---|---|---|---|---|---|
| viewAny | true | true | true | true | true |
| view | true | true | false | true | false |

### Policy Unit テスト: `tests/Unit/Policies/StoreApiKeyPolicyTest.php`

| アクション | Admin | owner | employee |
|---|---|---|---|
| viewAny | true | false | false |
| create | true | false | false |
| update | true | false | false |
| delete | true | false | false |

---

## 実装タスク分解

### バックエンド

- [ ] Migration: `create_sale_histories_table`
- [ ] Migration: `create_store_api_keys_table`
- [ ] Model: `SaleHistory`（リレーション・fillable・cast）
- [ ] Model: `StoreApiKey`（リレーション・fillable・cast）
- [ ] Model: `Store` に `saleHistories()`, `apiKeys()` リレーション追加
- [ ] Factory: `SaleHistoryFactory`
- [ ] Factory: `StoreApiKeyFactory`
- [ ] Repository: `SaleHistoryRepository`
- [ ] Repository: `StoreApiKeyRepository`
- [ ] Service: `SaleHistoryService`
- [ ] Service: `StoreApiKeyService`
- [ ] Form Request: `Api/SaleHistoryRequest`
- [ ] Form Request: `Admin/StoreApiKeyRequest`
- [ ] Policy: `SaleHistoryPolicy`（AuthServiceProvider 登録含む）
- [ ] Policy: `StoreApiKeyPolicy`（AuthServiceProvider 登録含む）
- [ ] Middleware: `AuthenticateStoreApiKey`
- [ ] Middleware: `RestrictPosIpAddress`（スタブ）
- [ ] Controller: `Api/SaleHistoryController`（`store` アクション）
- [ ] Controller: `Web/SaleHistoryController`（`index`, `show` アクション）
- [ ] Controller: `Admin/StoreApiKeyController`（`index`, `store`, `update`, `destroy` アクション）
- [ ] Resource: `SaleHistoryResource`（API レスポンス整形用）
- [ ] Route: `routes/api.php` に POS エンドポイント追加
- [ ] Route: `routes/web.php` に `sale-histories` resource ルート追加
- [ ] Route: `routes/admin.php` に `stores.api-keys` nested resource ルート追加

### フロントエンド

- [ ] Vue: `SaleHistories/Index.vue`（表示列: 販売日時・書籍タイトル・著者・販売冊数・POS端末ID）
- [ ] Vue: `SaleHistories/Show.vue`（書籍価格・登録日時も表示）
- [ ] Vue: `Admin/StoreApiKeys/Index.vue`（APIキー一覧・発行フォーム・有効化切替・削除。新規発行キーのワンタイム表示を含む）
