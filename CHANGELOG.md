# S2J ウェビナー・ソース追跡 QR - CHANGELOG

## unreleased

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
