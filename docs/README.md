# S2J Webinar Source QR - 仕様 (正本)

本ディレクトリ **docs/** が、本プラグイン **v0.0.1** 確定仕様の **正本** です。

* 仕様書上の製品名: **S2J ウェビナー・ソース追跡 QR**
* 管理 UI: 英語文字列 + テキストドメイン `s2j-webinar-source-qr`
* チャネル `id`: **S2J Webinar のソース名** (固定 id なし)
* Webinar REST 契約: [admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約)
* 保存 (初版厳格): [admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格)
* 定義 REST: イベント `_s2j_webinar_id` 必須 (Webinar 正本 **4xx**)。`catalogReady` = Webinar **有効** + 定義 GET 成功 + `sources` 非空 ([specs.md#用語](./specs.md#用語))
* HTTP: Webinar 定義 **4xx** (UI) vs 本プラグイン **503** (保存) — [rest_api_spec.md#http-カタログ保存](./rest_api_spec.md#http-カタログ保存)
* REST 検証 `code`: [persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版)
* localize 正本: `s2jWebinarInstalled` / `s2jWebinarActive`、`webinarInstallGuideUrl` / `webinarActivateUrl`、定義・取り込み path template、**`webinarHandoffOpenHook`** — [admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約)
* Webinar 依存パネル **3 状態** — [admin_ui_spec.md#s2j-webinar-有効-クライアント](./admin_ui_spec.md#s2j-webinar-有効-クライアント)
* handoff (Source QR 側): **`s2j_webinar.open_source_qr`** → 同一モーダル — [admin_ui_spec.md#webinar-handoff-実装](./admin_ui_spec.md#webinar-handoff-実装) / Webinar [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md)

版の取り扱いは [specs.md](./specs.md#版の取り扱い) を参照してください。**S2J Webinar 仕様 v0.0.2** は親プラグイン側の版であり、本プラグインの製品版ではありません。

将来の仕様改訂案は [docs_mod/](../docs_mod/) に置き、合意後に **docs/** に反映します。

ドキュメント運用: [governance/documentation_governance.md](./governance/documentation_governance.md) — 完了イニシアチブ索引は [archive/README.md](./archive/README.md)。

## 親プロジェクトとの関係

* 索引・接続先調査 (`zoom`): [S2J Webinar 運用素材](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md)
* 本プラグインを呼び出す側 handoff: [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md)
* 用語 (横断): [glossary_operational.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/glossary_operational.md)

## 仕様ファイル

| ファイル | 内容 |
| --- | --- |
| [specs.md](./specs.md) | 索引・読み順・版の取り扱い |
| [overview.md](./overview.md) | 責務・非対応 |
| [plugin_spec.md](./plugin_spec.md) | 統合見取り図・境界 |
| [data_dictionary.md](./data_dictionary.md) | `_s2j_webinar_source_qr` |
| [persistence_spec.md](./persistence_spec.md) | 保存、Webinar 受け渡し |
| [admin_ui_spec.md](./admin_ui_spec.md) | 4ステップ UI |
| [rest_api_spec.md](./rest_api_spec.md) | 本プラグイン REST (Webinar 取り込み GET とは別) |
| [status.md](./status.md) | 起草・実装追随の進捗 |
