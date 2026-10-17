# S2J ウェビナー・ソース追跡 QR - 仕様書の起点

本プロジェクトの仕様は、下記のドキュメントに分散して定義します。
各ドキュメントへの導線のみを提供し、詳細は個別ファイルに委譲します。

統合見取り図は [plugin_spec.md](./plugin_spec.md) です。構成は [S2J Webinar Survey の docs/](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/specs.md) に倣い、本プラグインに必要な層だけを置きます。

## 版の取り扱い

| 対象 | 版 | 意味 |
| --- | --- | --- |
| **本プラグイン** (S2J ウェビナー・ソース追跡 QR) | **v0.0.1** | 本 **`docs/`** が記述する **このプラグイン** の初版仕様・製品版 ([docs_mod/](../docs_mod/) は改訂案用) |
| **S2J Webinar** (親プラグイン) | **v0.0.2** | 運用素材 (CoverArt / フライヤー / Source QR handoff 等) の **Webinar 側** 改訂。正本 [operational_material_index.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md) |

本リポジトリの文中で **「初版」** と書く場合は、特記なければ **本プラグイン v0.0.1** を指します。Webinar の版を指す場合は **「S2J Webinar 仕様 v0.0.2」** などと明示します。

## 共通仕様

* [SPECS.md (共通仕様)](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md)

## 計算の正本 (Composer)

初版 (本プラグイン v0.0.1) では **`s2j-webinar-source-qr-service` は作りません。** Source Tracking **取り込み** の写像は [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) のプロバイダ Adapter です。QR 符号化と文書検証 (URL、スタイル) は本プラグインに置きます。

* 上記 Composer 方針の根拠 (Webinar 側): [S2J Webinar 運用素材索引](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md)

## 読み方ガイド

### 推奨読み順 (初読)

1. **[overview.md](./overview.md)**: なぜ存在するか、UI 表記規約
2. **[plugin_spec.md](./plugin_spec.md)**: 位置付けと境界
3. **[data_dictionary.md](./data_dictionary.md)**: メタ文書の形、デフォルト JSON
4. **[persistence_spec.md](./persistence_spec.md)**: 保存と Webinar への受け渡し
5. **[admin_ui_spec.md](./admin_ui_spec.md)**: 4ステップ UI、**Webinar REST 契約**
6. **[rest_api_spec.md](./rest_api_spec.md)**: 本プラグイン REST
7. **[governance/documentation_governance.md](./governance/documentation_governance.md)**: SoT、用語、lint、`docs_mod` → `docs`、archive

### 実装者 (本プラグイン)

1. [data_dictionary.md](./data_dictionary.md)
2. [persistence_spec.md](./persistence_spec.md)
3. [admin_ui_spec.md](./admin_ui_spec.md) — [Webinar REST](./admin_ui_spec.md#webinar-rest-本-ui-契約)
4. [rest_api_spec.md](./rest_api_spec.md)
5. [status.md](./status.md)

### S2J Webinar 側の実装者

1. [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md)
2. [source_qr_tracking_definitions_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_tracking_definitions_rest_spec.md)
3. [source_qr_tracking_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_tracking_rest_spec.md)
4. 本プラグインの [persistence_spec.md](./persistence_spec.md) (メタの読み方)

## ドキュメント一覧

| ドキュメント | 内容 |
| --- | --- |
| [overview.md](./overview.md) | 基本情報、責務、非対応、UI 表記 |
| [plugin_spec.md](./plugin_spec.md) | 統合見取り図、handoff |
| [data_dictionary.md](./data_dictionary.md) | メタキー、provider 解決、行の組み立て |
| [persistence_spec.md](./persistence_spec.md) | 保存経路、正規化、503、4xx `code` 正本 (`qr_generation_failed` 等) |
| [admin_ui_spec.md](./admin_ui_spec.md) | メンテナンス UI、Webinar REST |
| [rest_api_spec.md](./rest_api_spec.md) | 本プラグイン REST、`catalogReady` / **4xx** vs **503** |
| [status.md](./status.md) | 起草進捗 |
| [ドキュメンテーション・ガバナンス](./governance/documentation_governance.md) | SoT、用語、lint、改訂フロー、archive |
| [archive 索引](./archive/README.md) | 完了イニシアチブの凍結一覧 |

## 用語

[S2J Webinar glossary_operational.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/glossary_operational.md) を横断用語の正本とします。

| 仕様書 (日本語) | データキー / コード | 備考 |
| --- | --- | --- |
| ソース名 | 行 `id`、取り込み `source_name`、定義 REST `source_name` | **正本は S2J Webinar**。固定 id なし |
| チャネル行 | `channels[]` の要素 | **ソース名ごとに一行** (「チャネル」= UI 上のソース行) |
| ソース定義一覧 | Webinar `tracking-source-definitions` | 行の正本。取り込み REST とは別。**Webinar 正本:** イベント `_s2j_webinar_id` 必須 (未連携 **4xx**) — [admin_ui_spec.md](./admin_ui_spec.md) |
| 登録 URL | `registration_url` | UI: `Registration URL`。加工禁止 |
| 取り込み (Import) | Webinar `tracking-sources` | 初版 `zoom` のみ。表示条件 (パネル `provider` 等) の正本 [admin_ui_spec.md#1-ソースと-registration-url](./admin_ui_spec.md#1-ソースと-registration-url) |
| ソース追跡 QR 文書の状態 | メタ `status` | `_s2j_webinar_status` とは別 ([data_dictionary.md](./data_dictionary.md)) |
| 製品名 (仕様書) | — | **S2J ウェビナー・ソース追跡 QR** |
| 製品名 (UI 英語) | `_x( …, 'plugin title' )` 等 | [overview.md#ui-表記規約-初版-v001](./overview.md#ui-表記規約-初版-v001) |
| **`catalogReady`** | UI / クライアント state (boolean) | Webinar **有効** + ソース定義一覧 GET **成功** + `sources.length >= 1` — [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格)。**実装では同名 `catalogReady` を推奨**。TS は **導出を1ヵ所** ([admin_ui_spec.md#s2j-webinar-有効-クライアント](./admin_ui_spec.md#s2j-webinar-有効-クライアント)) |
| 定義 REST **4xx** (Webinar) | — | 例: `_s2j_webinar_id` 未連携。UI は **`catalogReady` false** (本プラグイン REST は呼ばない) |
| 保存 **503** (本プラグイン) | REST `code`: `catalog_unavailable` | `draft` / `apply` / `sanitize` がカタログ不可時。Webinar 側 **4xx** 等をサーバーが503に正規化 — [rest_api_spec.md#http-カタログ保存](./rest_api_spec.md#http-カタログ保存) |
| 文書検証 **4xx** | `invalid_registration_url` / `invalid_document` 等 | 正本 [persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版)。JSON 例 [rest_api_spec.md#エラー応答-例](./rest_api_spec.md#エラー応答-例) |
| QR 生成 **400** (`apply` / preview) | `qr_generation_failed` | GD / PNG / icon 合成失敗 — [persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版) |
| **501** (履歴) | `qr_not_implemented` | 起草時の PNG 未実装。**現行実装では emit しない** |
