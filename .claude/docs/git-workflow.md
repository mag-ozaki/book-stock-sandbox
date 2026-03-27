# Git 運用ルール

## ブランチ戦略

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

## ブランチ命名規則

```
feature/purchase-history
fix/stock-scope-leak
refactor/book-repository
docs/update-readme
hotfix/auth-bypass
```

## 基本フロー

**通常の機能開発・バグ修正**（`/commit-and-pr` スキルを使うこと）
```
develop からブランチを切る
→ PR: feature/* (or fix/*) → develop
→ staging で動作確認
→ PR: develop → main（リリースタイミングで）
```

**本番リリース**（`/release` スキルを使うこと）
```
develop → main への PR を作成・CI確認・マージ
```

**本番緊急修正**
```
main から hotfix/* を切る
→ PR: hotfix/* → main
→ PR: hotfix/* → develop（乖離防止）
```

## ルール

- `main` / `develop` への直接 push は禁止
- 作業は必ずブランチを切ってから開始する
- PR マージ後は作業ブランチを削除する
- `release/*` ブランチは現時点では不要。リリース前調整が複雑になったタイミングで導入する

## CI（GitHub Actions）

`.github/workflows/test.yml` により、以下のタイミングで自動テストが実行される:
- `feature/*` / `fix/*` 等から `develop` / `main` への PR 作成・更新時
- `develop` / `main` への push 時

テストは Docker Compose 環境（PostgreSQL 使用）で実行される。
PR マージ前にテストが通過していることを確認すること。
