# JANコード管理機能仕様

## 概要

POSターミナルから送られるJANコードで書籍を特定できるようにするための機能。
書籍のJANコードは上段（ISBN相当の 13 桁）と下段（本体価格・ジャンルを管理する 13 桁）の 2 段構成であり、POSターミナルは上段・下段を結合した **26 桁**の数字を送信する。
既存の `books.isbn` カラムを `jan_code`（26 桁）に置き換え、バリデーション・UI のラベルを正確な名称に揃える。
リリース前のため、既存 Migration を直接書き換える方針とする。

---

## カラム名の決定

**選択肢A（`isbn` → `jan_code` にリネーム）を採用する。**

### 理由

| 観点 | 判断 |
|---|---|
| 意味の正確さ | POSが送信する識別子は「JANコード」。`isbn` フィールドはISBN-13の格納を想定した名前だが、本システムではPOS連携が主目的であり、ドメイン言語を統一すべき |
| 体系の同一性 | ISBN-13 と JAN コードはどちらも EAN-13 体系であり、桁数・チェックディジット計算は同じ。カラムを分ける技術的根拠がない |
| 将来の混乱回避 | `isbn` という名前を残すと「ISBN-10 も入れられるか」「10桁と13桁が混在するか」という疑問が生じる。`jan_code` に統一することで 13 桁 EAN-13 限定であることが明確になる |
| 選択肢C の棄却 | `isbn` を残しつつ `jan_code` を追加すると、重複した識別子フィールドが生まれ、一貫性の維持が困難になる |

### 移行方針

リリース前のため、既存 Migration（`2026_03_18_000004_create_books_table.php`）を直接書き換える。
`isbn string(20)` を `jan_code string(26)` に変更する。
Factory も合わせて 26 桁生成に変更する。

---

## 影響範囲

| 区分 | 対象 | 変更種別 |
|---|---|---|
| DB | `books.isbn` → `books.jan_code` | 既存 Migration を直接書き換え |
| Model | `Book` | fillable 変更 |
| Form Request | `Web/BookRequest` | フィールド名・バリデーションルール変更 |
| Repository | `BookRepository` | `findByJanCode()` メソッド追加 |
| Service | `BookService` | `findByJanCode()` メソッド追加 |
| Factory | `BookFactory` | フィールド名変更 |
| Vue Pages | `Books/Index.vue`, `Create.vue`, `Edit.vue` | フィールド名・ラベル変更 |
| Tests | `BookRepositoryTest`, `BookServiceTest`, `BookTest` | `isbn` → `jan_code` 置換 + 新規テスト追加 |

---

## DB 変更

### Migration: `2026_03_18_000004_create_books_table.php`（直接書き換え）

リリース前のため、既存 Migration ファイルを直接修正する。
`isbn string(20) unique nullable` を `jan_code string(26) unique nullable` に変更する。

```php
Schema::create('books', function (Blueprint $table) {
    $table->id();
    $table->string('jan_code', 26)->unique()->nullable()->comment('JANコード（上段13桁+下段13桁の26桁）');
    $table->string('title');
    $table->string('author');
    $table->string('publisher')->nullable();
    $table->unsignedInteger('price')->nullable()->comment('円単位');
    $table->timestamps();
});
```

### 変更後の `books` テーブル

| カラム名 | 型 | 制約 | 説明 |
|---|---|---|---|
| id | bigint | PK, auto increment | |
| jan_code | string(26) | unique, nullable | 上段 13 桁 + 下段 13 桁の結合（計 26 桁） |
| title | string | NOT NULL | |
| author | string | NOT NULL | |
| publisher | string | nullable | |
| price | unsignedInteger | nullable | 円単位 |
| created_at / updated_at | timestamps | | |

---

## Inertia props 仕様

### Book 型定義

```typescript
interface Book {
  id: number
  jan_code: string | null  // 上段13桁+下段13桁の26桁、または null
  title: string
  author: string
  publisher: string | null
  price: number | null
}
```

### GET /books（Index）

```
props:
  books: Book[]
```

テーブル列: タイトル / 著者 / 出版社 / 価格 / JANコード / 操作

### GET /books/create（Create）

```
props: なし（フォームのみ）
```

### POST /books（store）

```
request body:
  jan_code:  string | null  // 26桁数字（上段13桁+下段13桁）、nullable
  title:     string         // required
  author:    string         // required
  publisher: string | null  // nullable
  price:     number | null  // nullable, min:0

redirect: books.index
```

### GET /books/{book}/edit（Edit）

```
props:
  book: Book
```

### PUT /books/{book}（update）

```
request body:
  jan_code:  string | null
  title:     string
  author:    string
  publisher: string | null
  price:     number | null

redirect: books.index
```

---

## Form Request 変更内容

**ファイル**: `app/Http/Requests/Web/BookRequest.php`

変更前:
```php
'isbn' => ['nullable', 'string', 'max:20', Rule::unique(Book::class, 'isbn')->ignore($book?->id)],
```

変更後:
```php
'jan_code' => [
    'nullable',
    'string',
    'digits:26',
    Rule::unique(Book::class, 'jan_code')->ignore($book?->id),
],
```

`digits:26` を使用する理由: 上段 13 桁 + 下段 13 桁の結合で 26 桁固定。`regex:/^\d{26}$/` と等価だが、Laravel 標準のバリデーションルールを優先する。

---

## Repository 変更内容

**ファイル**: `app/Repositories/BookRepository.php`

追加メソッド:
```php
/**
 * JANコードで書籍を検索する（POS連携用）
 * 見つからない場合は null を返す
 */
public function findByJanCode(string $janCode): ?Book
{
    return Book::where('jan_code', $janCode)->first();
}
```

`first()` を使用する理由: POS API のユースケースでは「書籍未登録 → 404」と「書籍未登録 → 新規登録誘導」の両パターンが想定される。`firstOrFail()` にせず呼び出し元に判断を委ねる。

---

## Service 変更内容

**ファイル**: `app/Services/BookService.php`

追加メソッド:
```php
public function findByJanCode(string $janCode): ?Book
{
    return $this->repo->findByJanCode($janCode);
}
```

---

## Vue フォーム変更内容

### 共通の変更方針

| 変更前 | 変更後 |
|---|---|
| `form.isbn` | `form.jan_code` |
| ラベル「ISBN」 | ラベル「JANコード」 |
| `form.errors.isbn` | `form.errors.jan_code` |
| `props.book.isbn` | `props.book.jan_code` |
| `book.isbn` | `book.jan_code` |

### Books/Create.vue・Edit.vue

JANコード入力フィールドの仕様:
- `type="text"`, `maxlength="26"`, `inputmode="numeric"`
- ヒントテキスト: 「26桁の数字（上段13桁+下段13桁）」
- `inputmode="numeric"` でモバイルでの数字入力を補助

### Books/Index.vue

- テーブルヘッダー: 「ISBN」→「JANコード」
- セル: `book.isbn` → `book.jan_code`（null の場合は `—` を表示）

---

## テスト方針

### 既存テストの修正

| ファイル | 変更内容 |
|---|---|
| `BookRepositoryTest.php` | `$data` 内の `'isbn'` → `'jan_code'` に変更 |
| `BookServiceTest.php` | `isbn` 参照なし。変更不要 |
| `BookTest.php`（Feature） | `isbn` を直接 POST しているテストなし。変更不要 |

### 新規テストケース

**BookTest.php（Feature）** に追加:

| テストケース | 期待レスポンス |
|---|---|
| `jan_code` が 26 桁数字で書籍を作成できる | redirect(books.index)、DBに jan_code が保存される |
| `jan_code` が 25 桁の場合はバリデーションエラー | 422 |
| `jan_code` が 27 桁の場合はバリデーションエラー | 422 |
| `jan_code` に数字以外が含まれる場合はバリデーションエラー | 422 |
| `jan_code` が null で書籍を作成できる（nullable） | redirect(books.index) |
| 同一 `jan_code` で 2 件目の作成はバリデーションエラー | 422 |
| 更新時に自分自身の `jan_code` は unique 除外される | redirect(books.index) |

**BookRepositoryTest.php** に追加:

| テストケース | 期待動作 |
|---|---|
| `findByJanCode` で存在するJANコードを検索すると Book が返る | Book インスタンスを返す |
| `findByJanCode` で存在しないJANコードを検索すると null が返る | null を返す |

**BookServiceTest.php** に追加:

```php
public function test_find_by_jan_code_delegates_to_repository(): void
{
    $book = new Book();
    $repo = Mockery::mock(BookRepository::class);
    $repo->shouldReceive('findByJanCode')->with('97840000000001920000000000')->once()->andReturn($book);

    $this->assertSame($book, (new BookService($repo))->findByJanCode('97840000000001920000000000'));
}
```

---

## 実装タスク分解

### バックエンド

- [ ] Migration: `2026_03_18_000004_create_books_table.php` を直接書き換え（`isbn string(20)` → `jan_code string(26)`）
- [ ] Model: `Book` の `$fillable` を `'isbn'` → `'jan_code'` に変更
- [ ] Factory: `BookFactory` のフィールド名を `isbn` → `jan_code` に変更
- [ ] Form Request: `BookRequest` の `isbn` ルールを `jan_code` ルール（`digits:26`, `unique`）に差し替え
- [ ] Repository: `BookRepository` に `findByJanCode(string $janCode): ?Book` を追加
- [ ] Service: `BookService` に `findByJanCode(string $janCode): ?Book` を追加
- [ ] Test: `BookRepositoryTest` の `isbn` 参照を `jan_code` に変更 + `findByJanCode` テスト追加
- [ ] Test: `BookServiceTest` に `findByJanCode` のデリゲートテストを追加
- [ ] Test: `BookTest`（Feature）にバリデーション・nullable・unique テストを追加

### フロントエンド

- [ ] `Books/Create.vue`: `form.isbn` → `form.jan_code`、ラベル・エラー参照変更、`maxlength="26"` / `inputmode="numeric"` 付与
- [ ] `Books/Edit.vue`: `props.book.isbn` → `props.book.jan_code`、同上
- [ ] `Books/Index.vue`: ヘッダーを「JANコード」に変更、`book.isbn` → `book.jan_code`

---

## 補足: 将来の POS 連携との接続点

本仕様で追加する `BookRepository::findByJanCode()` および `BookService::findByJanCode()` は、将来の POS API エンドポイントから直接呼び出せる設計になっている。

- JANコードで書籍を特定 → `BookService::findByJanCode()`
- 書籍が見つからない場合は 404 を返すか、新規登録フローに誘導するかは POS API 仕様で別途決定する
