# S2J ウェビナー・ソース追跡 QR - 実装状況

最終更新: 2026-10-16

索引は [specs.md](./specs.md) です。版の取り扱いは [specs.md#版の取り扱い](./specs.md#版の取り扱い) です。仕様改訂案は [docs_mod/](../docs_mod/) を参照。

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [plugin_spec.md](./plugin_spec.md) — 統合見取り図
* [overview.md](./overview.md) / [admin_ui_spec.md](./admin_ui_spec.md) / [persistence_spec.md](./persistence_spec.md) / [rest_api_spec.md](./rest_api_spec.md) / [data_dictionary.md](./data_dictionary.md)
* [governance/documentation_governance.md](./governance/documentation_governance.md) — ドキュメント運用
* [archive/README.md](./archive/README.md) — 完了イニシアチブ (まだなし)
* [test-results.md](./test-results.md) — 段階1–3、外部結合の記録

## 機能一覧

**完了条件列:** 100% 到達用チェックリスト。`[x]` = 満たし済み、`[ ]` = 未。正本 spec は各§の見出しリンク。

**実装％** は、各§の **プラグイン検証可能** 項 (`[x]` / その全数) のみ。Zoom OAuth、実 Webinar ID 等は [§外部結合ゲート](#status-external) に分離し、Pass 記録があっても % には入れない。§内の **Test (… E1/E3等)** 行と **任意** 行は分母に含めない (例: [§カタログ](#status-catalog) の E2、[§取り込み](#status-import) の E3)。

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (100%) |
| --- | --- | --- | --- |
| 仕様 (`docs/`) | 確定 | — | [§仕様分割](#status-spec-docs) |
| 配布 (Vite / dist / zip) | 実装済み | 100 | [§配布](#status-dist) (5/5) |
| メタ + REST `document` | 一部 | 75 | [§REST・永続化](#status-rest) (9/12) |
| 管理 UI (Gutenberg 4ステップ) | 一部 | 67 | [§管理 UI](#status-admin-ui) (8/12) |
| Webinar 定義 REST + `catalogReady` | 実装済み | 100 | [§カタログ](#status-catalog) (6/6) |
| 取り込み → draft | 実装済み | 100 | [§取り込み](#status-import) (4/4) |
| Apply + QR PNG | 実装済み | 100 | [§QR 反映](#status-qr-apply) (6/6) |
| Webinar handoff (同一モーダル) | 未着手 | 0 | [§handoff](#status-handoff) (0/2) |
| テスト、CI (段階1–3) | 一部 | 75 | [§品質・スモーク](#status-quality) (9/12) |

## 100% 完了条件チェックリスト

各§は **プラグイン実装 → Test (…)** の順。Zoom / 実 API の詳細は [§外部結合ゲート](#status-external)。

**Test 見出しの環境タグ** (先にやる / 後回しの目安):

| タグ | 意味 |
| --- | --- |
| **Local 可のみ** | Local / Studio / CI (`composer test:unit`, `test:unit:php:local`, smoke) で完結。HTTPS 不要 |
| **Local 可 (E*)** | localhost で可だが、表 [§外部結合](#status-external) の **E*** 前提 (例: Webinar 定義 REST) |
| **要 HTTPS** | 公衆 HTTPS (ステージング・トンネル等)。OAuth、実 Zoom 取り込み |

### 推奨テスト実施順 (localhost 自動優先) {#status-test-order}

[S2J Webinar](https://github.com/stein2nd/s2j-webinar) と同型。**段階1/2/3** × **localhost / HTTPS** のマトリックスで、先に localhost の **自動** を完了する。

| 順 | 段階 | 環境 | やること |
| --- | --- | --- | --- |
| 0 | 3a | Local 可のみ | `npm run test:unit:php:unit-only`、lint (WP 不要) |
| 1 | 1 | Local 可のみ (CLI) | Local Site Shell / Studio — `npm run test:smoke:local` |
| 2 | 3b | Local 可 (MySQL) / CI (Docker) | `npm run test:unit:php:local` または CI `phpunit-wp-env` ([test-results.md](./test-results.md#docker-なしの開発環境)) |
| 3 | 2 | Local 可のみ | Studio Blueprint + 手動 UI (Fill、モーダル) |
| 4 | 1–3 | Local 可 (E2/E4/E6) | 定義 REST 後 Save draft、Apply501/200 |
| 5 | 2–3 | 要 HTTPS | OAuth (E1)、Import (E3) — Pass は [test-results.md](./test-results.md) |

詳細コマンドは [test-results.md#推奨実施順-s2j-webinar-同型localhost-自動テスト優先](./test-results.md#推奨実施順-s2j-webinar-同型localhost-自動テスト優先)。**段階1の HTTPS 行はなし** (スモークは常に localhost WP)。

### 仕様分割 {#status-spec-docs}

* [x] 正本が `docs/`、改訂案は `docs_mod/` ([governance/documentation_governance.md](./governance/documentation_governance.md))
* [x] CI: `docs-lint` ([`.github/workflows/docs-lint.yml`](../.github/workflows/docs-lint.yml))

### 配布 {#status-dist}

* [x] Vite4エントリ / 本番 `dist/` ([`vite.config.ts`](../vite.config.ts))
* [x] `npm run dist` + [`tools/dist/make-dist-zip.mjs`](../tools/dist/make-dist-zip.mjs)
* [x] ルート `readme.txt` (textlint 対象外)
* [x] CI: [`.github/workflows/dist-zip.yml`](../.github/workflows/dist-zip.yml)
* [x] `composer.json` — 初版 Service 非依存

### REST、永続化 {#status-rest}

正本: [rest_api_spec.md](./rest_api_spec.md) / [persistence_spec.md](./persistence_spec.md)

#### プラグイン実装

* [x] `register_post_meta` — `_s2j_webinar_source_qr` ([`includes/Meta/Registrar.php`](../includes/Meta/Registrar.php))
* [x] `GET …/document` — メタマージ + `provider` 解決 ([`RestController.php`](../includes/RestController.php))
* [x] `POST …/draft` / `apply` — カタログ guard **503**、検証 **4xx**、apply **200** + QR PNG ([§QR 反映](#status-qr-apply))
* [x] ドメイン正規化 — Webinar 定義順 `channels` ([`includes/Domain/document.php`](../includes/Domain/document.php))
* [x] 固定 channel id 廃止 — ソース名正本 ([`includes/Constants.php`](../includes/Constants.php))
* [ ] `sanitize_callback` — draft と **同一** カタログ厳格 ([persistence_spec.md](./persistence_spec.md))
* [ ] `save_post` 再正規化 (CoverArt 型) — 任意
* [x] `provider` 解決 — [`includes/Domain/provider.php`](../includes/Domain/provider.php)
* [x] サーバー定義 REST — [`includes/Domain/catalog.php`](../includes/Domain/catalog.php)
* [x] Apply — PNG 生成・`ready`・`qr_attachment_id` ([§QR 反映](#status-qr-apply))
* [x] Import マージ (PHP 純関数) — `s2j_wsq_merge_tracking_items`
* [ ] meta `sanitize` / apply 以外の経路ガード文書化完了

#### Test (PHPUnit) — Local 可のみ

* [x] [`DocumentLogicTest.php`](../tests/phpunit/DocumentLogicTest.php) — 純 PHPUnit

#### Test (Integration REST) — Local 可のみ

* [x] draft **200** / catalog **503** ([`RestDocumentTest.php`](../tests/Integration/RestDocumentTest.php))
* [x] apply **200** + PNG メディア・中央 icon 合成・ invalid icon **400** (`qr_generation_failed`) — 同上
* [x] **Local MySQL** — `npm run test:unit:php:local` **PASS** (blockpluginbase, 2026-10-16: 3a **4** tests / 3b **6** tests, **31** assertions; `.env.tests` + GatherPress 実体)
* [ ] 4xx (invalid registration URL 等) — 任意拡張

### 管理 UI {#status-admin-ui}

正本: [admin_ui_spec.md](./admin_ui_spec.md)

#### プラグイン実装

* [x] Fill **Edit source tracking / QR** ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] 4ステップ / **Save draft** / **Apply** (英語 + i18n)
* [x] localize — `s2jWebinarActive` / 定義・取り込み パス template ([`GutenbergMount.php`](../includes/Editor/GutenbergMount.php))
* [x] 定義 GET → 行マージ ([`src/domain/document.ts`](../src/domain/document.ts))
* [x] `catalogReady` — 保存ボタン、readOnly ([admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))
* [x] Import 成功 → **POST draft** 必須
* [ ] QR プレビュー (ステップ3)
* [ ] Apply 成功時 UI (501時ダイアログ維持は確認済み想定)
* [ ] dirty / Notice `code` 分岐の網羅
* [ ] handoff から同一モーダル ([§handoff](#status-handoff))

#### Test (UI 骨格) — Local 可のみ

* [x] Studio — Fill + モーダル (`catalogReady` false は仕様どおり) (2026-10-14探索)
* [x] 順3— Local 手動 UI PASS (U1– U12、U10は CI 代替) (2026-10-14。[test-results.md](./test-results.md#最新結果))

#### Test (保存・カタログ) — Local 可 (E2・ E4)

* [ ] Local / Studio — `catalogReady` true で **Save draft** 200 ([E2](#status-external) 定義 REST + [E4](#status-external))

#### Test (取り込み UI) — 要 HTTPS (E3)

* [ ] Import → **POST draft** — 実 `tracking-sources` ([E1](#status-external)・[E3](#status-external))

### カタログ {#status-catalog}

正本: Webinar [source_qr_tracking_definitions_rest_spec.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/source_qr_tracking_definitions_rest_spec.md)

* [x] UI — Webinar 有効時のみ定義 GET
* [x] UI — 失敗 / 空 `sources` → Notice + 保存 disabled
* [x] サーバー — `rest_do_request` + **503** 正規化
* [x] TS — 定義順行・未知 id を Import で増やさない
* [x] `_s2j_webinar_id` 未設定 — Webinar **400** → UI `catalogReady` false (仕様)
* [x] 親 spec — 定義 / 取り込み REST **仕様確定** (Webinar 実装は別 repo)

#### Test — Local 可 (E2) / UI 未成立は Local 可のみ

* [x] `_s2j_webinar_id` 未設定 → `catalogReady` false (Studio: 2026-10-14)
* [ ] 定義 REST200 + `sources.length >= 1` → `catalogReady` true ([E2](#status-external))

### 取り込み {#status-import}

* [x] `GET …/tracking-sources` — クライアント ([admin_ui_spec.md](./admin_ui_spec.md))
* [x] マージ → 必須 **POST draft**
* [x] 未登録 `source_name` — 警告のみ
* [x] 初版 Import 表示 — `provider` **zoom** + `_s2j_webinar_id`

#### Test — 要 HTTPS (E3)

* [ ] 実 Zoom 登録 URL の取り込み → draft 永続化 ([E1](#status-external)・[E2](#status-external)・[E3](#status-external))

### QR 反映 {#status-qr-apply}

* [x] PHP PNG パイプライン — `chillerlan/php-qrcode` + GD ([`includes/Domain/qr.php`](../includes/Domain/qr.php))
* [x] `status: ready` + メディア `qr_attachment_id`
* [x] Apply **200** 経路 ([`RestController.php`](../includes/RestController.php))
* [x] `qr_not_implemented` **501** からの移行 (wp-env Integration)
* [x] 中央 icon 合成 (`icon_attachment_id`) — GD 白プレート + 約22% スケール ([`includes/Domain/qr.php`](../includes/Domain/qr.php))

#### Test (Apply API) — Local 可 (E6)

* [x] Apply **200** + PNG メディア — [`RestDocumentTest.php`](../tests/Integration/RestDocumentTest.php) (CI `phpunit-wp-env` 2026-10-14、**Local MySQL** 2026-10-16 — 中央 icon 含む)

### handoff {#status-handoff}

* [ ] Webinar hook 名確定 — [plugin_spec.md#webinar-handoff-同一モーダル](./plugin_spec.md#webinar-handoff-同一モーダル)
* [ ] open リスナ + 同一 `MaintenanceModal`

#### Test — Local 可 (E5)

* [ ] Webinar から同一モーダル open — hook 確定後 ([E5](#status-external))

### 品質・スモーク {#status-quality}

#### Test (自動・ smoke) — Local 可のみ

* [x] 段階3a — `composer test:unit` + CI [`phpunit-pure`](../.github/workflows/phpunit.yml)
* [x] 段階1— [`tests/smoke-local.sh`](../tests/smoke-local.sh) (Local / Studio `WP_PATH`)
* [x] 段階2— [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) + [`studio/README.md`](../studio/README.md)
* [x] `.wp-env.json` + [`phpunit.wp-env.xml.dist`](../phpunit.wp-env.xml.dist)
* [x] CI — js-lint / docs-lint / dist-zip / phpunit (pure + wp-env job)
* [x] 段階3b — Integration green (PR [#1](https://github.com/stein2nd/s2j-webinar-source-qr/pull/1) CI `phpunit-wp-env` 2026-10-14; **Local MySQL PASS** 2026-10-16 — `npm run test:unit:php:local`, blockpluginbase / `wordpress_test`)

#### Test (記録) — Local 可のみ

* [x] [test-results.md](./test-results.md) — localhost 可能 / 不可能の表
* [x] 段階1PASS — Local (2026-10-14)
* [x] 段階2一部 — Studio プラグイン・ Fill ・モーダル (2026-10-14。**catalog 未成立**は想定内)
* [x] 段階3b ローカル — [test-results.md#最新結果](./test-results.md#最新結果) 2026-10-16 行 (3a + 3b Local MySQL)
* [ ] 段階1–2— [test-results.md](./test-results.md) 最新結果表の定期更新 (Studio Blueprint 行など)

#### Test (外部結合) — Local 可 (E2/E4/E6) / 要 HTTPS (E1/E3)

* [ ] [E2](#status-external) 定義 REST — Webinar 実装後、localhost で Pass 記録
* [ ] [E3](#status-external) 取り込み — **要 HTTPS**
* [ ] Webinar [§ Source QR 関連](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md) — 定義 REST **実装** 完了後に連携 E2E

## 外部結合ゲート {#status-external}

**100% 分母外。** Pass は [test-results.md](./test-results.md) に記録。

**Local 可 / 要 HTTPS:** **Local** は Local / Studio の `http://localhost` 等。**要 HTTPS** は公衆 HTTPS (ステージング、トンネル等) が必要 ([test-results.md](./test-results.md))。

| # | 内容 | Local 可 / 要 HTTPS |
| --- | --- | --- |
| E1 | 前提 — S2J Webinar OAuth / `_s2j_webinar_id` | **要 HTTPS** (Zoom `4700`。Connect 省略可) |
| E2 | 定義 REST200 + `sources.length >= 1` | **Local 可** (Webinar 実装 + テスト用 ID があれば。未実装時は不可) |
| E3 | 取り込み `tracking-sources` → draft 保存 | **要 HTTPS** (実 Zoom 登録 URL。E1、E2後) |
| E4 | Save draft / Apply (catalogReady true) | **Local 可** (E2成立時。Apply PNG + 中央 icon は [§QR 反映](#status-qr-apply)) |
| E5 | handoff 同一モーダル | **Local 可** (hook 確定 + Webinar 側実装後) |
| E6 | QR PNG Apply end-to-end | **Local 可** — Integration + stub 定義で **PASS** (Local MySQL 2026-10-16; 実 Webinar 定義 REST は [E2](#status-external)) |

## 補足

* **実装％** はプラグイン検証可能項のみ。OAuth、実 Zoom API は Pass 記録があっても % に含めない。
* 初版 **Service なし** — 取り込み写像済み JSON のみ ([plugin_spec.md](./plugin_spec.md))。
* S2J Webinar Survey と同型 UX。メタは `_s2j_webinar_source_qr` (Survey 文書とは別)。

## 実装ロードマップ (到達順)

1. ~~仕様 `docs/` 正本~~ → ~~REST / カタログ / UI 骨格~~ → ~~QR Apply (§ QR 反映)~~ → [§推奨テスト実施順](#status-test-order) **順0–2** (3a、smoke、**3b 緑 — CI 2026-10-14 + Local MySQL 2026-10-16**) → **順3** Studio UI → 実装: meta `sanitize`、UI 残 → **順4** (E2/E4; **E6** は stub Integration 済) → **順5** HTTPS (E1/E3) → handoff (E5)
