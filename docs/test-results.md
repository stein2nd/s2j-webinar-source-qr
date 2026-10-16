# テスト戦略と結果記録

本プラグインの検証方針です。Local / Studio / PHPUnit の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このリポジトリ固有の手順** です。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリ |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (結合スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` |
| **2** | テストサイト構成の再現 (GatherPress 同梱) | **WordPress Studio**、Blueprints | [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) |
| **3** | PHP 自動テスト、PR 時のゲート | PHPUnit、GitHub Actions | 単体: `composer test:unit`。統合: `npm run test:unit:php:local` (MySQL) または CI `phpunit-wp-env` |

段階1・2は実 WordPress。段階3の **3a (unit)** は WordPress DB 不要。段階 **3b (integration)** は MySQL + テスト用 DB、または Docker (wp-env) です。

**依存:** 段階1・2では **[GatherPress](https://github.com/stein2nd/gatherpress)** を **有効化必須** です (wp.org 版では **GatherPress alpha** も必要な場合があります)。

### Docker なしの開発環境

**3a (unit)** は PHP + Composer のみ。**3b (integration)** は **Local の MySQL** で `npm run test:unit:php:local` が可能 (初回のみ Subversion で WP テスト lib 取得)。**Docker (wp-env)** は CI 向けの代替経路です。

| 段階 | Docker なしでローカル実行 | 代替 |
| --- | --- | --- |
| **3a** | **可能** — `npm run test:unit:php:unit-only` (= `composer test:unit`) | CI `phpunit-pure` と同一 |
| **3b** | **可能** — `npm run test:unit:php:local` (要 MySQL テスト DB、`.env.tests`) | **PR / push 後の GitHub Actions `phpunit-wp-env`** (Docker)。Pass は [最新結果](#最新結果) に記録 |
| **1** | **可能** — Local / Studio + `npm run test:smoke:local` | `WP_PATH` を Site Shell または Studio ルートに |
| **2** | **可能** — Studio Blueprint + 手動 UI | CLI は段階1と同じ smoke |

日常のループ例: **`npm run test:unit:php:unit-only`** → (統合を触ったら) **`npm run test:unit:php:local`** または push 後 CI **`phpunit-wp-env`**。

## 推奨実施順 (S2J Webinar 同型・ localhost 自動テスト優先)

**段階 (1/2/3)** と **環境 (localhost / HTTPS)** は別軸です。先に **localhost で完結する自動テスト** をそろえ、HTTPS は後回しにします。

| 順 | 段階 | 環境 | 内容 | 本リポジトリ |
| --- | --- | --- | --- | --- |
| **0** | **3a** | **Local 可のみ** (WP 不要) | 単体 PHPUnit ・ lint ・ CI と同型 | `npm run test:unit:php:unit-only` / `npm run lint` |
| **1** | **1** | **Local 可のみ** (CLI) | Local **Site Shell** と Studio ルートの **`WP_PATH`** で同じスクリプト | `npm run test:smoke:local` |
| **2** | **3b** | **Local 可** (MySQL) / **CI** (Docker) | 統合 PHPUnit — `tests/Integration/` | `npm run test:unit:php:local` または PR の `phpunit-wp-env` |
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

[S2J MediaLibrary Date Corrector](https://github.com/stein2nd/s2j-media-library-date-corrector) と同型。**wp-env / Docker は必須ではありません** (CI では引き続き利用可)。

### 3a. 単体 (PHP + Composer のみ)

```bash
composer install
npm run test:unit:php:unit-only
# 同等: composer test:unit
```

WordPress / MySQL 不要。`tests/phpunit/` (`includes/Domain/document.php` 等) を検証します。CI job **`phpunit-pure`** と同一です。

### 3b. 統合 (MySQL + 初回のみ Subversion)

**前提:** 開発サイト DB (`local` 等) とは **別名** のテスト DB。`.env.tests.example` を `.env.tests` にコピーして接続を合わせます。

```bash
composer install
npm run test:unit:php:local
```

`pretest:unit:php:local` で Composer install 後、`bin/phpunit-local.sh` が **unit → (必要なら) WP テスト lib インストール → integration** の順で実行します。初回は **MySQL** と **svn** (テスト lib 取得) が必要です。GatherPress (wp.org zip) と Webinar REST スタブはスクリプトが `tests/.wordpress/wp-content/plugins/` に用意します。

#### 統合 bootstrap (GatherPress / WP 6.7+)

[`tests/bootstrap.php`](../tests/bootstrap.php) は wp-env / ローカル MySQL の **同一** エントリです。GatherPress 有効化は [GatherPress #2426](https://github.com/GatherPress/gatherpress/issues/2426) の指摘に合わせ、次のとおりです。

| タイミング | 処理 |
| --- | --- |
| `plugins_loaded` (0) | `require_once` — `gatherpress/gatherpress.php` と Webinar REST スタブ。**`activate_plugin()` で GatherPress を有効化しない** (`after_setup_theme` 前のヘッダ翻訳 → WP 6.7+ Notice) |
| `init` (0) | `GatherPress\Core\Setup::get_instance()->activate_gatherpress_plugin( false )` — events 用 DB セットアップ (silent `activate_plugin` では activation hook が走らない問題の回避) |
| `init` (0) | Webinar スタブのみ `activate_plugin( …, true )` — `RestDocumentTest` の `deactivate_plugins()` 用 (スタブに Text Domain ヘッダなし) |

ローカル再確認 (2026-10-17): `npm run test:unit:php:local` — **Notice なし**、integration **6/6** PASS。

### 3b (代替) . wp-env — CI / Docker

```bash
npm install
npm run phpunit:wp-env
```

`.wp-env.json` で GatherPress + 本プラグイン + スタブを読み込み、**CI `phpunit-wp-env`** と同じ `tests/Integration/` を実行します。

---

## 最新結果

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-14 | 3a | CI | PASS | `composer test` (PR #1) |
| 2026-10-14 | 3b | CI (wp-env) | PASS | `RestDocumentTest` draft200 / 503 / apply200 (QR Apply PR) |
| 2026-10-14 | 1 | Local | PASS | `npm run test:smoke:local` (blockpluginbase) |
| 2026-10-14 | 2 | Local | PASS | U1– U12 (U10Apply503/501は CI Integration 代替)。新規イベント |
| 2026-10-16 | 3a | Local | PASS | `npm run test:unit:php:local` 内 — unit **4** tests, 13 assertions (WP 不要) |
| 2026-10-16 | 3b | Local (MySQL) | PASS | 同上 — integration **6** tests (合計 **31** assertions)。`.env.tests` / DB `wordpress_test` / GatherPress 実体 + Webinar REST stub。apply200・中央 icon・invalid icon **400** |
| 2026-10-17 | 3b | Local (MySQL) | PASS | bootstrap 変更後 ([GatherPress #2426](https://github.com/GatherPress/gatherpress/issues/2426)) — **WP 6.7+ Notice なし**、integration **6/6** (計 **31** assertions) |
| (未記録) | 2 | Studio | — | Blueprint 再現 (Local で U1– U9済) |
