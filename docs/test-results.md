# テスト戦略と結果記録

本プラグインの検証方針です。Local / Studio / PHPUnit の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このリポジトリ固有の手順** です。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリ |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (結合スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` |
| **2** | テストサイト構成の再現 (GatherPress 同梱) | **WordPress Studio**、Blueprints | [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) |
| **3** | PHP 自動テスト、PR 時のゲート | PHPUnit、GitHub Actions | `composer test` (純ロジック)。WP 統合は `npm run phpunit:wp-env` |

段階1・2は実 WordPress、段階3の **3a** は WordPress を起動しない純 PHPUnit です。

**依存:** 段階1・2では **[GatherPress](https://github.com/stein2nd/gatherpress)** を **有効化必須** です (wp.org 版では **GatherPress alpha** も必要な場合があります)。

### Docker なしの開発環境

**`npm run phpunit:wp-env` (段階3b) だけが Docker 必須** です (`@wordpress/env` がコンテナで WordPress を起動する)。Docker Desktop 等がないマシンでは **ローカルで3b は実行しない** 想定で問題ありません。

| 段階 | Docker なしでローカル実行 | 代替 |
| --- | --- | --- |
| **3a** | **可能** — `composer test` | CI `phpunit-pure` と同一 |
| **1** | **可能** — Local / Studio + `npm run test:smoke:local` | `WP_PATH` を Site Shell または Studio ルートに |
| **2** | **可能** — Studio Blueprint + 手動 UI | CLI は段階1と同じ smoke |
| **3b** | **不可** (wp-env) | **PR / push 後の GitHub Actions `phpunit-wp-env`** で確認。Pass は [最新結果](#最新結果) に記録 |

日常のループ例: **`composer test`** → 変更を push → CI の **`phpunit-wp-env`** を見る。Integration を触った PR では `phpunit-wp-env` が緑であることをマージ条件にすれば足ります。

## 推奨実施順 (S2J Webinar 同型・ localhost 自動テスト優先)

**段階 (1/2/3)** と **環境 (localhost / HTTPS)** は別軸です。先に **localhost で完結する自動テスト** をそろえ、HTTPS は後回しにします。

| 順 | 段階 | 環境 | 内容 | 本リポジトリ |
| --- | --- | --- | --- | --- |
| **0** | **3a** | **Local 可のみ** (WP 不要) | 純 PHPUnit ・ lint ・ CI と同型 | `composer test` / `npm run lint` |
| **1** | **1** | **Local 可のみ** (CLI) | Local **Site Shell** と Studio ルートの **`WP_PATH`** で同じスクリプト | `npm run test:smoke:local` |
| **2** | **3b** | **Local 可のみ** (要 Docker) | wp-env 統合 — **Docker なしは CI** | `npm run phpunit:wp-env` または PR の `phpunit-wp-env` |
| **3** | **2** | **Local 可のみ** | Blueprint 再現 + (1) 再実行 + **手動** UI (Fill / モーダル) | [studio/](../studio/) |
| **4** | **1–3** | **Local 可 (E2/E4/E6)** | Webinar 定義 REST 成立後の Save draft ・ Apply **501/200** | [status.md §外部結合](./status.md#status-external) |
| **5** | **2–3** | **要 HTTPS** | OAuth (E1) ・実 Zoom 取り込み (E3) ・ステージング UI | [test-results.md 最新結果](#最新結果) に Pass 記録 |

**補足**

* **段階1に HTTPS 版は通常なし** — スモークは Local / Studio の WordPress に対する WP-CLI のみ。
* **順0** は段階番号より先に回してよい (フィードバックが最も速い)。
* **順3** の Studio は CLI スモークと役割が重なるため、**(1) (2) が緑のあと**で Blueprint 再現性とブラウザ確認に使う。
* HTTPS 行は **自動ゲート (CI) の必須条件にしない** — [status.md](./status.md) の実装％・外部結合ゲート参照。

### localhost で可能 / 不可能 (S2J Webinar と同型)

| 観点 | Local / Studio (`localhost`) | 備考 |
| --- | --- | --- |
| プラグイン有効化、REST ルート登録 | **可能** | 段階1 `smoke-local.sh` |
| Gutenberg Fill 表示 (GatherPress イベント編集) | **可能** (GatherPress 必須) | `dist/` ビルド必須 |
| `GET/POST …/document` (本プラグイン REST) | **可能** | 定義カタログ不要の GET |
| **`catalogReady` / Save draft / Apply** | **条件付き** | **S2J Webinar 有効** + 定義 REST 成功 + `sources.length >= 1` |
| Webinar **Import** (`tracking-sources`) | **条件付き** | 上記 + `_s2j_webinar_id` + `provider: zoom` |
| Zoom OAuth / 公衆 Webhook | **不可** (localhost) | Webinar 側と同様 HTTPS ステージングで検証 |
| QR PNG **Apply** (501まで) | **可能** | パイプライン未実装時は **501** `qr_not_implemented` が期待 |

---

## 第1段階: Local (結合スモーク)

| # | 確認項目 | 期待 |
| --- | --- | --- |
| 1 | `composer install` + `npm run build:production` | `vendor/` と `dist/js/s2j-webinar-source-qr-gutenberg.js` |
| 2 | GatherPress 有効化 | エラーなく有効 |
| 3 | 本プラグイン有効化 | エラーなく有効 |
| 4 | GatherPress **イベント** 編集 | Fill **Edit source tracking / QR** |
| 5 | (任意) S2J Webinar 有効 + 定義 REST | Save draft が200、Import 表示 |

```bash
cd wp-content/plugins/s2j-webinar-source-qr
export WP_PATH="/path/to/site/app/public"
npm run test:smoke:local
```

---

## 第2段階: WordPress Studio (順3— 手動 UI)

**目的:** Blueprint 再現 + ブラウザで Gutenberg Fill / 4ステップモーダルを確認する。**`catalogReady` false** を含む **骨格 PASS** が順3の完了条件。Save draft200や Import は [順4 (E2/E4)](#順4-オプション-local-可-e2e4)(Webinar 定義 REST 必須)。

### 下準備

| # | 手順 |
| --- | --- |
| P1 | `npm run build:production` または `npm run dist:build` — `dist/js/s2j-webinar-source-qr-gutenberg.js` があること |
| P2 | **Studio:** Upload Blueprint → [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) ([`studio/README.md`](../studio/README.md)) |
| P2' | **Local 代替可:** GatherPress 有効 + 本プラグインを `wp-content/plugins/` に配置 (symlink 可)。Studio と **同じ手動表** でよい |
| P3 | 本プラグインを有効化。Studio / Local 管理画面を **1回** 開く (mu-plugin が下書きイベント **S2J Source QR smoke event** を追加) |
| P4 | CLI: `export WP_PATH='<サイト public ルート>'` → `npm run test:smoke:local` が **PASS** (段階1再確認) |

**プラグイン配置 (Local 例):** Local サイト停止中は WP-CLI 不可。Site Shell から smoke を実行する。

### 手動 UI チェックリスト (順3必須)

GatherPress **イベント** 編集 (ブロックエディター) で実施。記録は [最新結果](#最新結果) に `段階2` 行を追加。

| # | 操作 | 期待 | PASS |
| --- | --- | --- | --- |
| U1 | サイドバー / Fill | **Edit source tracking / QR** (または同等ラベル) が表示される | [x] |
| U2 | 上記をクリック | モーダル **Source tracking / QR** が開く | [x] |
| U3 | モーダル上部 | **Step1of 4** (または現在ステップ表示) | [x] |
| U4 | Notice | **Saving requires S2J Webinar and a source catalog.** (または i18n 同等)。`catalogReady` **false** 想定 | [x] |
| U5 | Step1→ **Next** | Step2へ。ソース行 UI (定義未成立時は空または readOnly 想定) | [x] |
| U6 | Step2 | **モジュール color** — WordPress **ColorPicker** + Hex フィールド。`catalogReady` false 時は編集不可 | [x] |
| U7 | Step2→ **Next** | Step3— プレビュー placeholder 文言 (未実装マイルストーン) | [x] |
| U8 | Step3→ **Next** | Step4— Apply 説明文言 | [x] |
| U9 | フッター | **Save draft** disabled。**Back** 使用可、Step4で **Next** disabled。**Apply** は Step4のみ primary 表示 ([admin_ui_spec.md](./admin_ui_spec.md) 上 `!catalogReady` では Apply も disabled — 見た目が primary のときは U10で確認) | [x] |
| U10 | Step4 **Apply** | **503** (カタログ) または **501** (PNG 未実装) — **メタ ready にならない**。失敗時 **モーダル開いたまま** | [ ] |
| U11 | **Back** で Step1まで戻る | クラッシュ・白画面なし | [x] |
| U12 | モーダルを閉じる | イベント編集画面に戻れる (**X**) | [x] |

**U10:** `!catalogReady` で Apply が押せる場合はネットワークタブで POST が **503** になること。501は `catalogReady` + 全 URL 時 (順4 / CI)。Apply が押せないだけの場合は **CI Integration** ([段階3b](#3b-wp-env-統合-拡張)) で501を代替可。

### 順4 (オプション・ Local 可 E2/E4)

**S2J Webinar 有効** + イベント `_s2j_webinar_id` + 定義 REST200 (`sources.length >= 1`) のサイトでのみ。

| # | 操作 | 期待 |
| --- | --- | --- |
| E1 | モーダル open | Notice 消え、`catalogReady` true — Save draft 有効 |
| E2 | URL 入力 → **Save draft** | **200**、再 open で値保持 |
| E3 | Step4 **Apply** (全 URL 入力) | 現状 **501** `qr_not_implemented` (PNG 未実装) |

### 記録テンプレート

```markdown
| YYYY-MM-DD | 2 | Local / Studio | PASS / PARTIAL | U1–U12。任意 E1–E3。WP_PATH=… |
```

---

## 第3段階: PHPUnit

### 3a. 純 PHPUnit (CI ・ローカル)

```bash
composer install
composer test
# または
npm run test:unit:php:local
```

WordPress 非起動。`includes/Domain/document.php` の正規化・マージを検証します。

### 3b. wp-env 統合 (拡張)

**前提:** ローカル実行には **Docker** が必要。ない場合は [Docker なしの開発環境](#docker-なしの開発環境) — **CI の `phpunit-wp-env` job** を正とする。

```bash
npm install
npm run phpunit:wp-env
```

`.wp-env.json` で GatherPress + 本プラグインを読み込み、`tests/Integration/` を実行します。Local DB とは別コンテナです。

---

## 最新結果

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-14 | 3a | CI | PASS | `composer test` (PR #1) |
| 2026-10-14 | 3b | CI (wp-env) | PASS | `RestDocumentTest` draft200 / 503 / apply200 (QR Apply PR) |
| 2026-10-14 | 1 | Local | PASS | `npm run test:smoke:local` (blockpluginbase) |
| 2026-10-14 | 2 | Local | PASS | U1– U12 (U10Apply503/501は CI Integration 代替)。新規イベント |
| (未記録) | 2 | Studio | — | Blueprint 再現 (Local で U1– U9済) |
