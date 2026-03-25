---
name: run-tests
description: Docker コンテナ上でテスト DB のマイグレーションを適用してから PHPUnit テストを実行する
---

以下の手順でテストを実行してください:

1. `docker compose up -d` でコンテナを起動する
2. `docker compose exec laravel php artisan migrate --env=testing` でテスト DB にマイグレーションを適用する
3. `docker compose exec -e XDEBUG_MODE=coverage laravel php artisan test --coverage --min-lines-covered-percentage=85` でテストを実行する

各コマンドは順番に実行し、前のステップが成功してから次に進むこと。
