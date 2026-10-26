# S2J Webinar Source QR - 概要

## はじめに

本プラグインは、GatherPress のイベント編集画面から、接続先ウェビナーの **ソース追跡 (登録) URL** を **S2J Webinar で定義したソース名ごと** に扱い、**QR コード PNG** を生成・保存する WordPress プラグインです。

接続先への OAuth、Webinar 本体の同期、**ソース名の登録** は [S2J Webinar](https://github.com/stein2nd/s2j-webinar) です。UX の参照モデルは [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey) です (別 Fill、別メタ、呼び出し元は handoff のみ)。

## 基本情報

| 項目 | 値 |
| --- | --- |
| 名称 (仕様書) | **S2J ウェビナー・ソース追跡 QR** |
| リポジトリ / スラッグ | `s2j-webinar-source-qr` (GitHub 等の英語 slug。「S2J Webinar Source QR」等の略称は **非公式**) |
| テキストドメイン | `s2j-webinar-source-qr` |
| ライセンス | GPL-3.0-or-later (リポジトリ LICENSE に同じ) |
| 仕様・製品版 (初版) | **v0.0.1** ([版の取り扱い](./specs.md#版の取り扱い)) |
| 実行環境 (PHP) | **v8.3 以上** ([WordPress 必須要件](https://ja.wordpress.org/about/requirements/)) |

## UI 表記規約 (初版 v001)

製品版 **v0.0.1** と同義 ([版の取り扱い](./specs.md#版の取り扱い))。

| 層 | 規約 |
| --- | --- |
| **管理 UI (コード)** | 文字列は **英語を正本** とし、`__()` / `_x()` / `sprintf()` 等で表示。テキストドメイン `s2j-webinar-source-qr` |
| **製品名 (英語 id 例)** | Document Settings / モーダル見出し: **`S2J Webinar Source QR`** (Plugin Name 同型)。パネル内ボタン: **`Open…`** |
| **主要ボタン (英語 id)** | 中間保存: **`Save draft`**。確定: **`Apply`** (単独 `Save` は使わない) |
| **仕様書 (docs)** | 日本語。製品名は **S2J ウェビナー・ソース追跡 QR** |
| **データ・ REST** | glossary 準拠 (`registration_url` 等)。UI ラベルとキー名を混同しない |

## 提供機能 (初版 v0.0.1)

* イベント編集から開く **4ステップ** メンテナンス UI (同一ダイアログ)
* **Webinar ソース定義にもとづく行** への **手入力 registration URL** (**`catalogReady` 時**。取り込み以外の URL 入力はプロバイダ共通) と、条件を満たす場合 **取り込み** (初版: 解決済みパネル `provider` が `zoom` のみ。HTTP は Webinar 経由 — Import 条件の正本 [admin_ui_spec.md#1-ソースと-registration-url](./admin_ui_spec.md#1-ソースと-registration-url))
* QR プレビュー (クライアント) と PNG メディア保存 (サーバー)
* メタ `_s2j_webinar_source_qr` の保存 (`draft` / `ready`)

## 責務

* GatherPress イベント編集 Document Settings へのパネル追加 (S2J Webinar 同型、フォーク非改変)
* 文書メタの正規化と永続化 (**`ready` / PNG は `apply` のみ** — [persistence_spec.md](./persistence_spec.md))
* QR 符号化 (JS プレビュー、PHP 保存)
* s2j-webinar-service Adapter 結果の **受け取り** (取り込み REST は Webinar が提供)

## 非対応スコープ (Out of Scope)

* OAuth、token option、接続先 HTTP の直接呼び出し
* Webinar create / update / 同期 plan への QR 載せ
* CoverArt / フライヤー (S2J Webinar)
* **本 UI からのソース名 (チャネル id) の新規作成・削除** (正本は Webinar)
* 取り込みで Webinar **ソース定義一覧** に **無い** `source_name` をメタ行として **追加** (警告表示のみ。Webinar 側でソース定義後に再取り込み)
* **S2J Webinar 無効** または **ソース定義 REST 失敗** 時の **メタ保存** (初版は **厳格**: 閲覧のみ — [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))
* 接続先ポータルでのリンク **新規作成** の再現 (初版 `zoom`: REST 作成不可。[Webinar 仕様 v0.0.2 調査](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md))
* 独自 UTM の付与、URL のクエリー改変
* WordPress 内 analytics、配配メール連携
* 初版以外のプロバイダ取り込み UI
* 中間保存の **自動 debounce / autosave** (明示 **Save draft** のみ)
* **複数ユーザー同時編集** の楽観ロック (初版 **なし** — 最後の保存勝ち。Survey 同型)

## 関連

* [plugin_spec.md](./plugin_spec.md)
* [admin_ui_spec.md](./admin_ui_spec.md) (Webinar REST 契約)
* [S2J Webinar 仕様 v0.0.2 README](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md)
