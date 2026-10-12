# S2J ウェビナー・ソース追跡 QR - データ辞書

用語 (パネル文書 / メタ文書) は [S2J Webinar 仕様 v0.0.2 / glossary.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/glossary_operational.md) と [persistence_spec.md](./persistence_spec.md) を参照します。横断用語表は [specs.md#用語](./specs.md#用語) です。本プラグインの版は [specs.md#版の取り扱い](./specs.md#版の取り扱い) です。

## 非対象

* Zoom API フィールド名の一覧 (写像正本は webinar-service Adapter 起草)
* OAuth option キー (S2J Webinar)

## イベントメタ (本プラグイン)

| キー | 型 | 説明 |
| --- | --- | --- |
| `_s2j_webinar_source_qr` | object (保護メタ) | 1イベントにつき一文書。`register_post_meta`: `type` = `object` (相当)、`show_in_rest` = true、`auth_callback` は `edit_post` 相当。書き込み正規化は [persistence_spec.md](./persistence_spec.md) |

### イベントメタ (S2J Webinar — 参照のみ)

| キー | 本プラグインでの用途 |
| --- | --- |
| `_s2j_webinar_id` | 取り込みボタン表示条件。ソース定義一覧 REST の前提 (未設定時 Webinar 正本 **4xx** → `catalogReady` false — [admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約)) |
| `_s2j_webinar_provider` | [provider 解決](#provider-解決-正本) の入力の一つ。Import 表示は open 後 **GET 解決済み** のパネル `provider` — [admin_ui_spec.md#1-ソースと-registration-url](./admin_ui_spec.md#1-ソースと-registration-url) (初版 `zoom` のみ) |
| `_s2j_webinar_status` | **ソース追跡 QR 文書の `status` とは無関係**。Webinar 接続・同期状態 ([glossary](https://github.com/stein2nd/s2j-webinar/blob/main/docs/glossary_operational.md)) |

## メタ文書のトップレベル

| フィールド | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `status` | string | はい | **ソース追跡 QR 文書** の `draft` \| `ready`。`_s2j_webinar_status` とは別 |
| `provider` | string | はい (正規化後) | [provider 解決](#provider-解決-正本) |
| `channels` | array | はい | 下記チャネル行。**件数・`id` は S2J Webinar のソース名正本に従う** (固定 id なし) |
| `style` | object | はい | 下記スタイル。全ソース行で共通 (初版 v0.0.1) |
| `updated_at` | string | いいえ | **UTC** の `DATE_ATOM` / `gmdate('c')`。**保存成功時のみ**サーバーが更新 |

### provider 解決 (正本)

`draft` / `apply` / `GET …/document` 応答でサーバーが同じ順序で解決し、**メタに保存** (REST body の `provider` は無視):

1. リクエスト処理時点のメタ `_s2j_webinar_source_qr.provider` (非空)
2. イベント `_s2j_webinar_provider` (非空)
3. リテラル `zoom`

### チャネル行 (`channels[]`)

| フィールド | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `id` | string | はい | **S2J Webinar のソース名** (取り込み `source_name` と同一キー) |
| `label` | string | いいえ | 表示用。**正規化時に Webinar ソース定義からスナップショット**。空なら UI は `id` を表示 |
| `registration_url` | string | 条件 | 接続先発行 URL。**加工禁止** (glossary)。`ready` では **Webinar ソース定義一覧上の全行** が非空かつ検証済み |
| `qr_attachment_id` | int | 条件 | `ready` 時の PNG attachment ID。`draft` では未設定可 (0)。URL / 共通 `style` 変更で **0に無効化** ([persistence_spec.md](./persistence_spec.md)) |

### チャネル行の組み立て (初版)

**`GET …/document` は保存済みメタのマージと `provider` 解決のみ** ([rest_api_spec.md](./rest_api_spec.md))。編集 UI で使う **行の完全性** (定義順・件数) は UI が次で行う:

1. `GET …/document` でパネル初期値を取得。
2. Webinar **ソース定義一覧** REST ([admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約)) で `sources[]` を取得。各要素は `{ source_name, label }`。
3. 定義順に行を生成し、手順1の `channels` から同一 `id` の `registration_url` / `qr_attachment_id` をマージ ([admin_ui_spec.md](./admin_ui_spec.md))。
4. 定義 REST 取得失敗: 手順1の `channels` のみ **閲覧** + Notice。**保存不可** (初版厳格 — [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))。メタも空なら行なし。

サーバー `draft` / `apply` 正規化も **同一ソース定義一覧** で照合 ([persistence_spec.md](./persistence_spec.md))。

本プラグインは **未知の `id` 行を取り込みだけで増やさない**。

### スタイル (`style`)

| フィールド | 型 | 必須 | 説明 |
| --- | --- | --- | --- |
| `icon_attachment_id` | int | いいえ | 中央アイコン。0 / 未設定はアイコンなし |
| `foreground_hex` | string | はい | `#RRGGBB`。QR モジュール色 |
| `size_px` | int | はい | 出力の正方形辺 px。**256〜2048**。デフォルト **512** ([admin_ui_spec.md](./admin_ui_spec.md)) |

### メタ文書に持たないもの

* 取り込み API の生 JSON
* OAuth token、接続先エラー詳細 (ログのみ)
* 取り込み item の `external_id` (UI / ログ用のみ — [rest_api_spec.md](./rest_api_spec.md))

## 規範デフォルト (空メタ・新規イベント)

`GET …/document` でメタがないとき、サーバーは次の形をベースに **メタマージのみ** 行います (`channels` は **保存済み分のみ**。Webinar 定義の付与は UI 手順)。

```json
{
  "status": "draft",
  "provider": "zoom",
  "channels": [],
  "style": {
    "icon_attachment_id": 0,
    "foreground_hex": "#000000",
    "size_px": 512
  },
  "updated_at": ""
}
```

* 応答の `provider` は **解決済み値** (上記は `_s2j_webinar_provider` 未設定時の例)。

## サイト option (初版 v0.0.1)

初版では **必須のサイト option なし**。将来のデフォルトスタイル用に拡張可。

## 一貫性ルール

* `registration_url` は常にこのキー名 (Zoom `tracking_url` は Adapter が写す)。
* 手編集・ REST body で `status` を `ready` にせず、**`apply` の結果だけ** を `ready` の正とする。
* チャネル `id` は **Webinar ソース名** のみ。本リポジトリに `web` / `sns` / `other` 等の固定列挙を置かない。
