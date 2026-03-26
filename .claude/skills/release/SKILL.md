---
name: release
description: develop → main への PR を作成し、CI 通過後にマージしてリリースする
---

以下の手順で develop から main へのリリースを行ってください。

## ステップ 1: ブランチの確認

現在のブランチを確認する。

```bash
git branch --show-current
```

`develop` 以外のブランチにいる場合は「develop ブランチにいません。develop に切り替えてから実行してください」と伝えて中止する。

## ステップ 2: develop の最新化

```bash
git pull origin develop
```

## ステップ 3: リリース内容の確認

`git log main..develop --oneline` を実行し、main に未反映のコミット一覧をユーザーに提示する。

```bash
git log main..develop --oneline
```

未反映コミットが 0 件の場合は「main はすでに develop と同じ状態です。リリースするものがありません」と伝えて終了する。

## ステップ 4: PR 作成の確認

リリース内容をユーザーに示し、「この内容で main への PR を作成してよいですか？」と確認する。

ユーザーが否定した場合はここで終了する。

## ステップ 5: PR 作成

`git log main..develop --oneline` の内容をもとに PR タイトルと本文を生成し、以下のコマンドで PR を作成する。

```bash
gh pr create --title "<タイトル>" --base main --head develop --body "<本文>"
```

タイトル例: `release: YYYY-MM-DD リリース`

本文フォーマット:

```
## リリース内容
<main に未反映のコミット一覧（git log main..develop --oneline の出力）>

## 確認事項
- [ ] develop で CI が通過していること
- [ ] ステージング環境での動作確認が完了していること
```

PR 作成後は URL をユーザーに伝える。

## ステップ 6: CI の監視

push 直後は CI がまだ起動していない場合があるため、10 秒待ってから run ID を取得して watch する。
コマンド置換（`$()`）による確認ダイアログを避けるため、2 つのコマンドに分けて実行すること。

```bash
# ステップ 6-1: 待機して run ID を取得する
sleep 10
gh run list --branch develop --limit 1 --json databaseId --jq '.[0].databaseId'

# ステップ 6-2: 取得した run ID を使って watch する（ID は数値リテラルで指定）
gh run watch <取得した run ID>
```

CI が失敗した場合は「CI が失敗しました。ログを確認して修正してください」と伝えて中止する。

CI が通過した場合は次のステップへ進む。

## ステップ 7: マージの確認

ユーザーに確認を求める:
「CI が通過しました。PR を main にマージしてよいですか？」

ユーザーが否定した場合はここで終了する。

## ステップ 8: マージ

PR をマージする（develop ブランチは削除しない）。

```bash
gh pr merge --merge
```

`--squash` は使わないこと。develop の履歴を main に保持するためマージコミットを使う。

完了後、「main へのリリースが完了しました」と伝えて終了する。
