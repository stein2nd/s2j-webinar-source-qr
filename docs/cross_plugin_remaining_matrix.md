# S2J ウェビナー系 — 100% チェックリスト未完了の分類

最終更新: 2026-10-18

**配置:** 本ファイルは **S2J Webinar Source QR** リポジトリのコピーです。同一内容を [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/cross_plugin_remaining_matrix.md) / [S2J Webinar](https://github.com/stein2nd/s2j-webinar/blob/main/docs/cross_plugin_remaining_matrix.md) にも置き、`status.md` の `[ ]` 更新時に **3 ファイルを同期** してください。

各プラグインの **正本** はそれぞれの `docs/status.md` §100% 完了条件チェックリスト です。本ファイルは **横断の読み方** 用で、`[ ]` 行を次の3軸に振り分けた一覧です。

| 軸 | 意味 |
| --- | --- |
| **Local** | Local / Studio / CI (`localhost`、MySQL テスト DB、WP-CLI smoke 等) で完結。公衆 HTTPS 不要 |
| **Webinar 実装待ち** | **S2J Webinar** の機能・REST・hook が先 (例: **P2a** 定義 REST、handoff 契約)。実装後は多くが **Local** に移る |
| **HTTPS + Zoom** | 外部結合ゲート — Marketplace OAuth、実 Zoom API、Webhook、実 Zoom 上のデータ (登録 URL、Survey 添付等)。ステージング / トンネル想定 |

**Service リポジトリ** (`s2j-webinar-service` / `s2j-webinar-survey-service`) の `[ ]` は各 Service の `docs/status.md` が正本です。Webinar [status §品質](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#status-quality) に「本プラグイン 100% 到達後に Service 側を [x]」があります。

**外部結合ゲート** (`E1` …) の Pass は各 `test-results.md` に記録し、多くの repo では **100% 分母外** です (実装％に含めない)。

---

## サマリ (件数)

| リポジトリ | Local | Webinar 実装待ち | HTTPS + Zoom | 備考 |
| --- | ---: | ---: | ---: | --- |
| [S2J Webinar Survey](#s2j-webinar-survey) | 9 | 0 | 0 (+ E3/E4 ゲート) | Zoom 非接触は Local 検証 |
| [S2J Webinar](#s2j-webinar) | 2 | 2 | 1 (+ E1–E4 ゲート) | P3 は v0.0.2 対象外 |
| [S2J Webinar Source QR](#s2j-webinar-source-qr) | 10 | 5 | 3 (+ E1/E3 ゲート) | 任意・handoff 含む |

---

## S2J Webinar Survey

正本: [status.md §100%](../s2j-webinar-survey/docs/status.md#100-完了条件チェックリスト) / [test-results.md](../s2j-webinar-survey/docs/test-results.md) ([GitHub](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/status.md))

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| 仕様 | CI: `docs-lint` | ● | | |
| Composer | Service 橋渡し smoke (段階1 WP-CLI) | ● | | |
| 設定 | Test: option 保存 | ● | | |
| 管理 UI | Smoke: REST / Fill (`smoke-local.sh`) | ● | | |
| 管理 UI | wp-env — メタ REST 往復 | ● | | 任意 |
| 永続化 | Test: wp-env 統合 (任意) | ● | | |
| 下書き UI | コネクタモック / E2E 採用フロー | ● | | 実 LLM は Local 可 |
| 品質 | `.wp-env.json` + wp-env PHPUnit (任意) | ● | | |
| 品質 | 段階3b — wp-env green | ● | | |
| アンインストール | Zoom 非接触 (アンインストールで Zoom 側を触らない) | ● | | |

### ゲートのみ (チェックリスト `[ ]` ではない)

| # | 内容 | HTTPS + Zoom |
| --- | --- | :---: |
| E3 | Survey `ready` → Webinar `attach_survey` | ● (Webinar E2 後) |
| E4 | Zoom 上アンケート添付 | ● |

---

## S2J Webinar (+ Webinar Service)

正本: [status.md §100%](../s2j-webinar/docs/status.md#100-完了条件チェックリスト) / [test-results.md](../s2j-webinar/docs/test-results.md) ([GitHub](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md))

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| 品質 | Blueprint **ゼロから** Studio 再現 Pass 記録 | ● | | |
| 品質 | Service §プラグイン — 本プラグイン 100% 後に Service [x] | ● | | プロセス (Service repo) |
| 品質 | Source QR 連携 — P2a 後 localhost `catalogReady` Pass | | ● | |
| 品質 | **[E1–E4]** — OAuth / 実 API / Webhook / Survey attach | | | ● |
| 運用 P1 | CoverArt / フライヤー | ● | | HTTP なし |
| 運用 P2 | Source QR / tracking REST (定義 → 取り込み) | | ● | P2b 実 GET は E2 |
| 運用 P3 | 配配メール (ラクス) | — | — | status 100% 外 |

### 外部結合ゲート (詳細)

| # | 内容 | Local | HTTPS + Zoom |
| --- | --- | :---: | :---: |
| E1 | OAuth connect (Marketplace Redirect / Allow list) | 資格情報保存・試行まで可 | ● 成功確認 |
| E2 | create / update / get / delete 実 API | 未接続 422 等は Local | ● |
| E3 | Webhook started / ended | | ● |
| E4 | Survey `ready` → attach_survey 実 Zoom | Survey UI は Local | ● |
| E5 | 参加 URL 目視 | ● | |
| E7 | Source QR 定義 REST → `catalogReady` | ● (P2a 後) | |

---

## S2J Webinar Source QR

正本: [status.md §100%](./status.md#100-完了条件チェックリスト) / [test-results.md](./test-results.md)

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| REST | `sanitize_callback` — draft と同一カタログ厳格 | ● | | |
| REST | `save_post` 再正規化 — 任意 | ● | | |
| REST | meta sanitize / 経路ガード文書化 | ● | | |
| REST | 4xx 拡張 — 任意 | ● | | |
| 管理 UI | Apply **成功時** UI (Step4 ブラウザ記録) | ● | | API は Integration PASS |
| 管理 UI | dirty / Notice `code` 分岐 | ● | | |
| 管理 UI | handoff から同一モーダル | | ● | hook 名 |
| 管理 UI | `catalogReady` true で Save draft 200 | | ● | Webinar 定義 REST |
| 管理 UI | Import → POST draft — 実 `tracking-sources` | | | ● E1/E3 |
| カタログ | 定義 REST 200 → `catalogReady` true (実 Webinar) | | ● | stub で UI 代替済 |
| 取り込み | 実 Zoom 登録 URL → draft 永続化 | | | ● |
| handoff | Webinar hook 名確定 | | ● | |
| handoff | open リスナ + 同一 Modal | | ● | |
| handoff | Webinar から同一モーダル open (Test) | | ● | E5 Local 可 |
| 品質 | Blueprint ゼロから Studio Pass 記録 | ● | | |
| 品質 | **E2** 定義 REST — Webinar 実装後 localhost Pass | | ● | |
| 品質 | **E3** 取り込み | | | ● |
| 品質 | Webinar § Source QR — 定義 REST 実装後 E2E | | ● | |

### 外部結合ゲート (参照)

| # | 内容 | Local | HTTPS + Zoom |
| --- | --- | :---: | :---: |
| E1 | Webinar OAuth / `_s2j_webinar_id` | | ● |
| E2 | 定義 REST 200 + `sources.length >= 1` | ● (P2a 後) | |
| E3 | 取り込み `tracking-sources` | | ● |
| E4 | Save draft / Apply (`catalogReady`) | ● (E2 後) | |
| E6 | QR PNG Apply E2E | ● (Integration PASS) | |

---

## 推奨の進め方 (依存順)

1. **Local 自動** — 各 repo [status §推奨テスト実施順](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#status-test-order) 順0–2 (Survey: smoke / option テスト等を追加)。
2. **Webinar P2a** — 定義 REST → Source QR **E2** / Webinar **E7** / Save draft 200 (Local)。
3. **Webinar P1 / Source QR sanitize・UI 残** — Local のみ。
4. **HTTPS ステージング** — Webinar **E1 → E2 → E3**、続けて Survey **E3/E4**、Source QR **E3** (Import)。

---

## メンテナンス

* 各 repo の `status.md` で `[ ]` / `[x]` を更新したら、**3 リポジトリ** の `docs/cross_plugin_remaining_matrix.md` を同じ内容で揃える (相対リンク行のみ各 repo 向けに調整可)。
* 配置: `s2j-webinar-source-qr/docs/` (本ファイル)、`s2j-webinar-survey/docs/`、`s2j-webinar/docs/`。Local モノレポでは `wp-content/plugins/` 直下の兄弟パス。
