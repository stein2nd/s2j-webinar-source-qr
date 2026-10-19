# S2J ウェビナー系 — 100% チェックリスト未完了の分類

最終更新: 2026-10-19

**配置:** 本ファイルは **S2J Webinar Source QR** リポジトリのコピーです。同一内容を [S2J Webinar Survey](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/cross_plugin_remaining_matrix.md) / [S2J Webinar](https://github.com/stein2nd/s2j-webinar/blob/main/docs/cross_plugin_remaining_matrix.md) にも置き、`status.md` の `[ ]` 更新時に **3 ファイルを同期** してください。

各プラグインの **正本** はそれぞれの `docs/status.md` §100% 完了条件チェックリスト です。本ファイルは **横断の読み方** 用で、`[ ]` 行を次の3軸に振り分けた一覧です。

| 軸 | 意味 |
| --- | --- |
| **Local** | Local / Studio / CI (`localhost`、MySQL テスト DB、WP-CLI smoke 等) で完結。公衆 HTTPS 不要 |
| **Webinar 実装待ち** | **S2J Webinar** の機能・REST・hook が先 (例: **P2b** 取り込み REST)。**P2a / handoff (Webinar)** と Source QR **handoff リスナ** は **2026-10-19** 実装済 — 残は Pass 記録・HTTPS 系 |
| **HTTPS + Zoom** | 外部結合ゲート — Marketplace OAuth、実 Zoom API、Webhook、実 Zoom 上のデータ (登録 URL、Survey 添付等)。ステージング / トンネル想定 |

**Service リポジトリ** (`s2j-webinar-service` / `s2j-webinar-survey-service`) の `[ ]` は各 Service の `docs/status.md` が正本です。Webinar [status §品質](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#status-quality) に「本プラグイン 100% 到達後に Service 側を [x]」があります。

**外部結合ゲート** (`E1` …) の Pass は各 `test-results.md` に記録し、多くの repo では **100% 分母外** です (実装％に含めない)。

---

## サマリ (件数)

| リポジトリ | Local | Webinar 実装待ち | HTTPS + Zoom | 備考 |
| --- | ---: | ---: | ---: | --- |
| [S2J Webinar Survey](#s2j-webinar-survey) | 0 | 0 | 0 (+ E3/E4 ゲート) | Local 未完了 9 件 **2026-10-19** 完了 (wp-env 分母外) |
| [S2J Webinar](#s2j-webinar) | 2 | 2 | 1 (+ E1–E4 ゲート) | P3 は v0.0.2 対象外 |
| [S2J Webinar Source QR](#s2j-webinar-source-qr) | 0 | 0 | 3 (+ E1/E3 ゲート) | |

---

## S2J Webinar Survey

正本: [status.md §100%](../s2j-webinar-survey/docs/status.md#100-完了条件チェックリスト) / [test-results.md](../s2j-webinar-survey/docs/test-results.md) ([GitHub](https://github.com/stein2nd/s2j-webinar-survey/blob/main/docs/status.md))

**Local 未完了 `[ ]`:** なし (**2026-10-19**)。メタ REST / 3b は `npm run test:unit:php:local` (MySQL) を正本とし、wp-env は採用しない。

| § | 備考 (完了) |
| --- | --- |
| 仕様 | `docs-lint` CI |
| Composer | `smoke-local.sh` — evaluate + REST ルート |
| 設定 / 永続化 / 下書き / アンインストール | Integration 9 本 + unit 6 |
| 品質 | 3a CI + 3b local + `lint:php` + test-results |

### ゲートのみ (チェックリスト `[ ]` ではない)

| # | 内容 | HTTPS + Zoom |
| --- | --- | :---: |
| E3 | Survey `ready` → Webinar `attach_survey` | ● (Webinar E2 後) |
| E4 | Zoom 上アンケート添付 | ● |

---

## S2J Webinar (+ Webinar Service)

正本: [status.md §100%](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#100-完了条件チェックリスト) / [test-results.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/test-results.md) ([GitHub](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md))

**Local 記録 (Pass 済 — [status §品質・スモーク](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#status-quality) / [test-results §最新結果](https://github.com/stein2nd/s2j-webinar/blob/main/docs/test-results.md#最新結果)):**

| 項目 | 記録日 | メモ |
| --- | --- | --- |
| 段階1 smoke + 手動 #1–8 | 2026-10-17 | Local + Studio (`webinar-smoke`)。OAuth #6 SKIP |
| Blueprint **ゼロから** + 手動 #1–8 | **2026-10-18** | 新規 Blueprint サイト。GP + Alpha (Releases)。#7 — `_s2j_webinar_panelists` 2名・スーパーリロード後も保持 |
| 段階3a+3b / `lint:php` | 2026-10-17–19 | **22 + 8** (Webinar 側)、本 repo 3b **19** |
| P2a + **E2/E7** (実定義 UI) | **2026-10-19** | post **8**、stub 無効 — [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/) **01–09** |

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| 品質 | Service §プラグイン — 本プラグイン 100% 後に Service [x] | ● | | プロセス (Service repo) |
| 品質 | Source QR 連携 — **E7** Pass 記録 (P2a 実装 **2026-10-19**) | | | **2026-10-19** Pass ([Webinar test-results](https://github.com/stein2nd/s2j-webinar/blob/main/docs/test-results.md#最新結果)) |
| 品質 | **[E1–E4]** — OAuth / 実 API / Webhook / Survey attach | | | ● |
| 運用 P1 | CoverArt / フライヤー | ● | | HTTP なし |
| 運用 P2 | Source QR **P2b** 取り込み REST (定義 **P2a** 済) | | ● | P2b 実 GET |
| 運用 P3 | 配配メール (ラクス) | — | — | status 100% 外 |

### 外部結合ゲート (詳細)

| # | 内容 | Local | HTTPS + Zoom |
| --- | --- | :---: | :---: |
| E1 | OAuth connect (Marketplace Redirect / Allow list) | 資格情報保存・試行まで可 | ● 成功確認 |
| E2 | create / update / get / delete 実 API | 未接続 422 等は Local | ● |
| E3 | Webhook started / ended | | ● |
| E4 | Survey `ready` → attach_survey 実 Zoom | Survey UI は Local | ● |
| E5 | 参加 URL 目視 | ● | |
| E7 | Source QR 定義 REST → `catalogReady` | **Pass 2026-10-19** (Studio post **8**) | |

---

## S2J Webinar Source QR

正本: [status.md §100%](./status.md#100-完了条件チェックリスト) / [test-results.md](./test-results.md)

**Local 記録 (Pass 済 — [status](./status.md) / [test-results §最新結果](./test-results.md#最新結果)):**

| 項目 | 記録日 | メモ |
| --- | --- | --- |
| handoff リスナ + hook 確定 | **2026-10-19** | `MaintenanceModalHost` / `s2j_webinar.open_source_qr`。Webinar `doAction` 側も同日 |
| **E5** handoff 同一モーダル | **2026-10-19** | **Edit source tracking / QR** → **開く…** と同一ダイアログ。証跡 [studio-2026-1019-handoff-e5/](./screenshots/studio-2026-1019-handoff-e5/) |
| **E2** + 順4 (Save / Apply / 再 open) | **2026-10-19** | P2a post **8** — Apply **準備完了**、投稿保存後 **開く…** で URL 保持 (08)。証跡 [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/) |
| E4 / Apply UI / stub U6–U7 | 2026-10-18 | post **8**、証跡 [studio-2026-1018-catalog-stub/](./screenshots/studio-2026-1018-catalog-stub/) |
| Blueprint ゼロ + meta Guard + 3b **19** tests | 2026-10-18 | `:8888`、integration **14** 等 |

| § | 未完了 `[ ]` | Local | Webinar 実装待ち | HTTPS + Zoom |
| --- | --- | :---: | :---: | :---: |
| 取り込み | 実 `tracking-sources` → draft (**E3**) | | | ● |

### 外部結合ゲート (参照)

| # | 内容 | Local | HTTPS + Zoom |
| --- | --- | :---: | :---: |
| E1 | Webinar OAuth / `_s2j_webinar_id` | | ● |
| E2 | 定義 REST 200 + `sources.length >= 1` | ● **Pass 2026-10-19** | |
| E3 | 取り込み `tracking-sources` | | ● |
| E4 | Save draft / Apply (`catalogReady`) | ● **Pass 2026-10-18** (stub) + **2026-10-19** (P2a 実定義 UI) | |
| E5 | handoff 同一モーダル | ● **Pass 2026-10-19** | |
| E6 | QR PNG Apply E2E | ● Integration PASS | |

---

## 推奨の進め方 (依存順)

1. **Local 自動** — 各 repo [status §推奨テスト実施順](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#status-test-order) 順0–2。
    * **Webinar** — 順0–3 + **P2a / E7 / E5 (2026-10-19)** Pass 済 ([test-results §最新結果](https://github.com/stein2nd/s2j-webinar/blob/main/docs/test-results.md#最新結果))。
    * (Survey: smoke / option テスト等を追加)。
2. ~~**Local 連携 (E7/E2/E5)**~~ — **2026-10-19** Pass (stub 無効、定義 + Save draft **200**)。
3. **Webinar P2b / P1** — 取り込み REST、CoverArt 等 (Local / HTTPS)。
4. **HTTPS ステージング** — Webinar **E1 → E3**、Survey **E3/E4**、Source QR **E3** (Import)。

---

## メンテナンス

* 各 repo の `status.md` で `[ ]` / `[x]` を更新したら、**3 リポジトリ** の `docs/cross_plugin_remaining_matrix.md` を同じ内容で揃える (相対リンク行のみ各 repo 向けに調整可)。
* 配置: `s2j-webinar-source-qr/docs/` (本ファイル)、`s2j-webinar-survey/docs/`、`s2j-webinar/docs/`。Local モノレポでは `wp-content/plugins/` 直下の兄弟パス。
