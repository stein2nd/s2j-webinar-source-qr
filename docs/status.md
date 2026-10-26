# S2J Webinar Source QR - 実装状況

最終更新: 2026-10-19

索引は [specs.md](./specs.md) です。版の取り扱いは [specs.md#版の取り扱い](./specs.md#版の取り扱い) です。仕様改訂案は [docs_mod/](../docs_mod/) を参照。

## 仕様書 (参照元)

* [specs.md](./specs.md) — 索引
* [plugin_spec.md](./plugin_spec.md) — 統合見取り図
* [overview.md](./overview.md) / [admin_ui_spec.md](./admin_ui_spec.md) / [persistence_spec.md](./persistence_spec.md) / [rest_api_spec.md](./rest_api_spec.md) / [data_dictionary.md](./data_dictionary.md)
* [governance/documentation_governance.md](./governance/documentation_governance.md) — ドキュメント運用
* [archive/README.md](./archive/README.md) — 完了イニシアチブ (まだなし)
* [test-results.md](./test-results.md) — 段階1–3、外部結合の記録
* [cross_plugin_remaining_matrix.md](./cross_plugin_remaining_matrix.md) — Webinar / Source QR / Survey の未完了 `[ ]` 分類 (Local / Webinar 待ち / HTTPS+Zoom)

**非 HTTPS 連携 (本 repo [E2](#status-external) / Webinar [E7](https://github.com/stein2nd/s2j-webinar/blob/main/docs/status.md#status-external)) — クローズ2026-10-19:** Studio `:8888` post **8** (`_s2j_webinar_id=studio-u7`)、definitions stub **無効**。Webinar **P2a** 本番ルート **200** → UI **`catalogReady` true** → Save draft **200** (順4 **E1– E2**)。拡張: Apply **準備完了**、再 **開く…** で URL 保持 (`08`)、Webinar サマリー **`Document: ready`** (`09`)。証跡 [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/) (01–09)、[test-results §最新結果](./test-results.md#最新結果)。*任意:* 再 open 時モーダルヘッダーが **準備完了** になる1枚 (`08` は Step1 **ドラフト** 表示のまま — メタ **ready** は `09` と一致)。

## 機能一覧

**完了条件列:** 100% 到達用チェックリスト。`[x]` = 満たし済み、`[ ]` = 未。正本 spec は各§の見出しリンク。

**実装％** は、各§の **プラグイン検証可能** 項 (`[x]` / その全数) のみ。Zoom OAuth、実 Webinar ID 等は [§外部結合ゲート](#status-external) に分離し、Pass 記録があっても % には入れない。§内の **Test (… E1/E3等)** 行と **任意** 行は分母に含めない (例: [§カタログ](#status-catalog) の E2、[§取り込み](#status-import) の E3)。

| 機能名 | 実装済み/未実装 | 実装％ | 完了条件 (100%) |
| --- | --- | --- | --- |
| 仕様 (`docs/`) | 確定 | — | [§仕様分割](#status-spec-docs) |
| 配布 (Vite / dist / zip) | 実装済み | 100 | [§配布](#status-dist) (5/5) |
| メタ + REST `document` | 実装済 | 100 | [§REST・永続化](#status-rest) (12/12) |
| 管理 UI (Gutenberg 4ステップ) | 実装済 | 100 | [§管理 UI](#status-admin-ui) (17/17) |
| Webinar 定義 REST + `catalogReady` | 実装済み | 100 | [§カタログ](#status-catalog) (6/6) |
| 取り込み → draft | 実装済み | 100 | [§取り込み](#status-import) (4/4) |
| Apply + QR PNG | 実装済み | 100 | [§QR 反映](#status-qr-apply) (6/6) |
| Webinar handoff (同一モーダル) | 実装済 | 100 | [§handoff](#status-handoff) (3/3) |
| テスト、CI (段階1–3) | 一部 | 100 | [§品質・スモーク](#status-quality) (12/12) |

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
| 0 | 3a | Local 可のみ | `npm run test:unit:php:unit-only`、`npm run lint` / **`npm run lint:php`** (`prelint:php` → Composer dev 依存) |
| 1 | 1 | Local 可のみ (CLI) | Local Site Shell / Studio — `npm run test:smoke:local` |
| 2 | 3b | Local 可 (MySQL) / CI (Docker) | `npm run test:unit:php:local` または CI `phpunit-wp-env` ([test-results.md](./test-results.md#docker-なしの開発環境)) |
| 3 | 2 | Local 可のみ | Studio Blueprint + 手動 UI (Fill、モーダル) |
| 4 | 1–3 | Local 可 (E2/E4/E6) | 定義 REST 後 Save draft **200**、Apply **400/503/200** ([順4 E1–E3](./test-results.md#順4-オプション-local-可-e2e4)) |
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
* [x] `POST …/document/preview` — Apply 同一レンダラー、JSON `data_url`、**メタ非更新** (カタログ **503なし**)
* [x] ドメイン正規化 — Webinar 定義順 `channels` ([`includes/Domain/document.php`](../includes/Domain/document.php))
* [x] 固定 channel id 廃止 — ソース名正本 ([`includes/Constants.php`](../includes/Constants.php))
* [x] `sanitize_callback` + **`update_post_metadata` ガード** — draft と同一カタログ厳格 ([`includes/Meta/Guard.php`](../includes/Meta/Guard.php)、[persistence_spec.md](./persistence_spec.md))
* [x] `save_post` 再正規化 (CoverArt 型) — [`SavePost.php`](../includes/Meta/SavePost.php)、[`s2j_wsq_renormalize_meta_on_post_save()`](../includes/Domain/document.php)
* [x] `provider` 解決 — [`includes/Domain/provider.php`](../includes/Domain/provider.php)
* [x] サーバー定義 REST — [`includes/Domain/catalog.php`](../includes/Domain/catalog.php)
* [x] Apply — PNG 生成・`ready`・`qr_attachment_id` ([§QR 反映](#status-qr-apply))
* [x] Import マージ (PHP 純関数) — `s2j_wsq_merge_tracking_items`
* [x] meta `sanitize` / apply 以外の経路ガード文書化 — [persistence_spec §保存経路](./persistence_spec.md#保存経路) + Guard 実装

#### Test (PHPUnit) — Local 可のみ

* [x] [`DocumentLogicTest.php`](../tests/phpunit/DocumentLogicTest.php) — 純 PHPUnit

#### Test (Integration REST) — Local 可のみ

* [x] draft **200** / catalog **503** ([`RestDocumentTest.php`](../tests/Integration/RestDocumentTest.php))
* [x] apply **200** + PNG メディア・中央 icon 合成・ invalid icon **400** (`qr_generation_failed`) — 同上
* [x] preview **200** `data_url` + メタ不変 / 空 URL **400** — 同上
* [x] **Local MySQL** — `npm run test:unit:php:local` **PASS** (2026-10-18: 3a **5** / 3b **14**; **2026-10-19** 3b **+1** `save_post` — 計 **20** tests; `.env.tests` + GatherPress 実体)
* [x] 4xx — `invalid_registration_url` / `invalid_document` (draft、apply) + meta ガード ([`RestDocumentTest.php`](../tests/Integration/RestDocumentTest.php))
* [x] `save_post` — カタログ再整列 (orphan 行削除) — `test_save_post_renormalizes_channels_to_catalog` (**2026-10-19**)

### 管理 UI {#status-admin-ui}

正本: [admin_ui_spec.md](./admin_ui_spec.md)

#### プラグイン実装

* [x] Document Settings パネル **S2J Webinar Source QR** + **Open…** ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] 4ステップ / **Save draft** / **Apply** (英語正本 + `@wordpress/i18n`)
* [x] Gutenberg **翻訳配信** — `wp_set_script_translations` + `languages/*-{hash}.json` (正本 `.po` → **`npm run makei18n`**)。**訳済み `msgstr` のみ JED** — [`languages/README.md`](../languages/README.md)、[`tools/i18n/maps/source-qr-ja-gutenberg.php`](../tools/i18n/maps/source-qr-ja-gutenberg.php)。初版 **ja**
* [x] **S2J Webinar 依存 UI** — パネル **3 状態** (未インストール / 無効 Notice、有効 → Open…)。`s2jWebinarInstalled` / `webinarInstallGuideUrl` / `webinarActivateUrl` ([`catalog.php`](../includes/Domain/catalog.php)、[`admin_ui_spec.md#s2j-webinar-有効-クライアント](./admin_ui_spec.md#s2j-webinar-有効-クライアント))
* [x] Gutenberg — **postId / postType 確定後** 描画、`core/editor` 文字列 store ([admin_ui_spec.md#gutenberg-エディター安定化](./admin_ui_spec.md#gutenberg-エディター安定化))
* [x] localize — 上記 + 定義・取り込み path template / **`webinarHandoffOpenHook`** ([`GutenbergMount.php`](../includes/Editor/GutenbergMount.php)、[`Constants.php`](../includes/Constants.php))
* [x] 定義 GET → 行マージ ([`src/domain/document.ts`](../src/domain/document.ts))
* [x] `catalogReady` — 保存ボタン、readOnly ([admin_ui_spec.md#保存可否-初版厳格](./admin_ui_spec.md#保存可否-初版厳格))
* [x] Step2 **モジュール color** — ColorPicker + Hex 双方向、プレビュー-only (`!catalogReady`) ([admin_ui_spec.md#21-モジュール色-module-color](./admin_ui_spec.md#21-モジュール色-module-color))
* [x] Step2 **Center icon** — MediaUpload → `icon_attachment_id` ([admin_ui_spec.md#22-中央アイコン-center-icon](./admin_ui_spec.md#22-中央アイコン-center-icon))
* [x] Step2 **Output size** — `size_px` 入力 UI ([admin_ui_spec.md#23-出力サイズ-output-size](./admin_ui_spec.md#23-出力サイズ-output-size))
* [x] Step2 **レイアウト** — コンパクト警告帯、モジュール color+Hex / Center icon / Output size の1行 ([admin_ui_spec.md#2-qr-見た目-全ソース共通](./admin_ui_spec.md#2-qr-見た目-全ソース共通))
* [x] Import 成功 → **POST draft** 必須
* [x] Step1Import 条件 — [`IMPORT_TRACKING_SUPPORTED_PROVIDERS`](../src/domain/document.ts) + `providerSupportsTrackingImport()` (初版 `zoom` のみ)。説明文は解決済み `provider` を動的表示
* [x] QR プレビュー (ステップ3) — **プレビュー REST** + `QrPreviewStep` ([rest_api_spec.md#post-event_post_iddocumentpreview](./rest_api_spec.md#post-event_post_iddocumentpreview))
* [x] Apply **失敗時** UI — メタ非 ready ・モーダル維持・ Notice (Studio U10 **400** 2026-10-17; **503** は Integration)
* [x] Apply **成功時** UI —200後 **適用しました。** + **準備完了** (stub **2026-10-18** / **P2a 実定義2026-10-19** post **8** — [test-results.md#最新結果](./test-results.md#最新結果))
* [x] dirty / Notice **`code` 分岐** — [`src/domain/restNotice.ts`](../src/domain/restNotice.ts) + Vitest [`tests/node/restNotice.test.ts`](../tests/node/restNotice.test.ts)
* [x] handoff から同一モーダル — **E5** Pass (**2026-10-19** Studio — [test-results.md#最新結果](./test-results.md#最新結果))

#### Test (UI 骨格) — Local 可のみ

* [x] Studio — Document Settings パネル + モーダル (`catalogReady` false は仕様どおり) (2026-10-14探索)
* [x] 順3— Local 手動 UI **U1– U12** (2026-10-14; U10の **503** は Integration 代替)
* [x] Studio **`wsq-smoke`** — **U6– U7, U10** + **ja UI** (`makei18n` 後・`WPLANG=ja`) (2026-10-17。[test-results.md#最新結果](./test-results.md#最新結果)、[studio/README.md](../studio/README.md))
* [x] Studio **`:8888` + definitions stub** — **U6– U7**, **E4** Save draft、**E3** Apply ブラウザ (**2026-10-18**, post **8**)

#### Test (保存・カタログ) — Local 可 (E2、E4)

* [x] Studio — `catalogReady` true で **Save draft** / **Apply** 200 (stub **2026-10-18**；**P2a 実定義2026-10-19** post **8** — 順4 **E2– E3** ブラウザ — [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/))

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
* [x] 定義 REST `200` + `sources.length >= 1` — **実 Webinar P2a** (**2026-10-19** Studio `:8888` post **8**、stub 無効 — [test-results.md](./test-results.md))。**ブラウザ** Step1 **WEB/SNS/OTHER**、Save draft、Apply、**再 open + 開く…** で URL 保持、Webinar サマリー **ready** — 証跡 [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/) (01–09)

### 取り込み {#status-import}

* [x] `GET …/tracking-sources` — クライアント ([admin_ui_spec.md](./admin_ui_spec.md))
* [x] マージ → 必須 **POST draft**
* [x] 未登録 `source_name` — 警告のみ
* [x] 初版 Import 表示 — `providerSupportsTrackingImport` (正本 slug 一覧 [`document.ts`](../src/domain/document.ts)) + `_s2j_webinar_id`

#### Test — 要 HTTPS (E3)

* [ ] 実 Zoom 登録 URL の取り込み → draft 永続化 ([E1](#status-external)・[E2](#status-external)・[E3](#status-external))

### QR 反映 {#status-qr-apply}

* [x] PHP PNG パイプライン — `chillerlan/php-qrcode` + GD ([`includes/Domain/qr.php`](../includes/Domain/qr.php))
* [x] `status: ready` + メディア `qr_attachment_id`
* [x] Apply **200** 経路 ([`RestController.php`](../includes/RestController.php))
* [x] `qr_not_implemented` **501** からの移行 (wp-env Integration)
* [x] 中央 icon 合成 (`icon_attachment_id`) — GD 白プレート + 約22% スケール ([`includes/Domain/qr.php`](../includes/Domain/qr.php))

#### Test (Apply API) — Local 可 (E6)

* [x] Apply **200** + PNG メディア — [`RestDocumentTest.php`](../tests/Integration/RestDocumentTest.php) (CI `phpunit-wp-env` 2026-10-14; **Local MySQL** 2026-10-16–17— 中央 icon、invalid icon **400** 含む、integration **9/9**)

### handoff {#status-handoff}

* [x] Webinar hook 名確定 — **`s2j_webinar.open_source_qr`** ([plugin_spec.md#webinar-handoff-同一モーダル](./plugin_spec.md#webinar-handoff-同一モーダル))
* [x] open リスナ + 同一 `MaintenanceModal` — **`s2jWebinarActive` 時のみ** [`MaintenanceModalHost`](../src/gutenberg/index.tsx)、`wp-hooks` 依存 ([`GutenbergMount.php`](../includes/Editor/GutenbergMount.php))

#### Test — Local 可 (E5)

* [x] Webinar から同一モーダル open — **Edit source tracking / QR** → **開く…** と同一モーダル (**2026-10-19** — [E5](#status-external)、[test-results.md](./test-results.md))

### 品質・スモーク {#status-quality}

#### Test (自動、smoke) — Local 可のみ

* [x] 段階3a — `composer test:unit` + CI [`phpunit-pure`](../.github/workflows/phpunit.yml)
* [x] 段階1— [`tests/smoke-local.sh`](../tests/smoke-local.sh) (Local / Studio `WP_PATH`)
* [x] 段階2— [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) + [`studio/README.md`](../studio/README.md)
* [x] `.wp-env.json` + [`phpunit.wp-env.xml.dist`](../phpunit.wp-env.xml.dist)
* [x] CI — js-lint (**TS/SCSS** + **`lint-php` job**、PHP **8.3**) / docs-lint / dist-zip / phpunit (pure + wp-env job、PHP **8.3**)
* [x] ローカル **`npm run lint:php`** — PHPStan + PHPCS + WPCS (**2026-10-17**、`prelint:php` で dev 依存自動 install)
* [x] Gutenberg TS — [`src/types/wordpress-block-editor.d.ts`](../src/types/wordpress-block-editor.d.ts)、`PluginDocumentSettingPanel` は `@wordpress/editor` ([`src/gutenberg/index.tsx`](../src/gutenberg/index.tsx))
* [x] 段階3b — Integration green (PR [#1](https://github.com/stein2nd/s2j-webinar-source-qr/pull/1) CI `phpunit-wp-env` 2026-10-14; **Local MySQL PASS** 2026-10-16 / **2026-10-17** — `npm run test:unit:php:local`, integration **9/9**)

#### Test (記録) — Local 可のみ

* [x] [test-results.md](./test-results.md) — localhost 可能 / 不可能の表
* [x] 段階1PASS — Local (2026-10-14)
* [x] 段階2一部 — Studio プラグイン・ Fill ・モーダル (2026-10-14。**catalog 未成立**は想定内)
* [x] 段階3b ローカル — [test-results.md#最新結果](./test-results.md#最新結果) 2026-10-16 / **2026-10-17** 行 (3a + 3b Local MySQL、**9/9** integration)
* [x] 段階2Studio — [test-results.md#最新結果](./test-results.md#最新結果) **2026-10-17** `wsq-smoke` 行 (U6– U7, U10, **U13** ja)
* [x] Blueprint **ゼロから** Studio 再現 — **2026-10-18** `s2j_webinarsouseqr_new_2026_1018_smoke-test-blueprint` (`:8888`)。smoke + **U1– U12 / U13 (ja)** ([test-results.md#最新結果](./test-results.md#最新結果))

#### Test (外部結合) — Local 可 (E2/E4/E6) / 要 HTTPS (E1/E3)

* [x] [E2](#status-external) 定義 REST + **`catalogReady` / Save draft / Apply** — **2026-10-19** Studio `:8888` post **8** (stub 無効 — 証跡01–09 [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/))
* [ ] [E3](#status-external) 取り込み — **要 HTTPS** (実 `tracking-sources` / **P2b**)
* [x] Webinar 横断 **E7** — **2026-10-19** (本 repo **E2** と同セッション。[Webinar test-results](https://github.com/stein2nd/s2j-webinar/blob/main/docs/test-results.md#最新結果))

## 外部結合ゲート {#status-external}

**100% 分母外。** Pass は [test-results.md](./test-results.md) に記録。

**Local 可 / 要 HTTPS:** **Local** は Local / Studio の `http://localhost` 等。**要 HTTPS** は公衆 HTTPS (ステージング、トンネル等) が必要 ([test-results.md](./test-results.md))。

| # | 内容 | Local 可 / 要 HTTPS |
| --- | --- | --- |
| E1 | 前提 — S2J Webinar OAuth / `_s2j_webinar_id` | **要 HTTPS** (Zoom `4700`。Connect 省略可) |
| E2 | 定義 REST200 + `sources.length >= 1` → `catalogReady` | **Local 可** — **Pass2026-10-19** (P2a 本番ルート、post **8** / `studio-u7`) |
| E3 | 取り込み `tracking-sources` → draft 保存 | **要 HTTPS** (実 Zoom 登録 URL。E1、E2後) |
| E4 | Save draft / Apply (`catalogReady` true) | **Local 可** — **Pass2026-10-19** (P2a 実定義 UI + stub **2026-10-18**) |
| E5 | handoff 同一モーダル | **Local 可** — **Pass2026-10-19** (Studio `:8888`) |
| E6 | QR PNG Apply end-to-end | **Local 可** — Integration PASS + **2026-10-19** P2a 実定義 Apply UI ([E2](#status-external)) |

## 補足

* 3プラグイン横断の未完了分類: [cross_plugin_remaining_matrix.md](./cross_plugin_remaining_matrix.md) (Webinar / Survey に同期コピーあり)。
* **実装％** はプラグイン検証可能項のみ。OAuth、実 Zoom API は Pass 記録があっても % に含めない。
* 初版 **Service なし** — 取り込み写像済み JSON のみ ([plugin_spec.md](./plugin_spec.md))。
* S2J Webinar Survey と同型 UX。メタは `_s2j_webinar_source_qr` (Survey 文書とは別)。

## 実装ロードマップ (到達順)

1. ~~仕様 `docs/` 正本~~ → ~~REST / カタログ / UI 骨格~~ → ~~QR Apply (§ QR 反映)~~ → [§推奨テスト実施順](#status-test-order) **順0–2** (3a、smoke、lint: php、**3b 緑** 2026-10-17) → **順3** Studio UI (**U1– U12** + **`wsq-smoke` U6– U7/U10/ja** 2026-10-17) → 実装: meta `sanitize`、Apply 成功 UI → **順4** (**E2** P2a REST **2026-10-19** / E4ブラウザ済) → ~~**handoff + E5** (**2026-10-19**)~~ → **順5** HTTPS (E1/E3取り込み)
