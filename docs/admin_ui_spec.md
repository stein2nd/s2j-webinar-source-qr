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
| Document Settings | `PluginDocumentSettingPanel` (S2J Webinar 同型) — パネル見出し **`S2J Webinar Source QR`** — [plugin_spec.md](./plugin_spec.md) |
| 入口 | **常設:** 上記パネル内 **Open…** → 4 ステップモーダル (見出しも **`S2J Webinar Source QR`**)。**任意:** S2J Webinar handoff から **同一モーダル** を開く ([plugin_spec.md#webinar-handoff-同一モーダル](./plugin_spec.md)) |
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
* **`!catalogReady` (閲覧モード):** **Save draft / Apply / Import は disabled**。Next / Back は **閲覧用ステップ移動のみ** 可。
  * **ステップ1** — Registration URL 等は **readOnly** (編集不可)。
  * **ステップ2** — 下記 [§2.1〜2.3](#2-qr-見た目-全ソース共通) の **`!catalogReady` 行** どおり。**メタ永続化は不可** (Save draft disabled)。カタログ不可の説明は `@wordpress/components` `Notice` ではなく **コンパクト 1 行帯** ([§2 レイアウト](#2-qr-見た目-全ソース共通))。

### S2J Webinar 有効 (クライアント)

初版は PHP (`GutenbergMount`) が **`s2jWebinarActive`** (bool) を localize する。判定は Survey メンテナンス UI と同型の **`is_plugin_active()` 相当** を正本とする。

* **`false`:** ソース定義一覧 GET を **呼ばない** (または即失敗同扱い)。`catalogReady` = false。
* **`true`:** 定義一覧 GET を実行。成功かつ `sources.length >= 1` の場合 `catalogReady` = true。
* **導出:** `src/gutenberg/` に **`catalogReady` 導出を1ヵ所** に集約 (上記 + `s2jWebinarActive` + 定義 GET 結果)。サーバー guard とは別評価 ([persistence_spec.md#保存可否-初版厳格](./persistence_spec.md#保存可否-初版厳格))。

## Webinar REST (本 UI 契約)

S2J Webinar 名前空間 `s2j-webinar/v1`。パス の **正本** は Webinar [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md) および [source_qr_tracking_definitions_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_tracking_definitions_rest_spec.md) / [source_qr_tracking_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_tracking_rest_spec.md) (本表は **確定契約** の要約)。

| 用途 | Method / パス | 200ボディ (最小) |
| --- | --- | --- |
| **ソース定義一覧** (行の正本) | `GET /events/{post_id}/tracking-source-definitions` | `{ "sources": [ { "source_name": "…", "label": "…" } ] }` |
| **registration URL 取り込み** | `GET /events/{post_id}/tracking-sources` | `{ "items": [ { "source_name", "registration_url", "external_id"? } ] }` — [rest_api_spec.md](./rest_api_spec.md) |

* 認証: 本 UI と同様 Cookie + `X-WP-Nonce` (`wp_rest`)。
* **定義一覧の前提 (Webinar 正本):** イベント `_s2j_webinar_id` が非空であること。未連携時は Webinar 側 **400** `tracking_webinar_id_empty` — [source_qr_tracking_definitions_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_tracking_definitions_rest_spec.md)。本 UI では **4xx も `catalogReady` false**。Import 条件の `_s2j_webinar_id` と暗黙整合。
* 定義一覧 **失敗:** Notice。メタ `channels` があれば **閲覧用** に表示。**Save draft / Apply / Import は disabled** ([保存可否 (初版・厳格)](#保存可否-初版厳格))。
* **`sources: []`:** 行なし。Import 不可。保存ボタン disabled。
* 取り込み **失敗:** Notice、メタは前回 `draft` のまま (自動 draft POST しない)。
* **localize キー (命名正本):** `GutenbergMount` 等から渡す。
  * `s2jWebinarActive` (bool) — [S2J Webinar 有効 (クライアント)](#s2j-webinar-有効-クライアント)
  * `webinarDefinitionsPathTemplate` — 定義 GET (`…/tracking-source-definitions`)
  * `webinarTrackingPathTemplate` — 取り込み GET (`…/tracking-sources`)
  * 実装: 上記3キーを `GutenbergMount` から localize ([status.md](./status.md))。
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

* **取り込み (任意):** 次を **すべて** 満たす場合 **`Import from provider`** ボタンを表示 (英語正本 — 下記「Import tracking URLs」と同義の初版ラベル)。
  * S2J Webinar が有効
  * イベント `_s2j_webinar_id` が非空
  * パネル `provider` が `zoom` (open 後 `GET …/document` の **解決済み** 値 — [data_dictionary.md#provider-解決-正本](./data_dictionary.md#provider-解決-正本)。`_s2j_webinar_provider` 未設定時は `zoom`)
  * パネル `channels` が **ソース定義一覧で構築済み** (0行の場合 Import **無効** )
* 取り込み: `GET …/tracking-sources` → マージ ([persistence_spec.md](./persistence_spec.md)) → **自動 `POST …/document/draft`** → 成功時 **未保存フラグクリア** + `Import finished (%d rows matched).` Notice。
* **取り込みマージ後の draft POST 失敗 (4xx / 503):** メタは **前値**のまま。**目標:** パネルはマージ結果を **保持**・**dirty**・Notice。**初版実装:** `saveDraft` 失敗時は **import 前の `document` state のまま** (マージ結果は state に載せない)。Notice は REST `message` / `Import failed.`。
* **未登録 `source_name`:** Warning Notice に一覧。行追加・手動割り当て UI **なし** (初版)。
* **接続先でリンク新規作成はしない** (初版 `zoom`: ポータル運用)。

**ステップ間 (Next):** URL **空** は許可。Next / Back は **常に可** (保存中 `saving` 時のみ disabled)。

* **クライアント URL 形式チェック (仕様上の推奨):** 非空かつ形式不正のとき **Notice 警告** を出しつつ Next 可 — **初版実装 (`MaintenanceModal`) では未実装**。不正 URL は **Save draft** / **Apply** / Step3 **preview** のサーバー **4xx** で検知。
* **`catalogReady` 時のみ** Registration URL が編集可能 (`TextControl` `disabled={!catalogReady}`)。`!catalogReady` は readOnly — [保存可否 (初版・厳格)](#保存可否-初版厳格)。
* **行ラベル:** 定義 `label` 非空なら `label`、空なら `id` (`channelDisplayLabel`)。
* **help (英語):** `Paste the registration URL from the provider portal.`
* **Import 非表示時:** 条件未充足の説明段落 (英語) — `Import is available when the source catalog is ready, S2J Webinar is active, the event has a webinar ID, and provider is zoom.`
* **未登録 `source_name`:** Warning `Notice` — `Some imported source names did not match the current catalog.` (`unassigned` 一覧は初版 **非表示**、メッセージのみ)。

### 2. QR 見た目 (全ソース共通)

**レイアウト (上→下):** (1) モーダル共通ヘッダ — **Step N of 4** と **Document status: Draft|Ready** を **1 行** (`steps-primary`)。(2) `catalogReady` false 時 — 文言 **Saving requires S2J Webinar and a source catalog.** の **コンパクト警告 1 行** (`catalog-notice` 帯。`Notice` コンポーネントより低い — PC/タブレットで Step2 **縦スクロールを出さない** 目標)。(3) Step2 本体 — **1 行 flex** (`step2-style-row`、狭い幅で折り返し):
  * 左ブロック: **Module color** ラベル + パレット | **Hex** ラベル + 入力 (`color-hex-row`)
  * **Center icon** 列 (ラベル + サムネ + **Select image** / **Replace image** / **Remove**)
  * **Output size (256–2048 px)** 列 (数値入力)
  (4) `catalogReady` 時のみ — モジュール色確定の **1 行ヒント** (英語: release pointer / leave Hex field)。(5) フッター — **Back** / **Next** / **Save draft** を **横並び** (`flex` + `gap`)。

**実装参照:** [`src/gutenberg/index.tsx`](../../src/gutenberg/index.tsx) (`ModuleColorControl` / `CenterIconControl` / `OutputSizeControl`)、[`src/styles/gutenberg.scss`](../../src/styles/gutenberg.scss)。Gutenberg 脚本依存: `wp-block-editor` (`MediaUpload`)、`@wordpress/edit-post` / `@wordpress/editor` の **`PluginDocumentSettingPanel`**。[`GutenbergMount.php`](../includes/Editor/GutenbergMount.php) — 脚本・CSS バージョンに **`filemtime` サフィックス** (開発中のキャッシュバスト)。

**Gutenberg ビルド:** Vite バナーで専用 **`S2JWebinarSourceQrJSXRuntime`** (`jsx`/`jsxs` が React `key` を props にマージ)。S2J Webinar 共有 `S2JReactJSXRuntime` との共存時も `TextControl` 等が void 要素の子を持たないよう分離 ([`vite.config.ts`](../vite.config.ts)、CHANGELOG unreleased)。

| 項目 | UI (英語ラベル例) | メタ (`style`) |
| --- | --- | --- |
| モジュール色 | **Module color** — `@wordpress/components` **ColorPicker** + **Hex** | `foreground_hex` (`#RRGGBB`) |
| 中央アイコン | **Center icon** — メディアライブラリ (任意)。正方形推奨 | `icon_attachment_id` (0 = なし) |
| 出力サイズ | **Output size** — px | `size_px` (**256〜2048**、デフォルト **512**) |

#### 2.1 モジュール色 (Module color)

* **永続化:** Web / GD / QR 用に **`#RRGGBB` (sRGB hex)** のみ ([data_dictionary.md](./data_dictionary.md)、サーバー正規化と同一)。Illustrator 型の CMYK / HSB モード切替は **初版非目標** (WP `ColorPicker` の Hex / RGB / HSL 相当で足りる方針)。
* **コントロール:** [`ModuleColorControl`](../../src/gutenberg/index.tsx) — パレット (`ColorPicker`) + 単一 **Hex** 欄。WP 付属の重複入力行 (RGB/HSL/Hex 切替ブロック) は CSS で非表示し、Hex 欄を正本表示とする。
* **パレット → Hex:** ドラッグ中は **`liveHex`** を Hex 欄にリアルタイム表示。**document へ確定** するのは **`catalogReady` 時** に (1) パレット領域で **pointer up**、(2) Hex 欄 **blur** (有効な hex のみ)。
* **Hex → パレット:** Hex 欄は **`catalogReady` に関わらず入力可** (プレビュー)。入力は `#` + 16進 **最大6桁** にサニタイズ。完全な `#RRGGBB` になった時点でパレット上の ◯ を同期 (`color` prop + 必要時 `ColorPicker` 再マウント)。blur で不正なら **open 時の確定 hex** に戻す。
* **`!catalogReady`:** パレット・Hex は **表示上プレビュー** (`liveHex`)。**`onCommit` は呼ばない** — `document.style.foreground_hex` は **更新しない** (Step 離脱で open 時値に戻る)。**dirty・Save draft** なし。

#### 2.2 中央アイコン (Center icon)

* **目的:** Apply 時 PNG の QR 中央に合成する **既存メディア添付** を選ぶ ([persistence_spec.md](./persistence_spec.md)、[`includes/Domain/qr.php`](../includes/Domain/qr.php))。
* **UI:** `@wordpress/block-editor` の **`MediaUpload`** + **`MediaUploadCheck`** (Survey / コアブロックと同型)。`allowedTypes`: `image` (JPEG / PNG / GIF / WebP — Apply 時サーバー MIME 検証と整合)。
* **操作 (英語ラベル例):**
  * 未選択: **Select image** (primary 相当) — ライブラリから **既存を選択** または **新規アップロード** (いずれもメディアライブラリに attachment 作成 / 参照)。
  * 選択済み: サムネ + **Replace image** / **Remove** (Remove → `icon_attachment_id` = **0**)。
* **反映:** 選択・Remove でパネル上の `document.style.icon_attachment_id` を更新。**`catalogReady` 時** は **dirty** とし Save draft で REST 永続化。
* **`!catalogReady`:** **Select / Replace / Remove は可能** (Module color と同型の **プレビュー** )。**dirty・Save draft** は不可。メディアモーダルでの **アップロード / 既存選択** は通常どおり (attachment はライブラリに残るが、イベントメタには載らない)。
* **Apply 失敗:** 存在しない ID・非画像等 → **400** `qr_generation_failed` (Integration 済)。UI は初版 **事前検証なし** (Save draft は ID を保存可、Apply で検知)。

#### 2.3 出力サイズ (Output size)

* **永続化:** `style.size_px` — 正方形 QR の辺 px。**256〜2048**、デフォルト **512** (サーバー clamp と同一 — [persistence_spec.md](./persistence_spec.md))。
* **UI:** ラベル **Output size (256–2048 px)** — 数値入力 (`type=number`、min/max 属性)。入力は **整数のみ** (非数字除去)。**blur** で clamp し、値が変わったときのみ **`catalogReady` 時** `document.style.size_px` を更新・**dirty**。
* **`!catalogReady`:** 入力 **disabled** (open 時メタ値を表示のみ)。

### 3. QR プレビュー

**方針 (確定):** **プレビュー REST** — Apply と **同一 PHP レンダラー** (`s2j_wsq_render_qr_png_bytes()` — [`includes/Domain/qr.php`](../includes/Domain/qr.php))。クライアント npm QR ライブラリは **使わない** ([QR 実装メモ](#qr-実装メモ))。API: [rest_api_spec.md#post-event_post_iddocumentpreview](./rest_api_spec.md#post-event_post_iddocumentpreview)。UI: [`QrPreviewStep`](../../src/gutenberg/index.tsx) — 行ごと `POST …/preview`、**400ms debounce**、200 `data_url` を `<img>` 表示。

* ソース行ごとに QR **一覧** (`<img>` / data URL 等)。**メタ・メディアは更新しない**。
* リクエスト: 行の `registration_url` (非空) + パネル `style` (Step2 確定値または dirty 中の `document`)。
* URL 空行: **preview REST を呼ばない**。プレースホルダ (英語): `Add a registration URL on step 1 to preview this QR.`
* 行ラベル: Step1 と同じ `channelDisplayLabel`。
* **intro (英語、実装済み):** `Previews use the same rendering as Apply. Scan with your phone to verify.`
* プレビュー **4xx/5xx:** 行ごと Warning `Notice` + REST `message` (fallback: `Preview failed.`)。
* **0 行:** `No source rows. Configure S2J Webinar source definitions for this event.`

### 4. Apply (反映)

* 本文 (英語): `Apply saves ready status and PNG media when the server pipeline is complete.`
* Primary: **`Apply`** (日本語仕訳: 反映)。**ステップ4のみ** フッターに表示。
* **ボタン disabled:** `!catalogReady` または `saving`。**dirty 不要** (未変更でも Apply 可 — サーバーは現在の `document` を POST)。
* **成功 (200):** サーバーは `ready` + PNG メディア。**初版 UI:** **`Applied.`** を info `Notice`、**モーダルは閉じない**、**`GET …/document` 再取得なし** (ヘッダ `Document status` は open 時のまま — [status.md](./status.md) Apply 成功 UI 未完了)。**目標 (将来):** 応答メタで state 更新 → `Document status: Ready`、モーダル close、Webinar サマリー (handoff 実装時)。
* **失敗 (4xx / 503):** メタ前値維持、**モーダル開いたまま**、REST `message` を info `Notice` (例: `One or more registration URLs are invalid.` — code `invalid_registration_url` **400**)。PNG / icon 失敗は **400** `qr_generation_failed`。**503** `catalog_unavailable` (カタログ guard)。
* **501 `qr_not_implemented`:** **現行 PHP では返さない** (PNG パイプライン実装済み)。履歴 code — [persistence_spec.md](./persistence_spec.md#検証エラー-code-初版)。

**フッター (全ステップ):** **Back** / **Next** / **Save draft** (secondary) + Step4 の **Apply** (primary)。`Back`/`Next` は step 端または `saving` で disabled。

**Save draft:** **`catalogReady` 時のみ**。**disabled:** `!catalogReady` / `saving` / **`!dirty`** (変更なしでは押せない)。**配置:** 全ステップ共通フッター secondary (Survey メンテナンスモーダルと **同配置** )。**未保存 (dirty):** open 時マージ結果 (または取り込み + draft 成功応答) から **URL / スタイル等が変化** したら dirty。**Save draft 成功 (200) 後**は返却メタで state 更新し dirty クリア + `Draft saved.` Notice。取り込み自動 draft 成功後は **未保存なし**。**ダイアログ close 時 dirty 確認** — 初版 **未実装** (推奨のまま)。

**Notice (初版):** 成功・失敗とも **`@wordpress/components` `Notice` `status="info"`** (REST エラーも同一)。`code` 別スタイル分岐は **未実装** ([status.md](./status.md))。

## QR 実装メモ

| 用途 | 層 |
| --- | --- |
| **Step3 プレビュー** | **REST** → PHP `s2j_wsq_render_qr_png_bytes()` (Apply と同一)。Gutenberg は **取得・表示のみ** |
| **Apply / メディア PNG** | PHP (`chillerlan/php-qrcode` + GD 中央 icon)。`includes/` |

Composer 純関数には QR バイト生成ロジックを **PHP 側に集約** (プレビュー REST も同関数を呼ぶ) ([S2J Webinar 運用素材索引](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md))。

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
| Step3 プレビュー | `POST …/document/preview` | `edit_post`。メタ非更新。→ [rest_api_spec.md](./rest_api_spec.md) |
| 取り込み | Webinar `GET …/tracking-sources` → マージ → **`POST …/document/draft`** | Import 表示条件 + **`catalogReady`** |

## 関連

* [persistence_spec.md](./persistence_spec.md)
* [data_dictionary.md](./data_dictionary.md)
* [rest_api_spec.md](./rest_api_spec.md)
