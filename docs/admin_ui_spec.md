<!--
目的：「メンテナンス UI (4ステップ)」の明文化
-->

# S2J ウェビナー・ソース追跡 QR - 管理 UI 仕様

## 設計意図 (ゴール)

運営者がイベント編集から、**Webinar 定義ソースごと** の registration URL と QR PNG を **1つのダイアログ** で完結できるようにします (Survey の設問メンテナンスと同型)。

## UI 表記 (コード)

* 文字列は **英語正本** + `s2j-webinar-source-qr` ドメイン ([overview.md#ui-表記規約-初版-v001](./overview.md#ui-表記規約-初版-v001))。
* ボタン: **`Save draft`** (secondary)、**`Apply`** (primary、ステップ4のみ)。
* 状態表示: 単独 `Status` 禁止。例: `Document status: Draft` (ソース追跡 QR 文書の `status`)。

## 置き場

| 項目 | 内容 |
| --- | --- |
| ホスト | GatherPress **イベント編集** (ブロックエディター) |
| Fill slot | `EventPluginDocumentSettings` ([GatherPress イベントサイドバー](https://github.com/stein2nd/gatherpress)) — [plugin_spec.md](./plugin_spec.md) |
| 入口 | **常設:** 本プラグイン Fill 内ボタン (例: `Edit source tracking / QR`)。**任意:** S2J Webinar handoff から **同一ダイアログ** を開く ([plugin_spec.md#webinar-handoff-同一モーダル](./plugin_spec.md)) |
| Post type 判定 | `post_type_supports( …, 'gatherpress-event-date' )` — [rest_api_spec.md](./rest_api_spec.md) と同一 |
| 権限 | 対象イベントの `edit_post` |
| Webinar 未設定 / 無効 | 取り込み非表示。**既存メタは閲覧可**。保存は [保存可否 (初版・厳格)](#保存可否-初版厳格) |

## 保存可否 (初版・厳格)

`catalogReady` = S2J Webinar **有効** かつ **ソース定義一覧 GET 成功** かつ `sources.length >= 1`。

| 条件 | ダイアログ表示 | Save draft / Apply | Import |
| --- | --- | --- | --- |
| `catalogReady` | 定義どおり行 + メタマージ | **有効** | 他条件も満たせば有効 |
| Webinar **無効** | 既存メタ `channels` のみ (**閲覧**) | **disabled** | disabled |
| Webinar 有効、定義 REST **失敗** | メタ行があれば表示 + Notice | **disabled** | disabled |
| 定義成功だが `sources: []` | 行なし | **disabled** | disabled |

* **503フォールバック:** クライアント disabled でも POST された場合はサーバー **503**・前値維持 ([persistence_spec.md](./persistence_spec.md))。
* Notice (英語 id 例): `Saving requires S2J Webinar and a source catalog.`
* **`!catalogReady` (閲覧モード):** 入力・スタイル操作は **readOnly**。Save draft / Apply / Import は **disabled**。Next / Back は **閲覧用ステップ移動のみ** 可 (編集不可)。

### S2J Webinar 有効 (クライアント)

初版は PHP (`GutenbergMount`) が **`s2jWebinarActive`** (bool) を localize する。判定は Survey メンテナンス UI と同型の **`is_plugin_active()` 相当** を正本とする。

* **`false`:** ソース定義一覧 GET を **呼ばない** (または即失敗同扱い)。`catalogReady` = false。
* **`true`:** 定義一覧 GET を実行。成功かつ `sources.length >= 1` のとき `catalogReady` = true。
* **導出:** `src/gutenberg/` に **`catalogReady` 導出を1ヵ所** に集約 (上記 + `s2jWebinarActive` + 定義 GET 結果)。サーバー guard とは別評価 ([persistence_spec.md#保存可否-初版厳格](./persistence_spec.md#保存可否-初版厳格))。

## Webinar REST (本 UI 契約)

S2J Webinar 名前空間 `s2j-webinar/v1`。パス の **最終正本** は Webinar [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/source_qr_handoff_spec.md) (本表は初版の **期待契約** )。**外部ブロッカー** (定義 REST / handoff 確定待ち): [status.md#webinar-側待ち](./status.md#webinar-側待ち)。合意後は status の doc メンテナンス手順に従い **確定** 表記に更新。

| 用途 | Method / パス (期待) | 200ボディ (最小) |
| --- | --- | --- |
| **ソース定義一覧** (行の正本) | `GET /events/{post_id}/tracking-source-definitions` | `{ "sources": [ { "source_name": "…", "label": "…" } ] }` |
| **registration URL 取り込み** | `GET /events/{post_id}/tracking-sources` | `{ "items": [ { "source_name", "registration_url", "external_id"? } ] }` — [rest_api_spec.md](./rest_api_spec.md) |

* 認証: 本 UI と同様 Cookie + `X-WP-Nonce` (`wp_rest`)。
* **定義一覧の前提 (Webinar 正本):** イベント `_s2j_webinar_id` が非空であること。未連携時は Webinar 側 **4xx** — [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/source_qr_handoff_spec.md) に明記。本 UI では **4xx も `catalogReady` false**。Import 条件の `_s2j_webinar_id` と暗黙整合。
* 定義一覧 **失敗:** Notice。メタ `channels` があれば **閲覧用** に表示。**Save draft / Apply / Import は disabled** ([保存可否 (初版・厳格)](#保存可否-初版厳格))。
* **`sources: []`:** 行なし。Import 不可。保存ボタン disabled。
* 取り込み **失敗:** Notice、メタは前回 `draft` のまま (自動 draft POST しない)。
* **localize キー (命名正本):** `GutenbergMount` 等から渡す。
  * `s2jWebinarActive` (bool) — [S2J Webinar 有効 (クライアント)](#s2j-webinar-有効-クライアント)
  * `webinarDefinitionsPathTemplate` — 定義 GET (`…/tracking-source-definitions`)
  * `webinarTrackingPathTemplate` — 取り込み GET (`…/tracking-sources`)
  * 骨格: 取り込みテンプレのみ一部実装。定義テンプレ / `s2jWebinarActive` は [status.md](./status.md) 追随待ち。
  * 型: `src/types/global.d.ts` (bootstrap 型) のプロパティ名も **上記キーと同一** にする。

## チャネル行 (ソース名と一行対応)

* **正本:** 上記 **ソース定義一覧** の `source_name` → 行 `id`、`label` → 行 `label`。
* ダイアログ open 手順:
  1. `GET …/document` ([rest_api_spec.md](./rest_api_spec.md))
  2. ソース定義一覧 GET
  3. [data_dictionary.md#チャネル行の組み立て-初版](./data_dictionary.md#チャネル行の組み立て-初版) でマージ
* **手入力:** 各行 **Registration URL** (ポータルからコピー & ペースト)。**行がある場合のみ**。
* 初版: 行の **追加・削除 UI なし** (Webinar 側でソース定義)。

## 4ステップフロー

### 1. ソースと registration URL

* **取り込み (任意):** 次を **すべて** 満たすとき **`Import tracking URLs`** を表示。
  * S2J Webinar が有効
  * イベント `_s2j_webinar_id` が非空
  * パネル `provider` が `zoom` (open 後 `GET …/document` の **解決済み** 値 — [data_dictionary.md#provider-解決-正本](./data_dictionary.md#provider-解決-正本)。`_s2j_webinar_provider` 未設定時は `zoom`)
  * パネル `channels` が **ソース定義一覧で構築済み** (0行のとき Import **無効** )
* 取り込み: `GET …/tracking-sources` → マージ ([persistence_spec.md](./persistence_spec.md)) → **自動 `POST …/document/draft`** → 成功時 **未保存フラグクリア**。
* **取り込みマージ後の draft POST 失敗 (4xx / 503):** メタは **前値**のまま。パネルはマージ結果を **保持**。**未保存あり** (dirty)。Notice 表示。
* **未登録 `source_name`:** Warning Notice に一覧。行追加・手動割り当て UI **なし** (初版)。
* **接続先でリンク新規作成はしない** (初版 `zoom`: ポータル運用)。

**ステップ間 (Next):** URL 空は許可。URL 非空かつ形式不正は **Notice 警告**、Next は可。厳密検証は **Save draft** / **Apply** (サーバー)。**`catalogReady` 時のみ** Registration URL が編集可能のため、形式警告は **編集モード** で意味を持つ (`!catalogReady` は readOnly — [保存可否 (初版・厳格)](#保存可否-初版厳格))。

### 2. QR 見た目 (全ソース共通)

| 項目 | UI (英語ラベル例) |
| --- | --- |
| 中央アイコン | Media library (任意)。正方形推奨 |
| モジュール色 | Color / hex。デフォルト `#000000` |
| 出力サイズ | px。**256〜2048**、デフォルト **512** |

### 3. QR プレビュー

* ソース行ごとに QR **一覧** (Canvas / data URL)。サーバー保存不要。
* URL 空の行はプレースホルダまたはスキップ。

### 4. Apply (反映)

* Primary: **`Apply`** (日本語仕訳: 反映)。
* **成功:** メタ `ready`、PNG メディア、**ダイアログを閉じる**、Webinar サマリー更新 (handoff 実装時)。
* **失敗 (4xx / 5xx / 501):** メタ前値維持、**ダイアログは開いたまま**、Notice。501は PNG パイプライン未実装メッセージ。

**Save draft:** **`catalogReady` 時のみ** 全ステップで利用可。**配置:** 全ステップ共通フッターの secondary (S2J Webinar Survey メンテナンスモーダルと **同配置** )。**Apply** はステップ4のみ primary。**未保存 (dirty):** パネル文書が open 時マージ結果 (または取り込み + draft 成功応答) から **URL / スタイル等が変化** したら dirty (Survey メンテナンスと **同ロジック** )。**Save draft 成功 (200) 後**は返却メタ文書を dirty **基準**に更新 (取り込み自動 draft 成功と同型)。ダイアログを閉じる際、dirty なら確認ダイアログ (推奨)。取り込み後の自動 draft 成功後は **未保存なし** とみなす。

## QR 実装メモ

| 用途 | 層 |
| --- | --- |
| プレビュー | JS (npm ライブラリ)。管理画面バンドルのみ |
| 保存 PNG | PHP (GD 等)。`includes/` |

Composer 純関数には QR バイトを載せない ([S2J Webinar 仕様 v0.0.2 / Composer メモ](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/status.md))。

## 非目標 (UI)

* 本 UI からの **ソース名** 追加 / 削除
* 未登録取り込み item の手動チャネル割り当て
* 公開フロントへの QR 埋め込み (別イニシアチブ)
* 中間保存の自動 debounce

## サーバー API (本プラグイン)

| UI 操作 | REST ([rest_api_spec.md](./rest_api_spec.md)) | 前提 |
| --- | --- | --- |
| ダイアログ open | `GET …/document` → Webinar ソース定義一覧 → クライアントマージ | 定義 GET 失敗時は閲覧モード ([保存可否 (初版・厳格)](#保存可否-初版厳格)) |
| Save draft | `POST …/document/draft` | **`catalogReady`** |
| Apply | `POST …/document/apply` | **`catalogReady`** (ステップ4UI) |
| 取り込み | Webinar `GET …/tracking-sources` → マージ → **`POST …/document/draft`** | Import 表示条件 + **`catalogReady`** |

## 関連

* [persistence_spec.md](./persistence_spec.md)
* [data_dictionary.md](./data_dictionary.md)
* [rest_api_spec.md](./rest_api_spec.md)
