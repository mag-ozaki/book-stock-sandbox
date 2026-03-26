---
name: commit-and-pr
description: 変更をコミットし、push して GitHub PR を作成する
---

以下の手順でコミットから PR 作成までを行ってください。

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
git add --all -- ':!.env' ':!.env.*'
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

ユーザーに確認を求める:
「push して PR を作成してよいですか？」

ユーザーが否定した場合はコミットのみで終了する。

## ステップ 6: push

```bash
git push origin <現在のブランチ名>
```

## ステップ 7: PR 作成

`git log` と `git diff develop...HEAD` をもとに PR タイトルと本文を生成し、以下の形式で PR を作成する。

- タイトル: コミットメッセージと同じ
- ベースブランチ: `develop`
- 本文フォーマット:

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

PR 作成後は URL をユーザーに伝えて完了とする。
