<!--
目的：「本プラグイン REST (メタ保存・反映)」の明文化
-->

# S2J ウェビナー・ソース追跡 QR - REST API

## 設計意図 (ゴール)

メンテナンス UI の **パネル文書** をサーバーで正規化し、メタ `_s2j_webinar_source_qr` に書く経路を固定します ([persistence_spec.md](./persistence_spec.md))。

**接続先取り込み GET** および **ソース定義一覧** は本プラグインでは提供しません (S2J Webinar `s2j-webinar/v1` — [admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約))。

## 非対象

* OAuth token、Zoom HTTP
* Webinar 同期 plan
* 取り込み GET (`tracking-sources`)、定義 GET (`tracking-source-definitions`)

## 共通

| 項目 | 値 |
| --- | --- |
| Namespace | `s2j-webinar-source-qr/v1` |
| 認証 | Cookie + `X-WP-Nonce` (`wp_rest`) |
| 権限 | 対象 `post_id` の `edit_post` |
| Post type | `post_type_supports( …, 'gatherpress-event-date' )` |

## HTTP (カタログ・保存)

| 層 | コード | 意味 |
| --- | --- | --- |
| UI → Webinar **ソース定義一覧** GET | **4xx** 等 | `catalogReady` false。Save draft / Apply / Import **disabled** ([admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格)) |
| UI → 本プラグイン `draft` / `apply` / `sanitize` | **503** (`catalog_unavailable`) | サーバーがカタログ取得失敗 (Webinar **4xx**、空 `sources` 等) を **503に正規化**。メタ前値維持 ([persistence_spec.md#webinar-ソース定義一覧-サーバー正規化](./persistence_spec.md#webinar-ソース定義一覧-サーバー正規化)) |
| UI → 本プラグイン `draft` / `apply` | **4xx** | パネル文書の検証 NG (URL 形式等)。メタ前値維持 |

用語の横断表: [specs.md#用語](./specs.md#用語)。

### エラー応答 (例)

カタログ guard 不合格 (**503**)。WP REST 標準形:

```json
{
  "code": "catalog_unavailable",
  "message": "Source catalog is unavailable.",
  "data": { "status": 503 }
}
```

* UI は `code` で Notice 分岐可 ([admin_ui_spec.md](./admin_ui_spec.md))。

文書検証 NG (**4xx**)。初版 `code` 正本 ([persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版)):

```json
{
  "code": "invalid_registration_url",
  "message": "One or more registration URLs are invalid.",
  "data": { "status": 400 }
}
```

* スタイル ・ attachment 等: `invalid_document` 例:

```json
{
  "code": "invalid_document",
  "message": "Document style or attachments are invalid.",
  "data": { "status": 400 }
}
```

* メタ前値維持。

PNG パイプライン未実装 (**501**、`apply` のみ)。初版 `code` 正本: `qr_not_implemented`

```json
{
  "code": "qr_not_implemented",
  "message": "QR export is not available yet.",
  "data": { "status": 501 }
}
```

* メタ前値維持。UI はダイアログを閉じない ([admin_ui_spec.md](./admin_ui_spec.md))。

## エンドポイント

### `GET /events/{post_id}/document`

| 項目 | 内容 |
| --- | --- |
| 用途 | UI 初期化。**保存済みメタ** を [data_dictionary.md#規範デフォルト-空メタ新規イベント](./data_dictionary.md#規範デフォルト-空メタ新規イベント) とマージ |
| 200 | マージ済み文書 + **`provider` 解決済み** ([data_dictionary.md#provider-解決-正本](./data_dictionary.md#provider-解決-正本)) |
| **行わないこと** | Webinar ソース定義一覧の取得、`channels` の定義にもとづく付与 (それは **UI** — [data_dictionary.md#チャネル行の組み立て-初版](./data_dictionary.md#チャネル行の組み立て-初版)) |

### `POST /events/{post_id}/document/draft`

| 項目 | 内容 |
| --- | --- |
| 用途 | **Save draft** — **`catalogReady` 時のみ** ([admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))。ステップ **1〜4**、URL / スタイルの中間保存。`status` は常に `draft` |
| Body | パネル文書 (JSON object)。body の `status` ( **`ready` への昇格含む** ) および `provider` は **無視** — サーバーが `provider` 解決・`draft` / `apply` に応じて `status` を決定 |
| 200 | 正規化後メタ文書 (`updated_at` 更新) |
| 4xx | 検証失敗 — **メタ前値維持** ([persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版)) |
| **503** | ソース定義一覧の取得失敗・ Webinar 無効・`sources` 空 (`catalog_unavailable`) — **メタ前値維持**。Webinar 定義 GET の **4xx** / 空 `sources` 等はサーバー側で **503に正規化** ([HTTP (カタログ・保存)](#http-カタログ保存)) |

### `POST /events/{post_id}/document/apply`

| 項目 | 内容 |
| --- | --- |
| 用途 | ステップ4 **Apply** (**`catalogReady` 時のみ** — [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))。ソース定義 **全行** URL 必須 → PNG → `status: ready` |
| Body | パネル文書。**Body ルールは `POST …/document/draft` と同一** (body の `status` 昇格・`provider` 無視) |
| 200 | `ready` メタ文書 |
| 4xx | 検証失敗 — **メタ前値維持** ([persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版)) |
| **503** | ソース定義一覧の取得失敗等 — **メタ前値維持** (`draft` と同型。**503正規化** — [HTTP (カタログ・保存)](#http-カタログ保存)) |
| **501** | PNG / 反映パイプライン未実装 (`qr_not_implemented`) — **メタ前値維持**。UI はダイアログを閉じない ([admin_ui_spec.md](./admin_ui_spec.md)、[エラー応答 (例)](#エラー応答-例)) |

## 取り込み (Webinar REST) との関係

| 操作 | 呼び先 |
| --- | --- |
| ソース行の生成 | Webinar `GET …/tracking-source-definitions` ([admin_ui_spec.md](./admin_ui_spec.md))。未連携 **4xx** → `catalogReady` false |
| 取り込みボタン | `GET /s2j-webinar/v1/events/{post_id}/tracking-sources` |
| マージ後 | **必須:** `POST /s2j-webinar-source-qr/v1/events/{post_id}/document/draft` |

### Webinar 取り込み応答 (写像済み・最小例)

契約の正本は Webinar `source_qr_tracking_rest_spec.md`。本プラグインが期待する形:

```json
{
  "items": [
    {
      "source_name": "<Webinar ソース名>",
      "registration_url": "https://example.com/register",
      "external_id": "optional-provider-id"
    }
  ]
}
```

* `external_id` は **メタに保存しない** (Notice / デバッグのみ)。
* 空 `items` の成功応答は、件数ゼロの取り込みとして扱う (失敗はクライアント / サーバーエラー応答)。

マージ規則: [persistence_spec.md#取り込み結果のマージ](./persistence_spec.md#取り込み結果のマージ)。

## 関連

* [admin_ui_spec.md](./admin_ui_spec.md)
* [persistence_spec.md](./persistence_spec.md)
* [data_dictionary.md](./data_dictionary.md)
* Webinar 取り込み: [source_qr_tracking_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/source_qr_tracking_rest_spec.md)
