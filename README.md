# Book Stock

本屋向け在庫管理 PoC Web アプリケーション。

---

## 技術スタック

| 領域 | 技術 |
|------|------|
| バックエンド | Laravel 13 (PHP 8.3) |
| フロントエンド | Inertia.js + Vue 3 + Vite |
| 認証 | Laravel Fortify |
| データベース | PostgreSQL 16 |
| Web サーバー | Nginx |
| ローカル開発 | Docker Compose (WSL2) |

---

## 前提条件

- Windows 11 + WSL2
- Docker Desktop（WSL2 統合が有効になっていること）
- エディタから WSL2 に接続できること（Cursor 推奨）
- **gh CLI**（GitHub CLI）— PR 作成・CI 監視・マージ自動化に必要

---

## 開発ツールのセットアップ

### gh CLI（GitHub CLI）のインストール

WSL2 上で以下を実行します。

```bash
# 公式リポジトリを追加して apt でインストール
(type -p wget >/dev/null || (sudo apt update && sudo apt-get install wget -y)) \
&& sudo mkdir -p -m 755 /etc/apt/keyrings \
&& out=$(mktemp) && wget -nv -O$out https://cli.github.com/packages/githubcli-archive-keyring.gpg \
&& cat $out | sudo tee /etc/apt/keyrings/githubcli-archive-keyring.gpg > /dev/null \
&& sudo chmod go+r /etc/apt/keyrings/githubcli-archive-keyring.gpg \
&& echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" | sudo tee /etc/apt/sources.list.d/github-cli.list > /dev/null \
&& sudo apt update \
&& sudo apt install gh -y

# インストール確認
gh --version
```

### gh CLI の認証

インストール後、一度だけ GitHub 認証を行います。

```bash
gh auth login
```

対話式プロンプトでは以下を選択してください：

| 質問 | 推奨選択 |
|------|----------|
| What account do you want to log into? | `GitHub.com` |
| What is your preferred protocol for Git operations? | `HTTPS` |
| Authenticate Git with your GitHub credentials? | `Y`（Enter） |
| How would you like to authenticate GitHub CLI? | `Login with a web browser` |

ブラウザで表示されるワンタイムコードを入力して認証を完了してください。

> **再認証が必要なタイミング：** トークンの有効期限切れ・失効・PC 移行時など。
> `gh auth status` で現在の認証状態を確認できます。

---

## セットアップ（初回）

### 1. リポジトリのクローン

```bash
git clone <repository-url>
cd book-stock-sandbox
```

### 2. 環境変数ファイルの作成

```bash
cp .env.example .env
```

必要に応じて `.env` の値を編集してください（デフォルトのまま動きます）。

### 3. Docker イメージのビルド

```bash
docker compose build
```

### 4. Laravel の依存パッケージインストール

```bash
docker compose run --rm --no-deps --entrypoint "" laravel composer install
docker compose run --rm --no-deps --entrypoint "" laravel npm install
```

### 5. Laravel の環境設定

```bash
# アプリケーションキーの生成
docker compose run --rm --no-deps --entrypoint "" laravel php artisan key:generate
```

`src/.env` の DB 設定が以下になっていることを確認してください：

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=book_stock
DB_USERNAME=book_stock
DB_PASSWORD=secret
```

### 6. コンテナ起動 & マイグレーション

```bash
# コンテナを起動
docker compose up -d

# マイグレーションの実行
docker compose exec laravel php artisan migrate

# シードの実行（初期 Admin アカウントが作成されます）
docker compose exec laravel php artisan db:seed
```

> **初期 Admin アカウント**
>
> | 項目 | 値 |
> |------|----|
> | email | `admin@example.com` |
> | password | `password` |
>
> 本番環境に移行する際は必ずパスワードを変更してください。

### 7. フロントエンドのビルド（本番用）

```bash
docker compose exec laravel npm run build
```

---

## 日常の開発ワークフロー

### コンテナの起動・停止

```bash
# 起動
docker compose up -d

# 停止
docker compose down
```

### アクセス URL

`docker compose up -d` 後、以下の URL でアクセスできます：

- Web アプリ：http://localhost
- 管理画面：http://localhost/admin/login

> Nginx（ポート 80）→ PHP-FPM（ポート 9000）の構成で動作します。
> Vite 開発サーバー（HMR）はコンテナ起動時に自動的にバックグラウンドで起動します。

### artisan コマンド

```bash
docker compose exec laravel php artisan <command>

# 例
docker compose exec laravel php artisan migrate
docker compose exec laravel php artisan tinker
docker compose exec laravel php artisan route:list
```

### データベース接続（直接）

```bash
docker compose exec db psql -U book_stock -d book_stock
```

---

## テスト

### テスト用 DB のセットアップ（初回のみ）

テスト用コンテナ（`db_test`）は `docker compose up -d` で自動的に起動します。
初回のみ、テスト用 DB にマイグレーションを実行してください。

```bash
docker compose exec laravel php artisan migrate --env=testing
```

### テストの実行

```bash
# 全テスト
docker compose exec laravel php artisan test

# スイート指定
docker compose exec laravel php artisan test --testsuite=Unit
docker compose exec laravel php artisan test --testsuite=Feature

# ファイル指定
docker compose exec laravel php artisan test tests/Feature/Admin/AuthTest.php
```

### カバレッジの確認

```bash
# ターミナルにカバレッジ率を表示
docker compose exec -e XDEBUG_MODE=coverage laravel php artisan test --coverage

# HTML レポートを生成（src/coverage/ に出力）
docker compose exec -e XDEBUG_MODE=coverage laravel php artisan test --coverage-html coverage
```

> カバレッジ目標: **85%**

### テスト構成

| 種別 | 場所 | 内容 |
|------|------|------|
| Unit/Policies | `tests/Unit/Policies/` | Policy のロール×アクション全組み合わせ（DB 不使用） |
| Unit/Services | `tests/Unit/Services/` | Service のビジネスロジック（Repository は Mockery でモック） |
| Feature/Admin | `tests/Feature/Admin/` | 管理者ルートの HTTP レスポンス検証 |
| Feature/Web | `tests/Feature/Web/` | 一般ユーザールートの HTTP レスポンス・スコープ制御検証 |

- Feature テストは `DatabaseTransactions` を使用（各テスト後にロールバック）
- テスト DB は開発 DB と分離（`db_test` コンテナ / `book_stock_test` DB）

---

## URL 一覧

### 一般ユーザー（store_users）

| URL | 説明 |
|-----|------|
| `GET /login` | ログイン画面 |
| `GET /dashboard` | ダッシュボード |
| `GET /books` | 書籍一覧 |
| `GET /stocks` | 在庫一覧 |
| `GET /stocks/export` | 在庫 CSV エクスポート |
| `GET /store-users` | スタッフ一覧 |
| `GET /purchase-histories` | 購入履歴一覧 |
| `GET /purchase-histories/create` | 購入履歴登録 |
| `GET /purchase-histories/{id}` | 購入履歴詳細 |

### 管理者（admins）

| URL | 説明 |
|-----|------|
| `GET /admin/login` | 管理者ログイン画面 |
| `GET /admin/dashboard` | 管理ダッシュボード |
| `GET /admin/stores` | 店舗一覧 |
| `GET /admin/store-users` | ユーザー一覧（全店舗） |

---

## ユーザーロールと権限

| 機能 | admin | owner | employee |
|------|:-----:|:-----:|:--------:|
| 店舗 CRUD（全店舗） | ✅ | — | — |
| ユーザー CRUD（全店舗） | ✅ | — | — |
| ユーザー CRUD（自店舗） | — | ✅ | — |
| ユーザー参照（自店舗） | — | ✅ | ✅ |
| 書籍 CRUD | — | ✅ | ✅ |
| 在庫 CRUD（自店舗） | — | ✅ | ✅ |
| 在庫 CSV エクスポート | — | ✅ | ✅ |
| 購入履歴 参照・登録（自店舗） | — | ✅ | ✅ |
| 購入履歴 削除（自店舗） | — | ✅ | — |

**スコープ制御の方針：**
- `store_id` はすべてログインユーザーのセッションから取得し、リクエスト入力値を信用しない
- リポジトリ層で `where('store_id', $user->store_id)` による絞り込みを徹底

---

## ER 図

```
admins
  id            BIGINT PK
  name          VARCHAR(255)
  email         VARCHAR(255) UNIQUE
  password      VARCHAR(255)
  remember_token
  timestamps

stores
  id            BIGINT PK
  name          VARCHAR(255)
  address       VARCHAR(255) NULL
  phone         VARCHAR(20) NULL
  timestamps

store_users
  id            BIGINT PK
  store_id      BIGINT FK → stores.id (CASCADE)
  name          VARCHAR(255)
  email         VARCHAR(255) UNIQUE
  email_verified_at TIMESTAMP NULL
  password      VARCHAR(255)
  role          ENUM('owner', 'employee')
  remember_token
  timestamps

books
  id            BIGINT PK
  isbn          VARCHAR(20) UNIQUE NULL
  title         VARCHAR(255)
  author        VARCHAR(255)
  publisher     VARCHAR(255) NULL
  price         INT UNSIGNED NULL  ※円単位
  timestamps

stocks
  id            BIGINT PK
  store_id      BIGINT FK → stores.id (CASCADE)
  book_id       BIGINT FK → books.id (CASCADE)
  quantity      INT UNSIGNED DEFAULT 0
  timestamps
  UNIQUE(store_id, book_id)

purchase_histories
  id            BIGINT PK
  store_id      BIGINT FK → stores.id (CASCADE)
  book_id       BIGINT FK → books.id (CASCADE)
  store_user_id BIGINT FK → store_users.id (CASCADE)
  quantity      INT UNSIGNED
  purchased_at  TIMESTAMP
  timestamps

password_reset_tokens
  email   VARCHAR PRIMARY KEY
  token   VARCHAR
  created_at

sessions
  id            VARCHAR PRIMARY KEY
  user_id       BIGINT NULL ※外部キー制約なし・複数ガード対応
  ip_address    VARCHAR(45) NULL
  user_agent    TEXT NULL
  payload       LONGTEXT
  last_activity INT
```

**リレーション：**

```
stores      ──< store_users
stores      ──< stocks
stores      ──< purchase_histories
books       ──< stocks
books       ──< purchase_histories
store_users ──< purchase_histories
```

---

## ディレクトリ構成（主要部分）

```
book-stock-sandbox/
├── docker/
│   ├── laravel/
│   │   ├── Dockerfile          # PHP 8.3-fpm + Node.js 20 + Composer
│   │   └── entrypoint.sh       # php-fpm + npm run dev 起動スクリプト
│   └── nginx/
│       ├── Dockerfile          # nginx:alpine ベース
│       └── nginx.conf          # リバースプロキシ設定（→ PHP-FPM:9000）
├── docker-compose.yml
├── .env.example
└── src/                        # Laravel プロジェクト
    ├── app/
    │   ├── Actions/Fortify/    # Fortify アクション（CreateNewUser 等）
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── Admin/      # 管理者向けコントローラー
    │   │   │   └── Web/        # 一般ユーザー向けコントローラー
    │   │   ├── Middleware/
    │   │   │   └── HandleInertiaRequests.php
    │   │   └── Requests/
    │   │       ├── Admin/      # 管理者向けフォームリクエスト
    │   │       └── Web/        # 一般ユーザー向けフォームリクエスト
    │   ├── Models/             # Admin, Store, StoreUser, Book, Stock, PurchaseHistory
    │   ├── Policies/           # StorePolicy, StoreUserPolicy, BookPolicy, StockPolicy, PurchaseHistoryPolicy
    │   ├── Providers/
    │   │   └── FortifyServiceProvider.php
    │   ├── Repositories/       # データアクセス層
    │   └── Services/           # ビジネスロジック層
    ├── bootstrap/
    │   ├── app.php             # ルーティング・ミドルウェア設定
    │   └── providers.php
    ├── config/
    │   ├── auth.php            # マルチガード設定 (web/admin)
    │   └── fortify.php
    ├── database/migrations/
    ├── resources/
    │   ├── js/
    │   │   ├── Layouts/        # AdminLayout.vue, AppLayout.vue
    │   │   └── Pages/          # Inertia ページコンポーネント
    │   └── views/
    │       └── app.blade.php   # Inertia ルートテンプレート
    └── routes/
        ├── admin.php           # /admin/* ルート
        └── web.php             # 一般ユーザー向けルート
```

---

## 認証設計

| ガード | モデル | テーブル | 用途 |
|--------|--------|----------|------|
| `web` (デフォルト) | `StoreUser` | `store_users` | オーナー・従業員 |
| `admin` | `Admin` | `admins` | アプリケーション運営者 |

- `web` ガード認証は **Laravel Fortify** が担当（ログイン・登録・パスワードリセット・メール認証）
- `admin` ガード認証は **独自コントローラー** (`Admin\AuthController`) が担当
- Fortify の UI はすべて **Inertia + Vue 3** で実装（Fortify の Blade ビューは使用しない）

---

## 仮定事項

- `books` テーブルは全店舗共有のマスターデータとして設計しています。
  「自店舗で扱う書籍」の絞り込みは `stocks` テーブルを介したクエリで実現します。
- `sessions.user_id` には外部キー制約を設けていません（admin / store_users の複数ユーザーテーブルに対応するため）。
- 新規登録（`/register`）は `role = owner` で `StoreUser` を作成します。
  店舗への紐付けは管理者または本人が後から行う運用を想定しています。
- Laravel 13 がインストールされています（CLAUDE.md の記載は Laravel 12 ですが、`composer create-project` 実行時点の最新版が 13 でした。動作に差異はありません）。
