# S2J ウェビナー・ソース追跡 QR - 永続化と受け渡し

## 設計意図 (ゴール)

1イベント1ソース QR 文書を保護メタに保存し、`ready` の場合に S2J Webinar パネルがサマリー表示できるようにします。

本プラグインは、**OAuth / 接続先 HTTP を直接呼びません** (Survey が Zoom を呼ばないのと同型)。**接続先取り込み GET** も Webinar が提供します。

## 用語

| 用語 | 意味 |
| --- | --- |
| パネル文書 | メンテナンス UI の作業コピー |
| メタ文書 | `_s2j_webinar_source_qr` に保存された正規化済み文書 |

## 保存

| 項目 | 内容 |
| --- | --- |
| 単位 | 1イベントにつきソース QR 文書1つ |
| キー | `_s2j_webinar_source_qr` |
| 中身 | [data_dictionary.md](./data_dictionary.md) のメタ文書 |

### 保存経路

| 操作 | 経路 | 初版 v0.0.1 |
| --- | --- | --- |
| **`ready` + PNG** | `POST …/document/apply` | **ここだけ** |
| **`draft` 正規化** | `POST …/document/draft` | 正本 |
| **`draft` 正規化 (共有)** | `register_post_meta` の `sanitize_callback` + **`update_post_metadata` ガード** ([`includes/Meta/Guard.php`](../includes/Meta/Guard.php)) | 形状は `sanitize_callback` (`merge_document`)。**カタログ + `normalize_for_draft` と同等**の検証はガード (post ID 付き)。失敗時 **更新拒否・前値維持**。`status: ready` の直書きは **拒否** (`apply` + REST bypass のみ) |
| **再正規化** | `save_post` (イベント「更新」) | [`s2j_wsq_renormalize_meta_on_post_save()`](../includes/Domain/document.php) — [`SavePost.php`](../includes/Meta/SavePost.php) priority **20**。カタログ不可時は **スキップ** (メタ維持)。`ready` は全行 URL ありのとき維持 |
| **禁止 / 非推奨** | autosave、Quick Edit、一括編集、WP-CLI、ブロックエディターからのメタ REST 直 PATCH | 正本は **`POST …/document/draft`** / **`apply`**。ガード外経路は [`Guard.php`](../includes/Meta/Guard.php) で draft 同等ルールまたは拒否 |

`provider` 解決順の正本は [data_dictionary.md#provider-解決-正本](./data_dictionary.md#provider-解決-正本) です。

### 保存のトリガー (UI)

* **Apply (反映):** ステップ4成功時 — 全ソース行 URL 必須 → PNG → `status: ready` (**明示保存**)。
* **Save draft (下書き保存):** **`catalogReady` 時のみ** ステップ **1〜4** ([admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))。`status` は常に `draft`。初版に **自動 debounce 保存はなし**。
* **取り込み成功後:** クライアントマージのあと **ただちに `POST …/document/draft`**。成功したら UI の未保存フラグは **クリア** ([admin_ui_spec.md](./admin_ui_spec.md))。

### Webinar ソース定義一覧 (サーバー正規化)

`draft` / `apply` の `channels` 検証は、UI と **同一契約** の Webinar REST ([admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約)) からソース名・ラベルを取得して照合します。

* **初版の取得方式:** 編集ユーザー権限で `rest_do_request()` により `GET /s2j-webinar/v1/events/{post_id}/tracking-source-definitions` を実行 (Cookie / nonce コンテキスト内)。
* **取得失敗時** (Webinar 無効、イベント未連携による定義 REST **4xx**、その他 REST エラー、空 `sources[]` を **行なし** とみなす場合含む): `draft` / `apply` / `sanitize_callback` は **503** (例: `catalog_unavailable`)、**メタ前値維持** (Webinar **4xx** 等を **503に正規化** — [rest_api_spec.md#http-カタログ保存](./rest_api_spec.md#http-カタログ保存))。未連携時 **4xx** の正本は Webinar [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md)(本 UI は **4xx も `catalogReady` false**)。
* 正本 パス が Webinar 側で異なる場合は [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md) を優先し、**JSON 形** (`sources[]` の `source_name` / `label`) は維持。

### 保存可否 (初版・厳格)

UI ガードの正本は [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格) です。サーバーは常に次を満たす場合だけメタを更新します。

* S2J Webinar プラグイン **有効**
* 当該イベントで **ソース定義一覧 GET が成功** (`sources` が空でないこと)

満たさない場合はカタログ guard 不合格として **503** のみ返し、メタを更新しない (オフライン編集でメタを書き換えない)。文書検証 NG は **4xx** — [rest_api_spec.md#http-カタログ保存](./rest_api_spec.md#http-カタログ保存)。

* **同時編集 / 楽観ロック:** 初版 **なし** (最後の保存勝ち。[overview.md](./overview.md) Out of Scope)。
* **UI / サーバー二重 guard:** クライアント `catalogReady` とサーバー catalog guard は **別々に同条件** を評価する (単一共有 API は作らない)。UI disabled でも POST 時はサーバー **503** ([admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))。

### 検証エラー `code` (初版)

`draft` / `apply` の REST **`code` 正本 (初版)** (`WP_Error` / WP REST 応答):

| `code` | 用途 |
| --- | --- |
| `invalid_registration_url` | URL 形式・スキーム・長さ NG。**Apply** で定義上の行に URL 欠落も本 code (メッセージ: `One or more registration URLs are invalid.` 等) |
| `invalid_document` | スタイル hex / size、attachment 等のその他検証 NG |
| `qr_generation_failed` | **`apply` / preview**。GD / QR 生成・中央 icon 合成失敗 — HTTP **400** |
| `qr_not_implemented` | **(履歴・予約)** HTTP **501** — 初版起草時の PNG 未実装。**現行 `includes/` では返さない** ([rest_api_spec.md](./rest_api_spec.md)) |

例 JSON: [rest_api_spec.md#エラー応答-例](./rest_api_spec.md#エラー応答-例)。追加 code は実装後に本表に追記。

### 正規化 (本プラグイン PHP)

初版では Survey Service の `evaluate` に相当する処理を **本プラグイン内** に置きます (専用 Service なし)。`draft` と `sanitize_callback` は **同一エントリ** を呼びます。

1. **`provider`:** [data_dictionary.md#provider-解決-正本](./data_dictionary.md#provider-解決-正本) に従いメタに保存。
2. **`channels`:** Webinar ソース定義一覧に存在する `id` のみ残す。各 `label` を定義から **スナップショット** (初版: 手編集不可)。定義にあって body にない行は **空 URL 行として追加** (Webinar 側でソースが増えたあとの draft 保存でも同様)。
3. 各 `registration_url` は `esc_url_raw` 相当後、**http / https のみ**、長さ上限 (2048等) を検証。空 URL は **draft** で許可。
4. `style.foreground_hex` は `#` + 6桁 hex。`size_px` は256〜2048に clamp。
5. `icon_attachment_id` は正の attachment のみ。画像 MIME は CoverArt と同リストを推奨。
6. **`qr_attachment_id` 無効化 (draft 正規化時):** 既存メタが **無い** 初回保存ではスキップ。それ以外は直前メタと比較し、当該行の `registration_url` または文書共通 `style` (icon / hex / size) が変わった行は `qr_attachment_id` = **0**。
7. **Apply 時:** ソース定義 **全行** に有効な `registration_url` があること → 行ごとに PNG 生成・メディア登録 → `qr_attachment_id` 更新 → `status` = **`ready`** → `updated_at` = UTC の `DATE_ATOM` 形式。
8. **draft 保存:** `status` = **`draft`** のみ ( **`ready` は apply のみ** )。

**失敗時** (検証エラー、カタログ503、Apply / PNG **400**): メタ **前値維持**。ユーザー向けメッセージのみ ([rest_api_spec.md](./rest_api_spec.md))。

### 取り込み結果のマージ

Webinar REST から `{ "items": [ { "source_name", "registration_url", "external_id"? } ] }` を受け取った場合 ([rest_api_spec.md](./rest_api_spec.md)):

**前提:** パネル `channels` が **ソース定義一覧で構築済み** ([admin_ui_spec.md](./admin_ui_spec.md))。未構築時は Import を出さない / 無効。

1. `source_name` === 既存行 `id` の行に `registration_url` を代入。
2. 定義一覧に **無い** `source_name` は **未登録一覧** として UI 警告。**メタ行は増やさない**。`external_id` はメタに保存しない。
3. マージ後 **必ず** `POST …/document/draft` (成功時のみメタ更新)。

写像の正本は webinar-service `zoom` Adapter です。本プラグインは **写像済み JSON** のみ受け取ります。

### クライアント / サーバー正規化 (責務)

| 層 | 責務 |
| --- | --- |
| TS (`src/domain/document.ts`) | 定義 + `GET document` の **行マージ**、**取り込みマージ** (パネル表示用) |
| PHP (`includes/Domain/document.php`) | **保存時の真** — `draft` / `apply` / `sanitize` 正規化、カタログ照合 |

取り込み後は必ず `POST …/draft` し、**サーバー正規化結果** をメタの正とする ([plugin_spec.md](./plugin_spec.md))。

## 無効化・アンインストール

| 操作 | メタ | QR PNG メディア | 接続先 |
| --- | --- | --- | --- |
| 無効化 | 残す | 残す | 変更しない |
| アンインストール | **消す** | **消さない** | 変更しない |

* Webinar ソース定義から **消えた行** の旧 `qr_attachment_id` PNG は、初版 **ガベージコレクションしない** (上表どおりアンインストール後もメディアは残る)。

## S2J Webinar との受け渡し

* `ready` / `draft` 文書は `_s2j_webinar_source_qr` にある。
* S2J Webinar は **メタを読むだけ** (サマリー)。**書き込まない** ([source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md))。
* **同期 plan に載せない** (Survey `attach_survey` とは別)。
* S2J Webinar が無効でも、**既存メタ** があれば Fill から **閲覧** (表示) 可。**Save draft / Apply は不可** (厳格 — [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))。
* ソース行の生成・取り込み・永続化は **Webinar 有効 + 定義 REST 成功** が前提 ([admin_ui_spec.md](./admin_ui_spec.md))。
* 取り込み GET は Webinar REST 経由 (本プラグインは token を読まない)。

## 関連

* [data_dictionary.md](./data_dictionary.md)
* [admin_ui_spec.md](./admin_ui_spec.md)
* [rest_api_spec.md](./rest_api_spec.md)
