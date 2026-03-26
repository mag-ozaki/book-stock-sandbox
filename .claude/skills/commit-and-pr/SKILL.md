---
name: commit-and-pr
description: 変更をコミットし、push して GitHub PR を作成する
---

以下の手順でコミットから PR 作成・マージ・develop 同期までを行ってください。

## ステップ 1: 変更ファイルの確認

`git status` と `git diff` を実行し、変更内容を確認する。

変更ファイルが存在しない場合は「コミットする変更がありません」と伝えて終了する。

## ステップ 2: コミットメッセージの提案

diff の内容をもとに、CLAUDE.md のコミットメッセージルールに従ってコミットメッセージを自動生成し、ユーザーに提示する。

```
形式: <type>: <内容>（日本語）
type: feat / fix / refactor / test / docs / chore
例: feat: 在庫一覧の CSV エクスポート機能を追加
```

ユーザーに確認を求める:
「このコミットメッセージでよいですか？変更する場合は入力してください。」

ユーザーが修正を入力した場合はそれを採用する。OK であればそのまま進む。

## ステップ 3: git add & git commit

`.env` `.env.*` を除く全変更ファイルをステージングし、コミットする。

```bash
git add --all
git restore --staged .env .env.* 2>/dev/null || true
git commit -m "<確定したコミットメッセージ>"
```

## ステップ 4: テストの実行

以下の順でテストを実行する:

```bash
docker compose up -d
docker compose exec laravel php artisan migrate --env=testing
docker compose exec -e XDEBUG_MODE=coverage laravel php artisan test --coverage --min-lines-covered-percentage=85
```

テストが失敗した場合、またはカバレッジが 85% を下回った場合は「テストが失敗しました。修正してから再実行してください」と伝えて中止する。

テストが通過した場合は次のステップへ進む。

## ステップ 5: push & PR 作成の確認

現在のブランチを確認する。

`develop` ブランチの場合: 「現在 develop ブランチにいます。push のみ行ってよいですか？」と確認し、承認されれば push して終了する。

feature / fix 等のブランチの場合: 「push して PR を作成してよいですか？」と確認する。ユーザーが否定した場合はコミットのみで終了する。

## ステップ 6: push

```bash
git push origin <現在のブランチ名>
```

## ステップ 7: PR 作成

`git log` と `git diff develop...HEAD` をもとに PR タイトルと本文を生成し、以下のコマンドで PR を作成する。

```bash
gh pr create --title "<タイトル>" --base develop --body "<本文>"
```

本文フォーマット:

```
## 概要
<変更内容を2〜3行で説明>

## 変更ファイル
<変更したファイルの一覧>

## 確認事項
- [ ] テストが通過していること
- [ ] 認証・認可の抜け漏れがないこと
- [ ] 店舗スコープの制御が正しいこと
```

PR 作成後は URL をユーザーに伝える。

## ステップ 8: CI の監視

push 直後は CI がまだ起動していない場合があるため、最新の run ID を取得してから watch する。

```bash
# CI が起動するまで最大 30 秒待機しながら run ID を取得する
for i in $(seq 1 6); do
  RUN_ID=$(gh run list --branch <現在のブランチ名> --limit 1 --json databaseId --jq '.[0].databaseId')
  [ -n "$RUN_ID" ] && break
  sleep 5
done

# run ID を指定して CI の完了を待機する
gh run watch "$RUN_ID"
```

CI が失敗した場合は「CI が失敗しました。ログを確認して修正してください」と伝えて中止する。

CI が通過した場合は次のステップへ進む。

## ステップ 9: マージの確認

ユーザーに確認を求める:
「CI が通過しました。PR をマージして develop に同期してよいですか？」

ユーザーが否定した場合はここで終了する。

## ステップ 10: マージ & develop 同期

PR をスカッシュマージしてブランチを削除し、develop を最新化する。

```bash
gh pr merge --squash --delete-branch
git checkout develop
git pull origin develop
```

完了後、「マージと develop への同期が完了しました」と伝えて終了する。
