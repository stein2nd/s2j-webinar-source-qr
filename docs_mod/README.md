# S2J ウェビナー・ソース追跡 QR - 起草用 (存置)

> **確定仕様の正本:** [docs/](../docs/) — v0.0.1 合意内容は **docs/** に写経済みです。本 **docs_mod/** は後日の改訂案と、進行中イニシアチブの三点セット (`modification.md` / `status.md` / `test-results.md`) 用に **削除せず存置** します。

改訂を始める際は、本ディレクトリで起草し、合意後に **docs/** に反映してください。運用規則は [docs/governance/documentation_governance.md](../docs/governance/documentation_governance.md) です。完了イニシアチブは [docs/archive/](../docs/archive/) に freeze します。

## 正本へのリンク (仕様本文)

| ファイル | 内容 |
| --- | --- |
| [specs.md](../docs/specs.md) | 索引・読み順・版の取り扱い |
| [overview.md](../docs/overview.md) | 責務・非対応 |
| [plugin_spec.md](../docs/plugin_spec.md) | 統合見取り図・境界 |
| [data_dictionary.md](../docs/data_dictionary.md) | `_s2j_webinar_source_qr` |
| [persistence_spec.md](../docs/persistence_spec.md) | 保存、Webinar 受け渡し |
| [admin_ui_spec.md](../docs/admin_ui_spec.md) | 4ステップ UI |
| [rest_api_spec.md](../docs/rest_api_spec.md) | 本プラグイン REST |
| [status.md](../docs/status.md) | 実装追随の進捗 |

## 親プロジェクトとの関係

* 索引・接続先調査 (`zoom`): [S2J Webinar 運用素材 (v0.0.2)](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md)
* handoff: [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_handoff_spec.md)
* 用語 (横断): [glossary_operational.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/glossary_operational.md)
