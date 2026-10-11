<!--
目的：「プラグイン v0.0.1 起草進捗」
-->

# S2J ウェビナー・ソース追跡 QR - 実装状況 (起草)

本ページは **docs/** (本プラグイン **v0.0.1** 確定仕様) の実装追随・外部合意の進捗です。機能実装の % ではありません。仕様改訂案は [docs_mod/](../docs_mod/) を参照。

版の取り扱い: [specs.md#版の取り扱い](./specs.md#版の取り扱い)

* [governance/documentation_governance.md](./governance/documentation_governance.md) — ドキュメント運用
* [archive/README.md](./archive/README.md) — 完了イニシアチブ (まだなし)

最終更新: 2026-10-11

## 起草

| 項目 | 状態 | メモ |
| --- | --- | --- |
| [specs.md](./specs.md) | 起草済 | 用語・ Webinar REST 導線 |
| [overview.md](./overview.md) | 起草済 | 英語 UI 製品名、手入力条件 |
| [plugin_spec.md](./plugin_spec.md) | 起草済 | handoff 同一モーダル |
| [data_dictionary.md](./data_dictionary.md) | 起草済 | provider 解決、GET/ UI 分担 |
| [persistence_spec.md](./persistence_spec.md) | 起草済 | ready/apply、503、QR 初回 |
| [admin_ui_spec.md](./admin_ui_spec.md) | 起草済 | `tracking-source-definitions` 契約 |
| [rest_api_spec.md](./rest_api_spec.md) | 起草済 | GET の非責務、503 (厳格)、4xx ・501エラー例 |
| 保存可否 (I/J) | 起草済 | Webinar 無効 = 閲覧のみ、`catalogReady` |
| プラグイン実装 | 骨格着手 | **仕様確定後に追随** ([plugin_spec.md](./plugin_spec.md) 実装順)。未: `catalogReady`、localize (`s2jWebinarActive` / `webinarDefinitionsPathTemplate` / `webinarTrackingPathTemplate`)、定義 REST 行マージ、4xx `code` ([persistence_spec.md#検証エラー-code-初版](./persistence_spec.md#検証エラー-code-初版))。現状は旧 channel 骨格 |
| `docs/` 正本 | **反映済** | v0.0.1合意内容を [docs/](../docs/) に写経 ([docs_mod/](../docs_mod/) は存置) |

## Webinar 側待ち

| 項目 | 状態 |
| --- | --- |
| `GET …/tracking-source-definitions` パス / 実装 | 本 repo は **期待契約** を記載 ([admin_ui_spec.md#webinar-rest-本-ui-契約](./admin_ui_spec.md#webinar-rest-本-ui-契約))。正本は Webinar handoff。`_s2j_webinar_id` 未設定時 **4xx** は Webinar spec に明記 |
| handoff 同一モーダル hook | Webinar + 本プラグイン open リスナ。**hook 名 TBD** — [plugin_spec.md#webinar-handoff-同一モーダル](./plugin_spec.md#webinar-handoff-同一モーダル) |

**合意後 (本 repo の doc メンテナンス):** Webinar handoff / tracking REST が確定したら、(1) REST **パス**・**4xx** 条件を正本と同期、(2) [admin_ui_spec.md](./admin_ui_spec.md) の表見出しを **期待契約** から **確定** に更新 (差分があれば JSON 形は維持)、(3) handoff hook 名を [plugin_spec.md](./plugin_spec.md) に追記。

## 親リポジトリとの整合

| 項目 | 状態 |
| --- | --- |
| [Webinar 仕様 v0.0.2 / glossary](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/glossary.md) | 相互リンク |
| [source_qr_handoff_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/source_qr_handoff_spec.md) | 定義 REST パス 正本 |
| [source_qr_tracking_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs_mod/v0.0.2/source_qr_tracking_rest_spec.md) | 取り込み REST |
| [webinar-service tracking import](https://github.com/stein2nd/s2j-webinar-service/blob/main/docs_mod/core/tracking_import_spec.md) | 起草済 |

## 合意後の完了条件

* ~~本 `docs_mod/` を `docs/` に移行または写経し、README からリンク。~~ **完了**
* Webinar 側 `tracking-source-definitions` が本契約 (JSON 形) と一致。
* 実装が **docs/** に追随 ([status.md](./status.md) 実装順)。
