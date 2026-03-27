# ローカル開発環境

## 前提

WSL2 上の docker compose を使用する。

## コンテナ構成

| コンテナ | 役割 |
|---|---|
| `laravel` | PHP-FPM（appuser UID/GID=1000 で実行） |
| `db` | PostgreSQL 5432（本番用） |
| `db_test` | PostgreSQL 5432（テスト用） |
| `nginx` | リバースプロキシ（ポート 80 → PHP-FPM 9000） |

## Docker 設計方針

- プロジェクトファイルは WSL 上から bind mount すること（nginx / laravel 両コンテナにマウント）
- UID / GID は 1000 / 1000 に揃えること
- Laravel コンテナ内では UID=1000, GID=1000 の `appuser` を作成すること
- PHP-FPM プロセスは `appuser` で実行すること
- WSL ホスト側を不必要に汚す構成は避けること

## よく使うコマンド

```bash
# コンテナ起動
docker compose up -d

# one-off コマンド実行
docker compose run --rm --no-deps --entrypoint "" laravel <cmd>

# テスト DB にマイグレーション適用
docker compose exec laravel php artisan migrate --env=testing

# テスト実行
docker compose exec laravel php artisan test
```
