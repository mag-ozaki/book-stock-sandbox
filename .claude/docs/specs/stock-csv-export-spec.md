# 在庫CSVエクスポート機能仕様

## 概要

自店舗の在庫データを CSV ファイルとしてダウンロードする機能。
owner / employee ともに利用可能とし、自店舗のデータのみエクスポートできる。
新規テーブルは不要。既存の `stocks` / `books` データを使用する。

---

## 要件まとめ

- owner / employee ともに自店舗の在庫を CSV エクスポートできる
- 他店舗のデータは含まれない（`store_id` はログインユーザーから導出）
- CSV は BOM 付き UTF-8 で出力する（Excel での文字化け防止）
- ファイル名は `stocks_YYYYMMDD.csv`（実行日付）
- 新規 Migration・Policy は不要（既存の `viewAny` を流用）

---

## 影響範囲

| 区分 | 対象 |
|---|---|
| Service | `StockService` に `exportByStore()` を追加 |
| Controller | `Web/StockController` に `export()` アクションを追加 |
| Routes | `routes/web.php` に `GET /stocks/export` を追加（resource より前に定義） |
| Vue | `Stocks/Index.vue` に CSV エクスポートボタンを追加 |

---

## Route 定義

```php
// routes/web.php（auth:web, verified ミドルウェアグループ内）
// ※ Route Model Binding の衝突を避けるため、resource より前に定義すること
Route::get('/stocks/export', [StockController::class, 'export'])->name('stocks.export');
Route::resource('stocks', StockController::class)->except(['show']);
```

| Method | URI | Action | Route 名 |
|---|---|---|---|
| GET | /stocks/export | export | stocks.export |

---

## Policy

既存の `StockPolicy::viewAny()` を使用する。新規 Policy メソッドは不要。

```php
// StockController::export() での認可チェック
$this->authorize('viewAny', Stock::class);
```

---

## CSV 仕様

### ヘッダー行

```
ISBN,タイトル,著者,出版社,価格,在庫数
```

### データ行

| カラム | 参照元 | 備考 |
|---|---|---|
| ISBN | `stocks.book.isbn` | null の場合は空文字 |
| タイトル | `stocks.book.title` | |
| 著者 | `stocks.book.author` | |
| 出版社 | `stocks.book.publisher` | null の場合は空文字 |
| 価格 | `stocks.book.price` | null の場合は空文字 |
| 在庫数 | `stocks.quantity` | |

### エンコーディング

- BOM 付き UTF-8（`\xEF\xBB\xBF` を先頭に付与）
- Excel で直接開いても文字化けしない

### ファイル名

```
stocks_YYYYMMDD.csv  例: stocks_20260326.csv
```

---

## Service

**ファイル**: `app/Services/StockService.php`

```php
public function exportByStore(int $storeId): string
{
    $stocks = $this->repo->allByStore($storeId);

    $rows = [];
    $rows[] = ['ISBN', 'タイトル', '著者', '出版社', '価格', '在庫数'];

    foreach ($stocks as $stock) {
        $rows[] = [
            $stock->book->isbn ?? '',
            $stock->book->title ?? '',
            $stock->book->author ?? '',
            $stock->book->publisher ?? '',
            $stock->book->price ?? '',
            $stock->quantity,
        ];
    }

    $handle = fopen('php://temp', 'r+');
    fwrite($handle, "\xEF\xBB\xBF"); // BOM
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
}
```

---

## Controller

**ファイル**: `app/Http/Controllers/Web/StockController.php`

```php
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
```

---

## Vue

**ファイル**: `resources/js/Pages/Stocks/Index.vue`

在庫一覧ページのヘッダー右側に「CSV エクスポート」ボタンを追加する。
Inertia の `<Link>` ではなく通常の `<a>` タグを使用（ファイルダウンロードのため）。

```vue
<a :href="route('stocks.export')"
  class="bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 text-sm border border-gray-300">
  CSV エクスポート
</a>
```

---

## テスト方針

### Service Unit テスト (`tests/Unit/Services/StockServiceTest.php`)

| テストケース | 検証内容 |
|---|---|
| `exportByStore` が CSV 文字列を返す | ヘッダー行・データ行・BOM の存在を確認 |

### Feature テスト (`tests/Feature/Web/StockTest.php`)

| テストケース | guard | 期待レスポンス |
|---|---|---|
| owner で export にアクセス | web | 200、`Content-Type: text/csv`、書籍タイトルを含む |
| employee で export にアクセス | web | 200 |
| 他店舗のデータが含まれないこと | web | 自店舗の書籍タイトルのみ含む |

