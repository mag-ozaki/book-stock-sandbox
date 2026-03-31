# CLAUDE.md

## プロジェクト概要

本屋向け在庫管理 PoC。主な利用者は admin（運営者）・owner（オーナー）・employee（従業員）。

技術スタック: Laravel 13 / Inertia.js + Vue 3 / Laravel Fortify / PostgreSQL / Docker Compose（WSL2）

PoC であっても、認証・認可・guard 設計・DB スキーマ・Docker 構成は本番運用を意識した品質に近づけること。

---

## 基本要件

以下の要件は、明示的に変更指示があるまで固定の前提として扱うこと。

### 認証

- Laravel Fortify を使用すること（UI は Inertia で構築）
- マルチガード認証:
  - `admin` guard → `admins` テーブル（独自 Controller）
  - `web` guard → `store_users` テーブル（Fortify 担当）
- `store_users.role` は `owner` または `employee`
- ユーザー登録（`Features::registration()`）は無効。admin が管理画面から store_users を作成する運用
- admin / web でログイン画面・ダッシュボード・ルートグループ・レイアウトを分離すること

### 認可

認可は Laravel Policy または Gate を使って実装すること。

| ロール | 権限 |
|---|---|
| admin | stores の CRUD（全店舗）、store_users の CRUD（全店舗）、store_api_keys の CRUD（全店舗）、genres CRUD |
| owner | 自店舗の store_users CRUD、books CRUD、stocks CRUD、purchase_histories Read/Create/Delete、sale_histories Read、genres CRUD |
| employee | 自店舗の store_users Read のみ、books CRUD、stocks CRUD、purchase_histories Read/Create、sale_histories Read、genres CRUD |

**店舗スコープ制御は必須。admin 以外は他店舗のデータにアクセスできてはならない。**

### データモデル

テーブル: `admins` / `stores` / `store_users` / `books` / `genres` / `stocks` / `purchase_histories` / `store_api_keys` / `sale_histories`

主なリレーション:
- stores hasMany store_users, stocks, purchase_histories, store_api_keys, sale_histories
- books hasMany stocks, purchase_histories, sale_histories
- books belongsTo genre（nullable）
- genres hasMany books
- stocks / store_users / purchase_histories / store_api_keys / sale_histories belongsTo store
- purchase_histories belongsTo book, store_user
- sale_histories belongsTo book

---

## 実装方針

### バックエンド構成

CRUD 系は Controller / Service / Repository / Model / Form Request / Policy の責務分離を基本とする。

- Controller は薄く、業務ロジックは Service、データアクセスは Repository
- バリデーションは Form Request、認可は Policy / Gate

### フロントエンド構成

- Inertia + Vue 3
- admin / web のレイアウト・ページ群を分離
- 早すぎる抽象化より保守性を優先

### 命名

- DB: snake_case / クラス: 単数形 PascalCase / ルート: guard が分かる名前
- 強い理由がない限り Laravel の慣習に従うこと

### スコープ安全性

- 必ず認証ユーザーの `store_id` で絞り込むこと
- 店舗所属判定をリクエスト入力だけで信用しないこと

---

## 品質基準

PoC であっても以下は妥協しないこと:

- 認証・認可が一貫していること
- CRUD がロール / スコープ制御を守っていること
- migration / relation が内部整合していること
- Docker 構成が他の開発者でも起動可能であること

速度のために認証・認可の正しさを犠牲にしないこと。

---

## 実装着手時の必須確認

1. `git status` で未コミットの変更がないか確認する（あれば中止）
2. `develop` ブランチにいることを確認する
3. `develop` から適切な feature / fix ブランチを切る
4. feature ブランチ上で実装を開始する

### コミットメッセージ形式

`<type>: <内容>`（日本語）
type: `feat` / `fix` / `refactor` / `test` / `docs` / `chore`

### 実装完了後のドキュメント更新

- `docs/*-spec.md`: 既存機能を変更した場合は対応する spec を更新する
- `CLAUDE.md`: テーブル追加・ロール権限変更があった場合、「データモデル」「認可」を更新する
- `README.md`: URL 一覧・権限テーブル・ER 図・ディレクトリ構成を更新する

---

## 迷ったときの原則

- Laravel の最も標準的な方法を選ぶ
- admin / web の分離を維持する
- 店舗単位のデータ分離を維持する
- 仮定した内容はレスポンスまたは README に明記する

---

## 詳細ドキュメント

| ドキュメント | 内容 |
|---|---|
| `.claude/docs/agent-workflow.md` | 機能追加時のエージェント構成・フェーズ詳細 |
| `.claude/docs/testing.md` | テスト方針の詳細（トレイト・認可テスト・カバレッジ等） |
| `.claude/docs/git-workflow.md` | Git 運用ルール詳細（ブランチ戦略・フロー・CI） |
| `.claude/docs/development.md` | ローカル開発環境・Docker 構成詳細 |
| `.claude/docs/specs/*-spec.md` | 各機能の仕様書 |
