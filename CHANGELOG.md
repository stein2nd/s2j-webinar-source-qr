# S2J ウェビナー・ソース追跡 QR - CHANGELOG

## セキュリティ (開発依存 - upstream 待ち)

最終確認: **2026-10-15** — `npm audit`: **23** 件 (moderate: 13/high: 8/critical: 2)、`npm audit --omit=dev`: **0**。配布 zip は `dist/` + PHP `vendor/` (Composer)。`node_modules` は同梱しない。`npm audit fix --force` は採用しない (破壊的ダウングレード)。

依存更新 (`ncu` / `npm install`) のたびに上記2コマンドを再実行し、件数と下記の解消状況をこの節で更新する。

* **`@wordpress/env`** (`wp-env` / CI `phpunit-wp-env`): transitive **`simple-git` / `@simple-git/argv-parser`** (critical、[GHSA-v5rq-49vh-5v5c](https://github.com/advisories/GHSA-v5rq-49vh-5v5c))、**`qs`** (moderate)、**`@wp-playground/cli`** 経由。実行は開発、CI のみ。**待ち**: [WordPress `@wordpress/env`](https://github.com/WordPress/gutenberg/tree/trunk/packages/env) / Playground CLI の依存更新 (npm の自動 fix は `@wordpress/env@3` 提案のため不可)
* **`@s2j/docs-linter`** (`npm run lint:docs`): **`textlint-rule-prh` / `prh`**、プリセット (`textlint-rule-preset-wp-docs-ja` 等)、**`sprintf-js`** (moderate、npm 上 fix なし)。**待ち**: [docs-linter](https://github.com/stein2nd/s2j-docs-linter) および textlint プリセット側
* **`stylelint`** (`npm run lint:scss`): **`braces` / `micromatch` / `fast-glob` / `globby`** (high)。npm の自動 fix は stylelint v7ダウングレード提案のため不可。**待ち**: [stylelint/stylelint](https://github.com/stylelint/stylelint) の transitive 更新
* **`vite-plugin-static-copy`** (`npm run build:*`): 同梱 **chokidar** 経由の **`braces`** (high)。npm の自動 fix は v0系ダウングレード提案のため不可。**待ち**: [vite-plugin-static-copy](https://github.com/sapphi-red/vite-plugin-static-copy) の chokidar 更新

## unreleased

## 0.0.1 - 2026-10-15

### Added

* **QR Apply (PR1):** `chillerlan/php-qrcode` + GD — [`includes/Domain/qr.php`](./includes/Domain/qr.php)。`POST …/apply` **200**、`status: ready`、行ごと PNG メディア (`qr_attachment_id`)
* **QR 中央 icon:** `style.icon_attachment_id` を Apply PNG に合成 (JPEG/PNG/GIF/WebP、白 quiet-zone プレート)
* wp-env Integration: 中央 icon 合成・不正 `icon_attachment_id` **400** ([`RestDocumentTest.php`](./tests/Integration/RestDocumentTest.php))
* 配布 zip に `vendor/` を同梱 (`predist:zip` + [`make-dist-zip.mjs`](./tools/dist/make-dist-zip.mjs)、[dist-zip.yml](./.github/workflows/dist-zip.yml) で Composer)

### Changed

* wp-env Integration: apply **501** → **200** (draft **200** / catalog **503** は維持)
* [`s2j-webinar-source-qr.php`](./s2j-webinar-source-qr.php): Composer autoload 読み込み
* [`includes/RestController.php`](./includes/RestController.php): apply 経路で [`s2j_wsq_apply_document_with_qr()`](./includes/Domain/qr.php) を呼び出し
* [status.md](./docs/status.md) / [test-results.md](./docs/test-results.md): QR Apply ・中央 icon ・**localhost / HTTPS** テスト順 ([PR #1](https://github.com/stein2nd/s2j-webinar-source-qr/pull/1)、[PR #2](https://github.com/stein2nd/s2j-webinar-source-qr/pull/2))
* 開発依存: `@wordpress/env` ^11、`@s2j/docs-linter` ^1.0.29、React / ESLint / Rollup 等 (`ncu` 追随)
* textlint の許可パターンに `#status-*` アンカー ID を追加

### Fixed

* **CI:** `main` の [phpunit.yml](./.github/workflows/phpunit.yml) が `npm run phpunit:wp-env` を実行する一方、`package.json` に script 未定義だった不整合 — テスト基盤マージ ([PR #1](https://github.com/stein2nd/s2j-webinar-source-qr/pull/1)) で `phpunit:wp-env` / `.wp-env.json` を追加

### Notes

* Step3: JS プレビュー、Apply 成功時モーダル close、Webinar handoff、meta `sanitize_callback` 厳格は未実装 ([status.md](./docs/status.md))

## 0.0.1 - 2026-10-14

### Added

* **docs 正本追随 (コード):** Webinar 定義カタログ ([`includes/Domain/catalog.php`](./includes/Domain/catalog.php))、`provider` 解決、固定 channel 廃止・定義順 `channels` ([`includes/Domain/document.php`](./includes/Domain/document.php))
* REST: `GET/POST …/document`、draft / apply (カタログ **503**、apply **501** `qr_not_implemented`) ([`includes/RestController.php`](./includes/RestController.php))
* Gutenberg: 定義 GET → 行マージ、`catalogReady`、Import 成功時 **POST draft**、Step2 **ColorPicker** + Hex ([`src/gutenberg/index.tsx`](./src/gutenberg/index.tsx))
* テスト: 純 PHPUnit ([`DocumentLogicTest.php`](./tests/phpunit/DocumentLogicTest.php))、段階1 [`smoke-local.sh`](./tests/smoke-local.sh)、wp-env Integration ([`RestDocumentTest.php`](./tests/Integration/RestDocumentTest.php): draft **200** / **503** / apply **501**)、Webinar 定義 REST スタブ ([`tests/fixtures/s2j-webinar/`](./tests/fixtures/s2j-webinar/))
* CI / 配布: [js-lint.yml](./.github/workflows/js-lint.yml)、[phpunit.yml](./.github/workflows/phpunit.yml)(pure + wp-env)、[`.wp-env.json`](./.wp-env.json)、Studio [`studio/`](./studio/)

### Changed

* [status.md](./docs/status.md): 機能一覧・ Test 見出し (**Local 可のみ** / **要 HTTPS**)、[推奨テスト実施順](./docs/status.md#status-test-order)
* [test-results.md](./docs/test-results.md): localhost 可能表、推奨実施順、**Docker なし**(3b は CI `phpunit-wp-env` で代替)
* [README.md](./README.md): テスト段階と Docker 要否の注記
* `.wp-env.json`: GatherPress を [wordpress.org plugin zip](https://downloads.wordpress.org/plugin/gatherpress.zip)(GitHub archive **404** 回避)

### Fixed

* [`tests/smoke-local.sh`](./tests/smoke-local.sh): macOS Bash3.2 + `set -u` で空 `"${WP_ARGS[@]}"` が落ちる問題 (`wp_cmd` ラッパー)

### Notes

* Step3プレビュー、Apply 成功時 UI close、Webinar handoff、meta `sanitize` 厳格 ([status.md](./docs/status.md))
* 段階 **3b** ローカル実行は Docker 必須。Docker なし環境は PR の **`phpunit-wp-env`** を正とする (2026-10-14CI PASS: [PR #1](https://github.com/stein2nd/s2j-webinar-source-qr/pull/1))

## 0.0.1 - 2026-10-12

### Added

* [tools/dist/make-dist-zip.mjs](./tools/dist/make-dist-zip.mjs): `npm run dist:zip` / `dist` 用ステージングと `release/{slug}-{version}.zip` 生成 (`release-meta.json` 付き)
* GitHub Actions: [dist-zip.yml](./.github/workflows/dist-zip.yml) (tag `v*` / 手動、`npm run dist` + artifact)、[phpunit.yml](./.github/workflows/phpunit.yml) (main / develop / PR)

### Changed

* **docs/** 正本: S2J Webinar 参照を `docs_mod/v0.0.2` から Webinar **`docs/`** (運用素材) に同期 (README、`specs` / `governance` / `overview` 等)
* [admin_ui_spec.md](./docs/admin_ui_spec.md): Webinar REST 表を **確定契約** に更新 (定義 GET、未連携時 **400** `tracking_webinar_id_empty`)
* [status.md](./docs/status.md): 定義 / 取り込み REST **仕様確定**、Webinar プラグイン **実装待ち**、親 repo リンクと完了条件を更新

### Notes

* コード (PHP/TS) の **docs 正本追随** は未着手のまま ([docs/status.md](./docs/status.md))
* `dist:zip` はルート `readme.txt` (または `README.txt`) 必須。PHPUnit CI は `npm run phpunit:wp-env` 等の **テスト基盤追加前** は未成立

## 0.0.1 - 2026-10-11

### Added

* 起草仕様 `docs_mod/` (本プラグイン初版 v0.0.1: overview, plugin_spec, persistence, admin UI, data dictionary, status)。連携は S2J Webinar 仕様 v0.0.2
* npm 開発基盤 (Vite / TypeScript / ESLint / Stylelint、`build:*` / `dist:*` / `lint:docs`)
* GitHub Actions による Markdown ドキュメント lint (`.github/workflows/docs-lint.yml`)
* プラグイン骨格: ブートストラップ、`includes/` (メタ登録、REST、ドメイン)、Gutenberg Fill、`src/gutenberg/` 4ステップ UI シェル
* [docs_mod/rest_api_spec.md](./docs_mod/rest_api_spec.md) (本プラグイン REST。Webinar `tracking-sources` との分離)
* [docs/](./docs/) 確定仕様の正本 (v0.0.1、`docs_mod/` から写経)
* `composer.json` (PHP 8.1+、Service 非依存)

### Fixed

* Gutenberg: `registerPlugin` から `icon: null` を削除し、`IconType` との TypeScript 不一致を解消
* 開発依存: `@wordpress/plugins` / `@wordpress/editor` を追加 (他 `@wordpress/*` と版をそろえ)

### Changed

* 確定仕様 v0.0.1を **docs/** に正本反映 (`docs_mod/` は改訂案用に存置)
* README: プロジェクト概要、**docs/** 正本および S2J Webinar 仕様 v0.0.2へのリンク
* 仕様: `docs_mod/` をベスト・プラクティス案で統一 (REST 正本、Webinar ソース名、Save draft / Apply、取り込み→ draft、規範デフォルト JSON 等)
* 仕様: レビュー項目 A 〜 H 反映 (`tracking-source-definitions`、GET/UI 分担、503、handoff 同一モーダル等)
* 仕様: I 〜 N 反映 (保存 **厳格**、`catalogReady`、定義失敗時 UI disabled、用語統一)
* 仕様: O 〜 T 反映 (Save draft と `catalogReady`、定義 REST + `_s2j_webinar_id` 4xx、閲覧モード readOnly、`sources[]` 形)
* 仕様: U 〜 Y および用語・抜け (REST `draft`/`apply` と `catalogReady`、**4xx**→**503** 正規化、Next/URL 注記、handoff TBD 手順、Webinar 合意後 doc 更新、用語表 `catalogReady`)
* 仕様: レビュー項目 Z (UI 表記アンカー v001、localize キー正本、503/4xx 明確化、REST エラー例、PHP/TS 責務、楽観ロックなし、import → draft 失敗 UI)
* 仕様: レビュー項目 AA (PHP/TS 取り込み境界、4xx code 正本、apply Body、dirty、UI/サーバー二重 guard、data_dictionary / README / status 追随)
* 仕様: レビュー項目 AI (Save draft 後 dirty 基準、Apply 空 URL code、4xx ・501リンクと `qr_not_implemented`、用語表)
* 仕様: レビュー項目 AR (REST `code` 見出し、Import のパネル `provider`、`invalid_document` JSON 例)
* 仕様: レビュー項目 AV (overview / data_dictionary / 用語表と Import、解決済み `provider` の整合)
* 仕様: 製品表記 **S2J ウェビナー・ソース追跡 QR**、UI 英語 + i18n 規約

### Notes

* 反映 (`apply`) の QR PNG 生成、プレビュー、Webinar handoff サマリーは未実装
* 実装骨格 (固定 channel id、`catalogReady`、定義 REST 行マージ等) は **[docs/status.md](./docs/status.md)** 記載どおり **docs 正本未追随**
