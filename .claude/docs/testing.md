# テスト方針

## フレームワーク

PHPUnit を使用すること。

## テスト種別

Unit テストと Feature テストの両方を書くこと。

## テスト用 DB

- PostgreSQL を使用すること（本番同等の環境で検証するため）
- テスト用 DB は稼働用 DB と分離すること
- `phpunit.xml` の `<env name="DB_DATABASE" value="book_stock_test"/>` で切り替えること
- テスト用 DB（`book_stock_test`）は PostgreSQL コンテナ内に事前に作成しておくこと

## テスト実行手順

テストを実行する際は `/run-tests` スキルを使うこと。

手動で実行する場合は以下の順番で行うこと:

```bash
# 1. コンテナ起動
docker compose up -d

# 2. テスト DB にマイグレーション適用（新しい migration を反映するため）
docker compose exec laravel php artisan migrate --env=testing

# 3. テスト実行
docker compose exec laravel php artisan test
```

注意: `DatabaseTransactions` はマイグレーションを実行しないため、migration 追加後は必ずステップ 2 を実行すること。

## トレイトの使い分け

- 通常の Feature テスト・Unit テストは `DatabaseTransactions` を使うこと（高速）
- migration 自体の検証など、スキーマレベルの確認が必要な場合のみ `RefreshDatabase` を使うこと

## Factory

全モデル（Admin, Store, StoreUser, Book, Stock）に Factory を用意すること。

## 認可テスト

- **Policy Unit テスト**: ロール × アクションの全組み合わせを網羅的に検証すること
- **Feature テスト**: 代表的なケースで HTTP レスポンス（200 / 403 / リダイレクト）を `actingAs` で確認すること
- Feature テストで全パターンを書かず、Policy Unit でロジックを網羅・Feature は主要ルートの動作確認に絞ること

## マルチガード認証のテスト

- admin guard: `actingAs($admin, 'admin')`
- web guard: `actingAs($storeUser, 'web')`

## Service Unit テスト

- Repository は Mockery でモック化すること
- Service のビジネスロジックのみを検証すること

## カバレッジ目標

- 目標値: **85%**
- 機能追加・バグ修正の実装完了条件: テストが全て通過し、カバレッジが 85% を下回らないこと
- 実装後は必ずテストを実行し、条件を満たしてから完了とすること
- 以下はカバレッジ対象外とする（フレームワーク提供コード・未使用コード）:
  - `Actions/Fortify/*`
  - `Models/User`（このアプリでは未使用）

## ディレクトリ構成

```
tests/
├── Unit/
│   ├── Models/        ← Model のメソッド単体テスト（DB 不使用・PHPUnit\Framework\TestCase）
│   ├── Policies/      ← Policy の単体テスト（DB 不使用・PHPUnit\Framework\TestCase）
│   ├── Repositories/  ← Repository の単体テスト（DB 使用・DatabaseTransactions）
│   └── Services/      ← Service の単体テスト（Repository はモック）
└── Feature/
    ├── Admin/         ← admin guard のルートに対するテスト
    └── Web/           ← web guard のルートに対するテスト
```
