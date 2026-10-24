# S2J ウェビナー・ソース追跡 QR - プラグイン仕様 (統合見取り図)

確定仕様の統合見取り図です (正本 **docs/** )。細部の正本は [specs.md](./specs.md) の一覧です。

## 実行環境

* **PHP v8.3 以上** — WordPress [必須要件](https://ja.wordpress.org/about/requirements/)の推奨ベースライン。プラグインヘッダー `Requires PHP: 8.3`、`composer.json` `require.php`、CI / wp-env / Studio Blueprint と同値。

## 位置付け

| 層 | 名称 | 役割 |
| --- | --- | --- |
| イベント UI | [stein2nd/gatherpress](https://github.com/stein2nd/gatherpress) | イベント編集画面 |
| 呼び出し元 (入口・サマリー) | **[S2J Webinar](https://github.com/stein2nd/s2j-webinar)** | パネル handoff。**ソース名正本**。定義 REST + 取り込み REST |
| **本プラグイン** | S2J ウェビナー・ソース追跡 QR | メンテナンス UI、`_s2j_webinar_source_qr`、QR PNG |
| 取り込み写像 | [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) | プロバイダ Adapter (初版 v0.0.1: `zoom`) |

## 目的 (初版 v0.0.1)

運営者が、1イベントについて、下記を終えることです。

1. **S2J Webinar で定義された各ソース名** について、接続先発行の **registration_url** を登録する (手入力、または `zoom` 取り込み)。
2. QR の見た目 (共通スタイル) を決め、プレビューで確認する。
3. **Apply** でメタ文書を **`ready`** にし、ソースごとの PNG をメディアに保存する。

## 非目標 (初版 v0.0.1)

* `s2j-webinar-source-qr-service` の追加
* 接続先へのリンク作成 API 呼び出し
* S2J Webinar の「同期」に QR を載せること
* 固定チャネル id (`web` / `sns` / `other` 等) のハードコード

## サービスとの境界

| 本プラグイン | s2j-webinar-service |
| --- | --- |
| パネル文書、保存時の URL / スタイル検証 | GET 応答 → `registration_url` 行への写像 (`zoom` Adapter) |
| QR バイト生成、メディア登録 | PNG / GD を持たない |
| 取り込みボタン → Webinar REST 呼び出し | HTTP を呼ばない (Webinar アダプタが呼ぶ) |

初版 (v0.0.1) の **`composer.json` に s2j-webinar-service は含めません** (取り込みは S2J Webinar REST の写像済み JSON のみ受け取る。方針根拠は [S2J Webinar 仕様 v0.0.2 / status.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md))。

## Webinar handoff (同一モーダル)

S2J Webinar Survey と同型:

* handoff は **別 UI を作らず**、本プラグインのメンテナンスモーダルを開く。
* 実装は Webinar 側の **カスタムイベント / `@wordpress/hooks` の `doAction`** 等、Survey handoff と **同じパターン** を写経 ([source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md))。
* 本プラグイン `src/gutenberg/` — **`MaintenanceModalHost`** が **open リスナ** を常時マウント。Document Settings **Open…** も handoff も **`requestOpenMaintenanceModal( postId )`** で同一 `MaintenanceModal`。
* **hook / イベント名:** **`s2j_webinar.open_source_qr`** (引数: `postId`)。Webinar が `doAction`、本プラグインが `addAction( hook, 's2j-webinar-source-qr/handoff', … )` / unmount で `removeAction( hook, namespace )` ([`WEBINAR_HANDOFF_OPEN_HOOK`](../includes/Constants.php)、[`MaintenanceModalHost`](../src/gutenberg/index.tsx))。正本 [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md)。
* **E5:** **Pass 2026-10-19** — Webinar **Edit source tracking / QR** → 同一 `MaintenanceModal` ([test-results.md](./test-results.md))。

## コード配置 (骨格)

| パス | 役割 |
| --- | --- |
| `s2j-webinar-source-qr.php` | ブートストラップ、定数 |
| `includes/Constants.php` | メタキー、REST namespace、**`WEBINAR_HANDOFF_OPEN_HOOK`** |
| `includes/Domain/document.php` | 文書の純関数 (**保存時の正規化** — `draft` / `apply` / `sanitize`。取り込みマージは TS) |
| `includes/Meta/` | `register_post_meta`、Guard、`save_post` 再正規化 ([`SavePost.php`](../includes/Meta/SavePost.php)) |
| `includes/RestController.php` | [rest_api_spec.md](./rest_api_spec.md) |
| `includes/Editor/GutenbergMount.php` | パネル脚本 enqueue (**`wp-hooks`**)、`s2jWebinarSourceQrData` localize |
| `src/gutenberg/` | 4ステップ UI + **handoff** (`MaintenanceModalHost`、英語 + i18n) |
| `src/domain/document.ts` | クライアント側文書型・取り込みマージ |

### クライアント / サーバー正規化

| 層 | 責務 |
| --- | --- |
| TS (`src/domain/document.ts`) | 定義 + `GET document` 行マージ、取り込みマージ (パネル) |
| PHP (`includes/Domain/document.php`) | 保存時正本 — `draft` / `apply` / `sanitize` |

詳細: [persistence_spec.md#クライアント--サーバー正規化-責務](./persistence_spec.md#クライアント--サーバー正規化-責務)。

## 設計方針

[kis-wordpress エコシステム仕様](https://github.com/stein2nd/kis-wordpress/blob/main/docs_mod/specs.md) および [wp-plugin-spec](https://github.com/stein2nd/wp-plugin-spec) に従います。

## 関連リポジトリ

| 名称 | 種別 | 役割 |
| --- | --- | --- |
| **本プラグイン** | WP プラグイン | QR UI、文書メタ |
| [s2j-webinar](https://github.com/stein2nd/s2j-webinar) | WP プラグイン | handoff、OAuth、ソース定義、tracking-sources REST |
| [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) | Composer | tracking 取り込み Adapter |
| [s2j-webinar-survey](https://github.com/stein2nd/s2j-webinar-survey) | WP プラグイン | コンパニオン UX の参照 |

## 実装順 (案)

実装は **docs/** 正本に追随する ([status.md](./status.md))。

1. Webinar ソース定義 REST + クライアント行マージ、`GET document` 連携
2. `draft` / `sanitize` 共有正規化、カタログ503、取り込み → 自動 draft
3. 保存パイプライン (`ready`、PNG、`qr_attachment_id` 無効化)
4. Webinar handoff (同一モーダル、サマリー)
