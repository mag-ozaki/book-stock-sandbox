# CLAUDE.md

## プロジェクト概要
このプロジェクトは、本屋向けの在庫管理を行う PoC 用 Web アプリケーションです。

主な利用者:
- admin: アプリケーション運営者
- owner: 本屋のオーナー
- employee: 本屋の従業員

技術スタック:
- バックエンド: Laravel 13
- フロントエンド: Inertia.js + Vue 3
- 認証: Laravel Fortify
- データベース: PostgreSQL
- ローカル開発: Windows 11 + WSL2 + docker compose
- エディタ: Cursor（WSL に接続して利用）

PoC であっても、ディレクトリ構成、認証、認可、guard 設計、DB スキーマ、Docker 構成は本番運用を意識した品質に近づけること。

---

## 基本要件
以下の要件は、明示的に変更指示があるまで固定の前提として扱うこと。

### 認証
- Laravel Fortify を使用すること。
- Fortify の UI は直接使用せず、認証画面は Inertia で構築すること。
- マルチガード認証を採用すること。
  - `admin` guard: アプリケーション運営者用
  - `web` guard: 本屋のオーナー・従業員用
- `admins` テーブルは admin ユーザー用とする。
- `store_users` テーブルは owner / employee 用とする。
- `store_users.role` は `owner` または `employee` を取ること。

Fortify で有効化する機能:
- ログイン認証
- パスワードリセット
- メール認証

注意: ユーザー登録（`Features::registration()`）は無効化している。
`store_users` は `store_id NOT NULL` のため自己登録できない設計上の制約があり、
admin が管理画面から store_users を作成する運用とする。

admin と web ユーザーについては以下を分離すること:
- ログイン画面
- ダッシュボード
- ルートグループ
- 必要に応じたレイアウト

### 認可
認可は Laravel Policy または Gate を使って実装すること。

ロールごとの権限制御:
- admin:
  - 全店舗に対する stores の CRUD
  - 全店舗に対する store_users の CRUD
- owner:
  - 自店舗の store_users の CRUD
  - 自店舗で扱う books の CRUD
  - 自店舗の stocks の CRUD
  - 自店舗の purchase_histories の Read / Create / Delete
- employee:
  - 自店舗の store_users の Read のみ
  - 自店舗で扱う books の CRUD
  - 自店舗の stocks の CRUD
  - 自店舗の purchase_histories の Read / Create

必ず店舗単位のスコープ制御を行うこと。  
admin 以外のユーザーは、他店舗のデータにアクセスできてはならない。

### データモデル
基本テーブル:
- admins
- stores
- store_users
- books
- stocks
- purchase_histories

想定リレーション:
- stores hasMany store_users
- stores hasMany stocks
- stores hasMany purchase_histories
- books hasMany stocks
- books hasMany purchase_histories
- stocks belongsTo store
- stocks belongsTo book
- store_users belongsTo store
- purchase_histories belongsTo store
- purchase_histories belongsTo book
- purchase_histories belongsTo store_user

必要に応じて timestamps と foreign keys を適切に設定すること。

---

## 機能追加時のエージェント構成

機能追加・バグ修正を実装する際は、以下のフェーズとエージェント構成で進めること。

### フェーズ構成

```
フェーズ1: 調査
  └─ Explore エージェント
       既存コードの構造・影響範囲（Route / Controller / Model / Policy / Vue）を把握する

フェーズ2: 設計
  └─ Plan エージェント（調査結果をインプットとして使う）
       - 要件の整理
       - DB / Route / Policy の変更点の確定
       - Inertia の props 仕様（バックエンドとフロントエンドのインターフェース）の確定
       - 実装タスクの分解
       - 成果物: docs/<機能名>-spec.md を作成する（既存の docs/purchase-history-spec.md を参考にすること）
       ※ spec を作成してからフェーズ3に進むこと

フェーズ3: 実装（バックエンドとフロントエンドを並列で進める）
  ├─ バックエンドエージェント
  │    Migration / Model / Repository / Service / Controller / Policy / Form Request
  └─ フロントエンドエージェント
       Vue / Inertia ページ・コンポーネント（フェーズ2で確定した props 仕様に従う）

フェーズ4: レビュー
  └─ レビューエージェント
       - 認可漏れ・Policy の適用漏れがないか
       - 店舗スコープ制御が正しいか
       - バックエンドとフロントエンドの props 仕様が整合しているか

フェーズ5: テスト
  └─ テストエージェント
       Unit / Feature テストの作成と実行（カバレッジ 85% 以上を満たすこと）
```

### 注意事項
- フェーズ1の調査結果をフェーズ2の設計に必ず反映すること
- フェーズ2で props 仕様を確定し、docs/*-spec.md を作成してからフェーズ3の並列実装に入ること
- フェーズ4のレビューで問題が見つかった場合はフェーズ3に戻って修正すること
- フェーズ5でカバレッジが 85% を下回った場合はテスト・実装を修正して再実行すること

---

## 実装方針

### 全般
- Laravel の標準的で分かりやすい構成を優先すること。
- 過度に賢い抽象化より、読みやすさを優先すること。
- PoC だからといって、認証・認可・Docker 構成を雑にしないこと。
- 大きな変更を行う前に、既存構造を確認し、ローカルルールに合わせること。

### バックエンド構成
CRUD 系の業務機能は、原則として以下の責務分離を優先すること:
- Controller
- Service
- Repository
- Model
- Form Request
- Policy

方針:
- Controller は薄く保つこと
- 業務ロジックは Service に置くこと
- データアクセスロジックは Repository に置くこと
- バリデーションは Form Request を使うこと
- 認可は Policy / Gate を使うこと

### フロントエンド構成
- Inertia + Vue 3 を使用すること
- admin 用レイアウトと web 用レイアウトを分離すること
- admin 用ページ群と web 用ページ群を分離すること
- 早すぎるコンポーネント抽象化は避け、保守しやすい画面構成を優先すること
- フォーム、バリデーション表示、画面遷移はシンプルで分かりやすく保つこと

### 命名
- 分かりやすい英語名を使用すること
- 強い理由がない限り Laravel の慣習に従うこと
- DB テーブル名・カラム名は snake_case
- クラス名は単数形 + PascalCase
- ルート名は guard / 文脈が分かるように明示的にすること

### スコープ安全性
owner / employee 向け機能では以下を徹底すること:
- 必ずログインユーザーの `store_id` で絞り込むこと
- 店舗所属判定をリクエスト入力だけで信用しないこと
- 可能な限り、アクセス可能範囲はログインユーザーから導出すること

---

## 想定される実装対象
必要に応じて、以下の領域を生成・更新すること:
- Laravel プロジェクト本体
- Fortify の設定と認証フロー
- guard 設定
- policies / gates
- models / migrations
- stores / store_users / books / stocks の CRUD
- admin / web 用の Inertia ログイン画面とダッシュボード
- docker-compose.yml
- Laravel コンテナ用 Dockerfile
- README
- 必要に応じたテキストベースの ER 図

---

## ローカル開発前提
ローカル開発環境は WSL2 上の docker compose を前提とする。

必要なコンテナ:
- nginx
- laravel
- db

要件:
- プロジェクトファイルは WSL 上から bind mount すること（nginx / laravel 両コンテナにマウント）
- UID / GID は 1000 / 1000 に揃えること
- Laravel コンテナ内では UID=1000, GID=1000 の `appuser` を作成すること
- PHP-FPM プロセスは `appuser` で実行すること
- Nginx はリバースプロキシとして PHP-FPM（ポート 9000）へ転送すること
- Nginx はポート 80 で受け付けること
- PostgreSQL は 5432 ポートを使用すること
- depends_on や network は適切に設定すること

WSL ホスト側を不必要に汚す構成は避けること。

---

## Claude への出力方針
機能実装時は以下を意識すること:
1. まず既存構造を確認する
2. 影響範囲を簡潔に説明する
3. バックエンドとフロントエンドを整合的に更新する
4. バリデーションと認可を追加または更新する
5. 可能な範囲でテストを追加または更新する
6. 実装完了後、以下のドキュメントを必ず更新すること:
   - **docs/*-spec.md**: 既存機能を変更した場合は対応する spec を更新する
   - **CLAUDE.md**: テーブル追加・ロール権限変更があった場合、「データモデル」「ロールごとの権限制御」を更新する
   - **README.md**: URL 一覧・権限テーブル・ER 図・ディレクトリ構成のコメントを更新する
   - セットアップ手順の変更（Docker 設定・環境変数・migration など）があった場合は README のセットアップ手順も更新する

変更提案時は以下を意識すること:
- できるだけファイル単位で具体的に示すこと
- 影響するテーブル、ルート、Policy、画面を明示すること
- 前提や仮定があれば明記すること

---

## 計画・仕様整理の方針
- 小規模な修正や単純な CRUD 追加では、必要以上に仕様書を作らなくてよい。
- 既存構造に影響する中規模以上の機能追加では、実装前に以下を整理すること:
  - 要件の要約
  - 影響範囲
  - DB / Route / Policy / 画面の変更点
  - 実装タスクの分解
- 必要に応じて、上記を Markdown の仕様メモまたは実装計画として残すこと。

---

## 品質基準
PoC であっても、以下は妥協しないこと:
- 認証が一貫していること
- 認可が曖昧でないこと
- CRUD がロール / スコープ制御を守っていること
- migration / relation が内部整合していること
- Docker 構成が他の開発者でも起動可能であること

速度のために認証・認可の正しさを犠牲にしないこと。

---

## テスト方針

### フレームワーク
- PHPUnit を使用すること

### テスト種別
- Unit テストと Feature テストの両方を書くこと

### テスト用 DB
- PostgreSQL を使用すること（本番同等の環境で検証するため）
- テスト用 DB は稼働用 DB と分離すること
- `phpunit.xml` の `<env name="DB_DATABASE" value="book_stock_test"/>` で切り替えること
- テスト用 DB（`book_stock_test`）は PostgreSQL コンテナ内に事前に作成しておくこと

### テスト実行手順
テストを実行する際は以下の順番で行うこと:

```bash
# 1. コンテナ起動
docker compose up -d

# 2. テスト DB にマイグレーション適用（新しい migration を反映するため）
docker compose exec laravel php artisan migrate --env=testing

# 3. テスト実行
docker compose exec laravel php artisan test
```

注意: `DatabaseTransactions` はマイグレーションを実行しないため、migration 追加後は必ずステップ 2 を実行すること。

### トレイトの使い分け
- 通常の Feature テスト・Unit テストは `DatabaseTransactions` を使うこと（高速）
- migration 自体の検証など、スキーマレベルの確認が必要な場合のみ `RefreshDatabase` を使うこと

### Factory
- 全モデル（Admin, Store, StoreUser, Book, Stock）に Factory を用意すること

### 認可テスト
- **Policy Unit テスト**: ロール × アクションの全組み合わせを網羅的に検証すること
- **Feature テスト**: 代表的なケースで HTTP レスポンス（200 / 403 / リダイレクト）を `actingAs` で確認すること
- Feature テストで全パターンを書かず、Policy Unit でロジックを網羅・Feature は主要ルートの動作確認に絞ること

### マルチガード認証のテスト
- admin guard: `actingAs($admin, 'admin')`
- web guard: `actingAs($storeUser, 'web')`

### Service Unit テスト
- Repository は Mockery でモック化すること
- Service のビジネスロジックのみを検証すること

### カバレッジ目標
- 目標値: **85%**
- 機能追加・バグ修正の実装完了条件: テストが全て通過し、カバレッジが 85% を下回らないこと
- 実装後は必ずテストを実行し、条件を満たしてから完了とすること
- 以下はカバレッジ対象外とする（フレームワーク提供コード・未使用コード）:
  - `Actions/Fortify/*`
  - `Models/User`（このアプリでは未使用）

### ディレクトリ構成
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

---

## Git 運用ルール

### ブランチ戦略
Git Flow ライトを採用する。

| ブランチ | 対応環境 | 役割 |
|---|---|---|
| `main` | production | 本番リリース済みコード |
| `develop` | staging | 統合済み・検証待ちコード |
| `feature/*` | local | 機能開発 |
| `fix/*` | local | バグ修正 |
| `refactor/*` | local | リファクタリング |
| `docs/*` | local | ドキュメント更新 |
| `hotfix/*` | — | 本番緊急修正（main から分岐） |

### ブランチ命名規則
```
feature/purchase-history
fix/stock-scope-leak
refactor/book-repository
docs/update-readme
hotfix/auth-bypass
```

### 基本フロー

**通常の機能開発・バグ修正**
```
develop からブランチを切る
→ PR: feature/* (or fix/*) → develop
→ staging で動作確認
→ PR: develop → main（リリースタイミングで）
```

**本番緊急修正**
```
main から hotfix/* を切る
→ PR: hotfix/* → main
→ PR: hotfix/* → develop（乖離防止）
```

### ルール
- `main` / `develop` への直接 push は禁止
- 作業は必ずブランチを切ってから開始する
- PR マージ後は作業ブランチを削除する
- `release/*` ブランチは現時点では不要。リリース前調整が複雑になったタイミングで導入する

### 実装着手前のブランチチェック
機能追加・バグ修正などの実装を始める前に、必ず以下の順で確認・実行すること:

1. `git status` で未コミットのファイルがないか確認する
   - 未コミットの変更がある場合は実装を**中止**し、「未コミットの変更があります。先に処理してください」とユーザーに伝える
2. 現在のブランチを確認する
   - `develop` 以外のブランチにいる場合は `develop` に戻る
3. `develop` から適切な名前で feature ブランチを切る
4. feature ブランチ上で実装を開始する

### コミットメッセージ
日本語で記述する。形式: `<type>: <内容>`

| type | 用途 |
|---|---|
| `feat` | 新機能 |
| `fix` | バグ修正 |
| `refactor` | リファクタリング |
| `test` | テスト追加・修正 |
| `docs` | ドキュメント更新 |
| `chore` | ビルド・設定変更など |

例: `feat: 購入履歴一覧ページを追加`

### CI（GitHub Actions）
`.github/workflows/test.yml` により、以下のタイミングで自動テストが実行される:
- `feature/*` / `fix/*` 等から `develop` / `main` への PR 作成・更新時
- `develop` / `main` への push 時

テストは Docker Compose 環境（PostgreSQL 使用）で実行される。
PR マージ前にテストが通過していることを確認すること。

---

## 迷ったときの原則
要件が曖昧な場合は、以下を優先すること:
- Laravel の最も標準的な方法を選ぶ
- admin / web の分離を維持する
- 店舗単位のデータ分離を維持する
- 仮定した内容はレスポンスまたは README に明記する