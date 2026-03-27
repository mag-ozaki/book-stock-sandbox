# APIキー管理機能仕様

## 機能概要

admin が店舗ごとの APIキー（`store_api_keys`）を発行・一覧表示・有効化切替・削除する機能。
本機能は `admin` guard 専用。`StoreUser`（owner / employee）からは一切操作できない。

APIキーは POS 端末が販売履歴 API（`POST /api/stores/{store}/sale-histories`）を呼び出す際の Bearer トークンとして使用される。
認証フローの詳細は `sales-history-api-spec.md` の「セキュリティ設計」を参照。

本仕様書は管理画面（Inertia + Vue 3）側の実装に特化する。
`StoreApiKeyService::authenticate()` / `AuthenticateStoreApiKey` Middleware については `sales-history-api-spec.md` を参照。

---

## DB スキーマ

### テーブル: `store_api_keys`

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| store_id | bigint | NOT NULL, FK → stores.id (CASCADE DELETE) | 紐付く店舗 |
| name | string(100) | NOT NULL | キーの識別名（例: "レジ1"） |
| key_hash | string(64) | NOT NULL, UNIQUE | SHA-256 ハッシュ値（平文は発行時のみ返す） |
| allowed_ips | json | nullable | 許可IPアドレスのリスト（null = IP制限なし） |
| is_active | boolean | NOT NULL, default=true | 有効フラグ |
| last_used_at | timestamp | nullable | 最終使用日時 |
| expires_at | timestamp | nullable | 有効期限（null = 無期限） |
| created_by | bigint | NOT NULL, FK → admins.id (RESTRICT) | 発行した admin |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**インデックス**:
- `key_hash` UNIQUE（認証時の高速検索）
- `store_id`（店舗単位絞り込み）

---

## Migration

ファイル名: `2026_03_27_000001_create_store_api_keys_table.php`

```php
Schema::create('store_api_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
    $table->string('name', 100);
    $table->string('key_hash', 64)->unique();
    $table->json('allowed_ips')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->foreignId('created_by')->constrained('admins')->restrictOnDelete();
    $table->timestamps();

    $table->index('store_id');
});
```

---

## Model

### `StoreApiKey`

**fillable**:
```php
protected $fillable = [
    'store_id', 'name', 'key_hash', 'allowed_ips',
    'is_active', 'last_used_at', 'expires_at', 'created_by',
];
```

**casts**:
```php
protected $casts = [
    'allowed_ips'  => 'array',
    'is_active'    => 'boolean',
    'last_used_at' => 'datetime',
    'expires_at'   => 'datetime',
];
```

**リレーション**:
- `belongsTo(Store::class)`
- `belongsTo(Admin::class, 'created_by')`

### `Store` への追加

```php
public function apiKeys(): HasMany
{
    return $this->hasMany(StoreApiKey::class);
}
```

---

## Route 定義

```php
// routes/admin.php（auth:admin グループ内に追加）
Route::resource('stores.api-keys', \App\Http\Controllers\Admin\StoreApiKeyController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->parameters(['api-keys' => 'api_key']);
```

### 生成されるルート一覧

| メソッド | URI | アクション | Route 名 |
|---|---|---|---|
| GET | /admin/stores/{store}/api-keys | index | admin.stores.api-keys.index |
| POST | /admin/stores/{store}/api-keys | store | admin.stores.api-keys.store |
| PATCH | /admin/stores/{store}/api-keys/{api_key} | update | admin.stores.api-keys.update |
| DELETE | /admin/stores/{store}/api-keys/{api_key} | destroy | admin.stores.api-keys.destroy |

**設計注記**:
- `create` アクションは設けない。発行フォームは `index` ページに集約する（1ページ設計）。
- `show` / `edit` アクションは設けない。APIキーの平文は再表示しない仕様のため不要。
- `update` / `destroy` で `$store->id !== $apiKey->store_id` の整合チェックを行う（`scopeBindings()` に依存しない明示的チェック）。

---

## Policy 設計

### `StoreApiKeyPolicy`

Admin のみ全操作を許可。StoreUser（owner / employee）は全操作を拒否。

```php
public function before(Admin|StoreUser $user, string $ability): bool
{
    return $user instanceof Admin;
}

// 以下は before() が true を返した Admin のみ到達する
public function viewAny(Admin $user): bool  { return true; }
public function create(Admin $user): bool   { return true; }
public function update(Admin $user, StoreApiKey $apiKey): bool { return true; }
public function delete(Admin $user, StoreApiKey $apiKey): bool { return true; }
```

**設計注記**: `before()` が `bool`（nullable でない）なのは Admin または StoreUser のいずれかしか到達しないため。`StorePolicy` の `?bool` とは異なる。

### Policy 権限一覧

| アクション | Admin | owner | employee |
|---|---|---|---|
| viewAny | true | false | false |
| create | true | false | false |
| update | true | false | false |
| delete | true | false | false |

---

## Repository 設計

### `StoreApiKeyRepository`

| メソッド | シグネチャ | 説明 |
|---|---|---|
| listByStore | `listByStore(int $storeId): Collection` | 店舗のAPIキー一覧（created_at 降順） |
| create | `create(array $data): StoreApiKey` | 新規作成 |
| update | `update(StoreApiKey $apiKey, array $data): StoreApiKey` | 更新（`$model->fresh()` で返す） |
| delete | `delete(StoreApiKey $apiKey): void` | 物理削除 |
| findByKeyHash | `findByKeyHash(string $hash): ?StoreApiKey` | key_hash で検索（認証用） |

---

## Service 設計

### `StoreApiKeyService`

| メソッド | シグネチャ | 説明 |
|---|---|---|
| listByStore | `listByStore(int $storeId): Collection` | Repository に委譲 |
| issue | `issue(int $storeId, int $adminId, array $data): array` | 平文キーを生成・ハッシュ化して保存。`['plain' => string, 'model' => StoreApiKey]` を返す |
| toggle | `toggle(StoreApiKey $apiKey, bool $isActive): StoreApiKey` | `is_active` の切替 |
| delete | `delete(StoreApiKey $apiKey): void` | 物理削除 |
| authenticate | `authenticate(string $plainKey): ?StoreApiKey` | is_active / expires_at を検証し有効なキーを返す。`last_used_at` を更新する |

**`issue()` の実装方針**:
1. `Str::random(40)` で平文キーを生成
2. `hash('sha256', $plain)` でハッシュ化
3. `['store_id', 'name', 'key_hash', 'allowed_ips', 'expires_at', 'created_by']` を詰めて `Repository::create()`
4. `['plain' => $plain, 'model' => $model]` を返す

**`authenticate()` の実装方針**:
1. `hash('sha256', $plainKey)` でハッシュ化
2. `Repository::findByKeyHash($hash)` で取得、見つからなければ `null`
3. `is_active = false` なら `null`
4. `expires_at` が非 null かつ過去なら `null`
5. 成功時に `last_used_at` を更新して返す

---

## Controller 設計

### `Admin\StoreApiKeyController`

コンストラクタで `StoreApiKeyService` を DI する。

#### `index(Store $store): Response`

1. `$this->authorize('viewAny', StoreApiKey::class)`
2. `$this->service->listByStore($store->id)` で一覧取得
3. `session('newly_issued_key')` をセッションから取得（消費済みなら null）
4. `inertia('Admin/StoreApiKeys/Index', [...])`

#### `store(StoreApiKeyRequest $request, Store $store): RedirectResponse`

1. `$this->authorize('create', StoreApiKey::class)`
2. `$this->service->issue($store->id, auth('admin')->id(), $request->validated())`
3. `session()->flash('newly_issued_key', $result['plain'])`
4. redirect to `admin.stores.api-keys.index` with `success = 'APIキーを発行しました。発行されたキーは一度しか表示されません。'`

#### `update(StoreApiKeyToggleRequest $request, Store $store, StoreApiKey $apiKey): RedirectResponse`

1. `$this->authorize('update', $apiKey)`
2. `$store->id !== $apiKey->store_id` なら `abort(403)`
3. `$this->service->toggle($apiKey, $request->validated()['is_active'])`
4. redirect to `admin.stores.api-keys.index` with `success` メッセージ

#### `destroy(Store $store, StoreApiKey $apiKey): RedirectResponse`

1. `$this->authorize('delete', $apiKey)`
2. `$store->id !== $apiKey->store_id` なら `abort(403)`
3. `$this->service->delete($apiKey)`
4. redirect to `admin.stores.api-keys.index` with `success = 'APIキーを削除しました。'`

---

## Form Request 設計

### `Admin\StoreApiKeyRequest`（発行用）

```php
public function rules(): array
{
    return [
        'name'          => ['required', 'string', 'max:100'],
        'allowed_ips'   => ['nullable', 'array'],
        'allowed_ips.*' => ['ip'],
        'expires_at'    => ['nullable', 'date', 'after:now'],
    ];
}
```

### `Admin\StoreApiKeyToggleRequest`（有効化切替用）

```php
public function rules(): array
{
    return [
        'is_active' => ['required', 'boolean'],
    ];
}
```

発行と切替で Form Request を分離する（単一クラスでメソッド分岐しない）。

---

## Inertia props 仕様

### `GET /admin/stores/{store}/api-keys`

```
props:
  store: { id: number, name: string }
  apiKeys: Array<{
    id: number
    name: string
    allowed_ips: string[] | null
    is_active: boolean
    last_used_at: string | null   // ISO 8601、null = 未使用
    expires_at: string | null     // ISO 8601、null = 無期限
    created_at: string
  }>
  newlyIssuedKey: string | null   // 直前の発行時のみ非 null（フラッシュ消費後は null）
```

`key_hash` は props に含めない。

### `POST /admin/stores/{store}/api-keys`

```
request body:
  name:          string        // required, max:100
  allowed_ips:   string[] | null  // optional
  expires_at:    string | null    // optional, ISO 8601

redirect: admin.stores.api-keys.index
session flash: newly_issued_key = <平文キー>
```

### `PATCH /admin/stores/{store}/api-keys/{api_key}`

```
request body:
  is_active: boolean  // required

redirect: admin.stores.api-keys.index
```

### `DELETE /admin/stores/{store}/api-keys/{api_key}`

```
redirect: admin.stores.api-keys.index
```

---

## Vue ページ設計

### `Admin/StoreApiKeys/Index.vue`

1ページに一覧・発行フォーム・ワンタイム表示・無効化/有効化・削除を集約する。

#### ページ構成

**ヘッダー**: `{store.name} の APIキー管理` + 店舗一覧へ戻るリンク

**新規発行キーのワンタイム表示** (`newlyIssuedKey` が非 null の場合のみ表示):
- 黄色警告ブロック（`bg-yellow-50 border border-yellow-400`）
- 文言: 「以下のAPIキーは一度しか表示されません。安全な場所にコピーしてください。」
- モノスペースフォントでキー表示
- コピーボタン（`navigator.clipboard.writeText()`）

**発行フォーム**:
- 識別名（text, required）
- 許可IPアドレス（textarea, 改行区切りで複数入力、空欄 = 制限なし）
- 有効期限（date input、空欄 = 無期限）
- submit 時に `allowed_ips` を改行で split して配列に変換（空行除去、空欄は `null`）

**APIキー一覧テーブル**:

| 列 | 内容 |
|---|---|
| 識別名 | `apiKey.name` |
| 状態 | 緑バッジ「有効」/ 赤バッジ「無効」 |
| 最終使用 | `last_used_at`（null = 「未使用」） |
| 有効期限 | `expires_at`（null = 「無期限」） |
| 発行日時 | `created_at` |
| 許可IP | null = 「制限なし」、配列 = カンマ区切り |
| 操作 | 有効/無効切替ボタン + 削除ボタン |

**操作**:
- 切替: `router.patch(route('admin.stores.api-keys.update', [store.id, apiKey.id]), { is_active: !apiKey.is_active })`
- 削除: `confirm()` 後、`router.delete(route('admin.stores.api-keys.destroy', [store.id, apiKey.id]))`

### `Admin/Stores/Index.vue` への変更

操作列に「APIキー」リンクを追加:
```html
<Link :href="route('admin.stores.api-keys.index', store.id)" class="text-indigo-600 hover:underline">
  APIキー
</Link>
```

AdminLayout ナビゲーションバーへの「APIキー管理」直リンクは追加しない（店舗コンテキストが必要なため）。

---

## テスト方針

### Factory: `StoreApiKeyFactory`

```php
[
    'name'         => fake()->words(2, true),
    'key_hash'     => hash('sha256', fake()->uuid()),
    'allowed_ips'  => null,
    'is_active'    => true,
    'last_used_at' => null,
    'expires_at'   => null,
    // store_id / created_by は各テストで指定
]
```

### Feature テスト: `tests/Feature/Admin/StoreApiKeyTest.php`

| テストケース | 期待レスポンス |
|---|---|
| admin で index にアクセスできる | 200 |
| admin で APIキーを発行できる | 302 redirect、DB に key_hash 保存、セッションに `newly_issued_key` |
| 発行後に index を開くと `newlyIssuedKey` が props に渡される | props.newlyIssuedKey が非 null |
| 2 回目に index を開くと `newlyIssuedKey` が null | props.newlyIssuedKey が null |
| admin で is_active を false に更新できる | 302 redirect、DB の is_active が false |
| admin で is_active を true に戻せる | 302 redirect、DB の is_active が true |
| admin で APIキーを削除できる | 302 redirect、DB から消える |
| 未認証で index にアクセス | 302 → admin.login |
| owner で index にアクセス | 403 |
| employee で index にアクセス | 403 |
| 別店舗の api_key に update | 403 |
| 別店舗の api_key に destroy | 403 |
| name 空で発行 | バリデーションエラー |
| 不正な IP で発行 | バリデーションエラー |
| 過去日時の expires_at で発行 | バリデーションエラー |

### Policy Unit テスト: `tests/Unit/Policies/StoreApiKeyPolicyTest.php`

| テストケース | 期待結果 |
|---|---|
| before: Admin → true | true |
| before: owner → false | false |
| before: employee → false | false |
| viewAny: Admin | true |
| create: Admin | true |
| update: Admin | true |
| delete: Admin | true |

---

## 実装タスク分解

### バックエンド

- [ ] Migration: `create_store_api_keys_table`
- [ ] Model: `StoreApiKey`（fillable・casts・リレーション）
- [ ] Model: `Store` に `apiKeys(): HasMany` 追加
- [ ] Factory: `StoreApiKeyFactory`
- [ ] Repository: `StoreApiKeyRepository`（`listByStore`, `create`, `update`, `delete`, `findByKeyHash`）
- [ ] Service: `StoreApiKeyService`（`listByStore`, `issue`, `toggle`, `delete`, `authenticate`）
- [ ] Form Request: `Admin\StoreApiKeyRequest`（発行用）
- [ ] Form Request: `Admin\StoreApiKeyToggleRequest`（有効化切替用）
- [ ] Policy: `StoreApiKeyPolicy`
- [ ] Controller: `Admin\StoreApiKeyController`（`index`, `store`, `update`, `destroy`）
- [ ] Route: `routes/admin.php` に `stores.api-keys` ネストリソース追加

### フロントエンド

- [ ] Vue: `Admin/StoreApiKeys/Index.vue`（一覧・発行・ワンタイム表示・切替・削除）
- [ ] Vue: `Admin/Stores/Index.vue` に「APIキー」リンク追加

### テスト

- [ ] Feature: `tests/Feature/Admin/StoreApiKeyTest.php`
- [ ] Policy Unit: `tests/Unit/Policies/StoreApiKeyPolicyTest.php`
