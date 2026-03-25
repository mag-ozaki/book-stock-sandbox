# 購入履歴（PurchaseHistory）機能仕様

## 概要

本屋が書籍を仕入れた際の購入（仕入れ）履歴を管理する機能。
履歴データは作成・参照・削除のみ可能とし、編集・更新は行わない。
店舗単位のスコープ制御を徹底し、他店舗のデータへのアクセスは許可しない。

---

## 要件まとめ

- owner / employee ともに自店舗の購入履歴を参照・作成できる
- 削除は owner のみ許可（employee は不可）
- 編集・更新は不可（履歴の性質上、変更を認めない）
- `store_id` および `store_user_id` はログインユーザー情報から導出し、リクエスト入力を信用しない

---

## 影響範囲

| 区分 | 対象 |
|---|---|
| DB | `purchase_histories` テーブル新規作成 |
| Model | `PurchaseHistory` 新規作成 |
| Migration | `create_purchase_histories_table` 新規作成 |
| Repository | `PurchaseHistoryRepository` 新規作成 |
| Service | `PurchaseHistoryService` 新規作成 |
| Controller | `Web/PurchaseHistoryController` 新規作成 |
| Form Request | `Web/PurchaseHistoryRequest` 新規作成 |
| Policy | `PurchaseHistoryPolicy` 新規作成 |
| Routes | `routes/web.php` に resource ルート追加 |
| Vue Pages | `PurchaseHistories/Index.vue`, `Create.vue`, `Show.vue` 新規作成 |
| Factory | `PurchaseHistoryFactory` 新規作成 |

---

## DB スキーマ

### テーブル: `purchase_histories`

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| store_id | bigint | NOT NULL, FK → stores.id | 購入した店舗 |
| store_user_id | bigint | NOT NULL, FK → store_users.id | 登録した担当者 |
| book_id | bigint | NOT NULL, FK → books.id | 購入した書籍 |
| quantity | integer | NOT NULL, min=1 | 購入冊数 |
| purchased_at | date | NOT NULL | 購入日 |
| note | text | nullable | 備考 |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**外部キー制約**:
- `store_id` → `stores.id` (CASCADE DELETE)
- `store_user_id` → `store_users.id` (RESTRICT)
- `book_id` → `books.id` (RESTRICT)

---

## リレーション

```
PurchaseHistory belongsTo Store
PurchaseHistory belongsTo StoreUser
PurchaseHistory belongsTo Book

Store hasMany PurchaseHistory
StoreUser hasMany PurchaseHistory
Book hasMany PurchaseHistory
```

---

## Route 定義

```php
// routes/web.php（auth:web, verified ミドルウェアグループ内）
Route::resource('purchase-histories', PurchaseHistoryController::class)
    ->except(['edit', 'update'])
    ->parameters(['purchase-histories' => 'purchase_history']);
```

利用するアクション: `index`, `create`, `store`, `show`, `destroy`
除外するアクション: `edit`, `update`（履歴の変更は不可）

| Method | URI | Action | Route 名 |
|---|---|---|---|
| GET | /purchase-histories | index | purchase-histories.index |
| GET | /purchase-histories/create | create | purchase-histories.create |
| POST | /purchase-histories | store | purchase-histories.store |
| GET | /purchase-histories/{purchase_history} | show | purchase-histories.show |
| DELETE | /purchase-histories/{purchase_history} | destroy | purchase-histories.destroy |

---

## Policy

**ファイル**: `app/Policies/PurchaseHistoryPolicy.php`

```php
public function before(Admin|StoreUser $user, string $ability): ?bool
{
    if ($user instanceof Admin) {
        return true;
    }
    return null;
}

// 自店舗の購入履歴一覧は参照可能（owner / employee ともに可）
public function viewAny(StoreUser $user): bool
{
    return true;
}

// 自店舗の購入履歴のみ参照可能
public function view(StoreUser $user, PurchaseHistory $purchaseHistory): bool
{
    return $user->store_id === $purchaseHistory->store_id;
}

// 購入履歴の登録は認証済み store_user であれば可能（store_id / store_user_id はサーバー側で付与）
public function create(StoreUser $user): bool
{
    return true;
}

// 削除は owner のみ、かつ自店舗のレコードのみ
public function delete(StoreUser $user, PurchaseHistory $purchaseHistory): bool
{
    return $user->isOwner() && $user->store_id === $purchaseHistory->store_id;
}
```

---

## Form Request

**ファイル**: `app/Http/Requests/Web/PurchaseHistoryRequest.php`

```php
public function authorize(): bool
{
    return true; // 認可は Policy で制御
}

public function rules(): array
{
    return [
        'book_id'      => ['required', 'integer', 'exists:books,id'],
        'quantity'     => ['required', 'integer', 'min:1'],
        'purchased_at' => ['required', 'date'],
        'note'         => ['nullable', 'string', 'max:1000'],
        // store_id はログインユーザーから導出するため入力値を受け取らない
        // store_user_id はログインユーザーから導出するため入力値を受け取らない
    ];
}
```

---

## Repository

**ファイル**: `app/Repositories/PurchaseHistoryRepository.php`

```php
// 自店舗の購入履歴一覧（book, storeUser を eager load）
public function allByStore(int $storeId): Collection
{
    return PurchaseHistory::with(['book', 'storeUser'])
        ->where('store_id', $storeId)
        ->orderByDesc('purchased_at')
        ->get();
}

// 自店舗スコープでフェッチ（他店舗は 404）
public function findByStoreOrFail(int $id, int $storeId): PurchaseHistory
{
    return PurchaseHistory::with(['book', 'storeUser'])
        ->where('id', $id)
        ->where('store_id', $storeId)
        ->firstOrFail();
}

public function create(array $data): PurchaseHistory
{
    return PurchaseHistory::create($data);
}

public function delete(PurchaseHistory $purchaseHistory): void
{
    $purchaseHistory->delete();
}
```

---

## Service

**ファイル**: `app/Services/PurchaseHistoryService.php`

```php
public function listByStore(int $storeId): Collection
{
    return $this->repo->allByStore($storeId);
}

public function findByStore(int $id, int $storeId): PurchaseHistory
{
    return $this->repo->findByStoreOrFail($id, $storeId);
}

public function create(int $storeId, int $storeUserId, array $data): PurchaseHistory
{
    // store_id, store_user_id はログインユーザーから導出し、リクエスト入力を信用しない
    $data['store_id']      = $storeId;
    $data['store_user_id'] = $storeUserId;

    return $this->repo->create($data);
}

public function delete(PurchaseHistory $purchaseHistory): void
{
    $this->repo->delete($purchaseHistory);
}
```

---

## Controller

**ファイル**: `app/Http/Controllers/Web/PurchaseHistoryController.php`

```php
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
    // Route Model Binding では store スコープが効かないため、Service で再取得
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
    // Route Model Binding では store スコープが効かないため、Service で再取得
    /** @var StoreUser $user */
    $user = auth()->user();

    $purchaseHistory = $this->service->findByStore($purchaseHistory->id, $user->store_id);

    $this->authorize('delete', $purchaseHistory);

    $this->service->delete($purchaseHistory);

    return redirect()->route('purchase-histories.index')
        ->with('success', '購入履歴を削除しました。');
}
```

> **注意**: `show` / `destroy` では Route Model Binding をそのまま使うと他店舗のレコードを取得できてしまうため、必ず `service->findByStore()` で再取得してから Policy の認可チェックを行うこと。

---

## Vue Props 定義

### Index ページ

```javascript
// PurchaseHistories/Index.vue
defineProps({ purchaseHistories: Array })
```

表示するカラム: 購入日（`purchased_at`）、書籍タイトル（`book.title`）、著者（`book.author`）、購入冊数（`quantity`）、担当者（`storeUser.name`）、備考（`note`）

### Create ページ

```javascript
// PurchaseHistories/Create.vue
defineProps({ books: Array })

const form = useForm({
  book_id:      '',
  quantity:     1,
  purchased_at: '',
  note:         '',
})

const submit = () => form.post(route('purchase-histories.store'))
```

### Show ページ

```javascript
// PurchaseHistories/Show.vue
defineProps({ purchaseHistory: Object })
```

表示する情報: 購入日、書籍タイトル・著者・価格、購入冊数、担当者名、備考、登録日時

### 削除パターン（Index ページ）

```javascript
const destroy = (id) => {
  if (confirm('この購入履歴を削除しますか？')) {
    router.delete(route('purchase-histories.destroy', id))
  }
}
```

---

## Show ページの必要性について

購入履歴は一覧（Index）で主要な情報（日付・書籍・数量・担当者）を表示できるが、
**備考（note）の全文表示**および**詳細確認のパーマリンク**のために Show ページを設ける。

具体的なユースケース:
- 長い備考を一覧では省略し、詳細で全文表示する
- 特定履歴へのリンクを社内共有する際に使える

---

## テスト方針

### Factory

```php
// database/factories/PurchaseHistoryFactory.php
// store_id, store_user_id, book_id は呼び出し元で設定する
[
    'quantity'     => fake()->numberBetween(1, 50),
    'purchased_at' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
    'note'         => fake()->optional()->sentence(),
]
```

### Policy Unit テスト (`tests/Unit/Policies/PurchaseHistoryPolicyTest.php`)

検証する組み合わせ:

| アクション | Admin | owner（自店舗） | owner（他店舗） | employee（自店舗） | employee（他店舗） |
|---|---|---|---|---|---|
| viewAny | true | true | true | true | true |
| view | true | true | false | true | false |
| create | true | true | — | true | — |
| delete | true | true | false | false | false |

### Feature テスト (`tests/Feature/Web/PurchaseHistoryTest.php`)

| テストケース | guard | 期待レスポンス |
|---|---|---|
| 未認証で index にアクセス | なし | リダイレクト（302） |
| owner で index にアクセス | web | 200 |
| employee で index にアクセス | web | 200 |
| owner で store にアクセス | web | リダイレクト（302） |
| employee で store にアクセス | web | リダイレクト（302） |
| employee で他店舗の履歴を destroy | web | 404 |
| employee で自店舗の履歴を destroy | web | 403（owner のみ許可） |
| owner で自店舗の履歴を destroy | web | リダイレクト（302） |
| owner で他店舗の履歴を destroy | web | 404 |

### Service Unit テスト (`tests/Unit/Services/PurchaseHistoryServiceTest.php`)

- `PurchaseHistoryRepository` は Mockery でモック化
- `create()` で `store_id` / `store_user_id` がリクエスト入力値を上書きすることを検証
- `listByStore()` が正しい `storeId` で Repository を呼ぶことを検証

---

## 実装タスク分解

1. Migration: `create_purchase_histories_table`
2. Model: `PurchaseHistory`（リレーション・fillable 設定）
3. Factory: `PurchaseHistoryFactory`
4. Repository: `PurchaseHistoryRepository`
5. Service: `PurchaseHistoryService`
6. Form Request: `Web/PurchaseHistoryRequest`
7. Policy: `PurchaseHistoryPolicy`（`AuthServiceProvider` への登録も含む）
8. Controller: `Web/PurchaseHistoryController`
9. Routes: `routes/web.php` に resource ルート追加
10. Vue: `PurchaseHistories/Index.vue`
11. Vue: `PurchaseHistories/Create.vue`
12. Vue: `PurchaseHistories/Show.vue`
13. Tests: Policy Unit / Service Unit / Feature
