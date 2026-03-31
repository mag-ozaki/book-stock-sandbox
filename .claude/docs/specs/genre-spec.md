# 書籍ジャンル（genres）機能仕様

## 概要

書籍をジャンル（小説・ビジネス書・技術書など）で分類するためのマスターデータ管理機能。
genres は books と同じ「共有マスター」として設計し、`store_id` を持たない。
books テーブルに `genre_id`（nullable FK）を追加し、任意でジャンルを紐付ける。

---

## 影響範囲

| 区分 | 対象 |
|---|---|
| DB | `genres` テーブル新規作成 |
| DB | `books` テーブルに `genre_id` カラム追加（差分 Migration） |
| Model | `Genre` 新規作成 |
| Model | `Book` に `genre()` リレーション追加、`$fillable` に `genre_id` 追加 |
| Migration | `create_genres_table` 新規作成 |
| Migration | `add_genre_id_to_books_table` 新規作成 |
| Repository | `GenreRepository` 新規作成 |
| Service | `GenreService` 新規作成 |
| Controller | `Web/GenreController` 新規作成 |
| Form Request | `Web/GenreRequest` 新規作成 |
| Policy | `GenrePolicy` 新規作成 |
| Routes | `routes/web.php` に genres リソースルート追加 |
| Resource | `BookResource` に `genre_id`, `genre_name` を追加（POS API 用） |
| Factory | `GenreFactory` 新規作成 |
| Vue Pages | `Genres/Index.vue`, `Genres/Create.vue`, `Genres/Edit.vue` 新規作成 |
| Vue Pages | `Books/Create.vue`, `Books/Edit.vue` に genre 選択 UI 追加 |
| Vue Pages | `Books/Index.vue` にジャンル列追加 |

---

## DB スキーマ

### テーブル: `genres`

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| name | string(100) | NOT NULL, UNIQUE | ジャンル名（例: 小説、ビジネス書、技術書） |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**設計注記**:
- `store_id` は持たない。books が共有マスターであるため、genres も共有マスターとする
- `name` に UNIQUE 制約を付与し、ジャンル名の重複を防ぐ

### `books` テーブル変更

既存 Migration の直接書き換えは行わず、差分 Migration として追加する。

```php
Schema::table('books', function (Blueprint $table) {
    $table->foreignId('genre_id')
          ->nullable()
          ->after('price')
          ->constrained()
          ->nullOnDelete();
});
```

**外部キー制約の選択理由**:

| 候補 | 判断 |
|---|---|
| nullOnDelete | 採用。ジャンルを廃止・整理しても書籍データは失わない。null = 未分類として扱える |
| cascadeOnDelete | 棄却。ジャンル削除で書籍レコードが消えるのは許容できない |
| RESTRICT | 棄却。書籍が存在する限りジャンルを削除できず、管理者の運用が困難になる |

### 変更後の `books` テーブル

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| jan_code | string(26) | unique, nullable | JANコード（26桁） |
| title | string | NOT NULL | |
| author | string | NOT NULL | |
| publisher | string | nullable | |
| price | unsignedInteger | nullable | 円単位 |
| genre_id | bigint | nullable, FK → genres.id | null = 未分類 |
| created_at / updated_at | timestamps | | |

---

## リレーション

```
Genre hasMany Book

Book belongsTo Genre（nullable）
```

---

## Route 定義

```php
// routes/web.php（auth:web, verified ミドルウェアグループ内に追加）
Route::resource('genres', GenreController::class)
    ->except(['show']);
```

| Method | URI | Action | Route 名 |
|---|---|---|---|
| GET | /genres | index | genres.index |
| GET | /genres/create | create | genres.create |
| POST | /genres | store | genres.store |
| GET | /genres/{genre}/edit | edit | genres.edit |
| PUT/PATCH | /genres/{genre} | update | genres.update |
| DELETE | /genres/{genre} | destroy | genres.destroy |

**admin ルートへの追加要否**: 追加しない。genres は books と同じ位置づけであり、BookController が admin ルートにないのと対称的にする。admin は `before()` によって web ルート経由の操作も全許可される。

---

## Policy

**ファイル**: `app/Policies/GenrePolicy.php`

BookPolicy と同一の設計を採用する。genres は books と同じ共有マスターであり、CLAUDE.md の権限テーブルで owner/employee ともに books CRUD が可能であることと対称的に扱う。

**権限一覧**:

| アクション | Admin | owner | employee |
|---|---|---|---|
| before（Admin） | true | — | — |
| viewAny | — | true | true |
| view | — | true | true |
| create | — | true | true |
| update | — | true | true |
| delete | — | true | true |

---

## Form Request

**ファイル**: `app/Http/Requests/Web/GenreRequest.php`

```php
public function rules(): array
{
    $genre = $this->route('genre');

    return [
        'name' => [
            'required',
            'string',
            'max:100',
            Rule::unique(Genre::class, 'name')->ignore($genre?->id),
        ],
    ];
}
```

---

## Repository

**ファイル**: `app/Repositories/GenreRepository.php`

| メソッド | 説明 |
|---|---|
| `all(): Collection` | name 順で全ジャンルを返す |
| `findOrFail(int $id): Genre` | 単件取得（失敗時は例外） |
| `create(array $data): Genre` | 新規作成 |
| `update(Genre $genre, array $data): Genre` | 更新（`fresh()` で再取得して返す） |
| `delete(Genre $genre): void` | 削除 |

---

## Service

**ファイル**: `app/Services/GenreService.php`

| メソッド | 説明 |
|---|---|
| `listAll(): Collection` | `all()` にデリゲート |
| `create(array $data): Genre` | `create()` にデリゲート |
| `update(Genre $genre, array $data): Genre` | `update()` にデリゲート |
| `delete(Genre $genre): void` | `delete()` にデリゲート |

---

## Controller

**ファイル**: `app/Http/Controllers/Web/GenreController.php`

| アクション | 認可 | レスポンス |
|---|---|---|
| `index()` | `viewAny` Genre::class | `Genres/Index` with `genres` |
| `create()` | `create` Genre::class | `Genres/Create` |
| `store(GenreRequest)` | `create` Genre::class | redirect genres.index |
| `edit(Genre)` | `update` $genre | `Genres/Edit` with `genre` |
| `update(GenreRequest, Genre)` | `update` $genre | redirect genres.index |
| `destroy(Genre)` | `delete` $genre | redirect genres.index |

---

## Inertia props 仕様

### Genre 型定義

```typescript
interface Genre {
  id: number
  name: string
}
```

### GET /genres（Index）

```
props:
  genres: Genre[]
```

表示列: ジャンル名 / 操作（編集・削除）

### GET /genres/create（Create）

```
props: なし
```

### GET /genres/{genre}/edit（Edit）

```
props:
  genre: Genre
```

### Books/Create, Books/Edit の変更点

genres 選択 UI 追加のため、`genres` 一覧を props として追加する。

**BookController の変更**:
- `GenreRepository` をコンストラクタインジェクション
- `create()` / `edit()` アクションに `'genres' => $this->genreRepo->all()` を追加

```typescript
// Books/Create.vue, Books/Edit.vue で追加される props
const props = defineProps({
  genres: Array<Genre>,
  // ...既存props
})

// useForm に genre_id 追加
const form = useForm({
  genre_id: '',  // Edit では props.book.genre_id ?? '' で初期値
  // ...既存フィールド
})
```

ジャンル選択 UI:
- `<select>` 要素
- 先頭に「未分類（選択なし）」オプション（value = ''）
- `genres` prop を `v-for` でレンダリング

**Books/Index.vue の変更点**:
- テーブルに「ジャンル」列を追加
- セル: `book.genre?.name ?? '—'`

---

## BookRequest の変更

`genre_id` フィールドをバリデーションルールに追加する。

```php
'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
```

---

## BookResource の変更（POS API）

`genre_id` と `genre_name` を追加する。null 安全に実装する。

```php
'genre_id'   => $this->genre_id,
'genre_name' => $this->genre?->name,
```

`Api/BookController` の `show` アクションに `$book->loadMissing('genre')` を追加する。

**POS API レスポンス例**:

```json
{
  "data": {
    "id": 5,
    "jan_code": "97840000000001920000000000",
    "title": "Laravel入門",
    "author": "山田 太郎",
    "publisher": "技術書院",
    "price": 3080,
    "genre_id": 3,
    "genre_name": "技術書"
  }
}
```

ジャンル未設定の書籍: `"genre_id": null, "genre_name": null`

---

## テスト方針

### Factory

**ファイル**: `database/factories/GenreFactory.php`

```php
['name' => fake()->unique()->word()]
```

### Policy Unit テスト (`tests/Unit/Policies/GenrePolicyTest.php`)

BookPolicyTest と対称的な構成。Admin は before() で全許可、StoreUser は全操作 true を検証。

### Repository Unit テスト (`tests/Unit/Repositories/GenreRepositoryTest.php`)

DatabaseTransactions トレイト使用。

| テストケース |
|---|
| `all` が name 順で返す |
| `create` でジャンルが永続化される |
| `update` で変更が永続化される |
| `delete` でジャンルが削除される |

### Service Unit テスト (`tests/Unit/Services/GenreServiceTest.php`)

GenreRepository を Mockery でモック化。各メソッドのデリゲートを検証。

### Feature テスト (`tests/Feature/Web/GenreTest.php`)

| テストケース | guard | 期待 |
|---|---|---|
| 未認証で index にアクセス | なし | 302 → login |
| owner で index にアクセス | web | 200 |
| employee で index にアクセス | web | 200 |
| owner でジャンルを作成 | web | redirect genres.index、DB に保存 |
| employee でジャンルを作成 | web | redirect genres.index |
| owner でジャンルを更新 | web | redirect genres.index、DB が更新される |
| employee でジャンルを更新 | web | redirect genres.index |
| owner でジャンルを削除 | web | redirect genres.index、DB から削除 |
| employee でジャンルを削除 | web | redirect genres.index |
| name が空でバリデーションエラー | web | 422 |
| 同名ジャンルを作成するとバリデーションエラー | web | 422 |
| 更新時に自身の name は unique 除外される | web | 302 |
| ジャンル削除後、紐づく books の genre_id が null になる | web | 302、books.genre_id = null |
| create フォームアクセス | web | 200、genres prop なし |
| edit フォームアクセス | web | 200、genre prop あり |

### BookTest.php への追加テストケース

| テストケース | 期待 |
|---|---|
| 有効な genre_id で書籍を作成できる | redirect、DB に genre_id が保存される |
| genre_id が null で書籍を作成できる（nullable） | redirect |
| 存在しない genre_id はバリデーションエラー | 422 |
| create フォームアクセス時に genres が props として含まれる | Inertia props に `genres` キーあり |
| edit フォームアクセス時に genres が props として含まれる | Inertia props に `genres` キーあり |

---

## 実装タスク分解

### バックエンド

**グループ A: DB・モデル基盤（他グループの前提）**
- Migration: `create_genres_table`
- Migration: `add_genre_id_to_books_table`（nullable FK, nullOnDelete）
- Model: `Genre`（fillable = ['name'], hasMany books）
- Model: `Book` に `genre()` リレーション、`$fillable` に `genre_id` 追加
- Factory: `GenreFactory`

**グループ B: Genre CRUD バックエンド（A の後）**
- `GenreRepository`
- `GenreService`
- `Web/GenreRequest`
- `GenrePolicy`
- `Web/GenreController`
- `routes/web.php` に genres リソースルート追加

**グループ C: Book 側の変更（A の後）**
- `Web/BookRequest` に `genre_id` バリデーション追加
- `Web/BookController` に `GenreRepository` インジェクション、create/edit に genres props 追加
- `BookResource` に `genre_id`, `genre_name` 追加
- `Api/BookController` の show に `loadMissing('genre')` 追加

### フロントエンド（B と並列可）
- `Genres/Index.vue`
- `Genres/Create.vue`
- `Genres/Edit.vue`
- `Books/Create.vue` に genre_id select フィールド追加
- `Books/Edit.vue` に genre_id select フィールド追加
- `Books/Index.vue` にジャンル列追加

### テスト（B・C・フロントエンド完了後）
- `GenrePolicyTest.php`
- `GenreRepositoryTest.php`
- `GenreServiceTest.php`
- `GenreTest.php`（Feature）
- `BookTest.php` に genre 関連テストケース追加

---

## CLAUDE.md 更新箇所

実装完了後に以下を更新すること。

- データモデル テーブル一覧に `genres` を追加
- 主なリレーションに `genres hasMany books` / `books belongsTo genre（nullable）` を追加
- 認可テーブルに genre 行を追加（admin: CRUD、owner: CRUD、employee: CRUD）
