# テスト戦略と結果記録

最終更新: 2026-10-24

本プラグインの検証方針です。

**実行環境:** PHP **v8.3 以降** (CI / wp-env / Local PHPUnit も同値。[WordPress 必須要件](https://ja.wordpress.org/about/requirements/))。Local / Studio / PHPUnit の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このリポジトリ固有の手順** です。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリ |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (結合スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` |
| **2** | テストサイト構成の再現 (GatherPress 同梱) | **WordPress Studio**、Blueprints | [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) |
| **3** | PHP 自動テスト、PR 時のゲート | PHPUnit、GitHub Actions | 単体: `composer test:unit`。統合: `npm run test:unit:php:local` (MySQL) または CI `phpunit-wp-env` |

段階1・2は実 WordPress。段階3の **3a (unit)** は WordPress DB 不要。段階 **3b (integration)** は MySQL + テスト用 DB、または Docker (wp-env) です。

**依存:** 段階1・2では **[GatherPress](https://github.com/stein2nd/gatherpress)** を **有効化必須** です (wp.org 版では **GatherPress alpha** も必要な場合があります)。

### Docker なしの開発環境

**3a (unit)** は PHP + Composer のみ。**3b (integration)** は **Local の MySQL** で `npm run test:unit:php:local` が可能 (初回のみ Subversion で WP テスト lib 取得)。**Docker (wp-env)** は CI 向けの代替経路です。

| 段階 | Docker なしでローカル実行 | 代替 |
| --- | --- | --- |
| **3a** | **可能** — `npm run test:unit:php:unit-only` (= `composer test:unit`) | CI `phpunit-pure` と同一 |
| **3b** | **可能** — `npm run test:unit:php:local` (要 MySQL テスト DB、`.env.tests`) | **PR / push 後の GitHub Actions `phpunit-wp-env`** (Docker)。Pass は [最新結果](#最新結果) に記録 |
| **1** | **可能** — Local / Studio + `npm run test:smoke:local` | `WP_PATH` を Site Shell または Studio ルートに |
| **2** | **可能** — Studio Blueprint + 手動 UI | CLI は段階1と同じ smoke |

日常のループ例: **`npm run test:unit:php:unit-only`** → (統合を触ったら) **`npm run test:unit:php:local`** または push 後 CI **`phpunit-wp-env`**。

## 推奨実施順 (S2J Webinar 同型・ localhost 自動テスト優先)

**段階 (1/2/3)** と **環境 (localhost / HTTPS)** は別軸です。先に **localhost で完結する自動テスト** をそろえ、HTTPS は後回しにします。

| 順 | 段階 | 環境 | 内容 | 本リポジトリ |
| --- | --- | --- | --- | --- |
| **0** | **3a** | **Local 可のみ** (WP 不要) | 単体 PHPUnit ・ TS/SCSS lint ・ **PHPStan/PHPCS** | `npm run test:unit:php:unit-only` / `npm run lint` / **`npm run lint:php`** |
| **1** | **1** | **Local 可のみ** (CLI) | Local **Site Shell** と Studio ルートの **`WP_PATH`** で同じスクリプト | `npm run test:smoke:local` |
| **2** | **3b** | **Local 可** (MySQL) / **CI** (Docker) | 統合 PHPUnit — `tests/Integration/` | `npm run test:unit:php:local` または PR の `phpunit-wp-env` |
| **3** | **2** | **Local 可のみ** | Blueprint 再現 + (1) 再実行 + **手動** UI (Fill / モーダル) | [studio/](../studio/) |
| **4** | **1–3** | **Local 可 (E2/E4/E6)** | 定義 REST 成立後 Save draft **200** ・ Apply **400/503/200** ([順4](#順4-オプション-local-可-e2e4)) | [status.md §外部結合](./status.md#status-external) |
| **5** | **2–3** | **要 HTTPS** | OAuth (E1) ・実 Zoom 取り込み (E3) ・ステージング UI | [test-results.md 最新結果](#最新結果) に Pass 記録 |

**補足**

* **段階1に HTTPS 版は通常なし** — スモークは Local / Studio の WordPress に対する WP-CLI のみ。
* **順0** は段階番号より先に回してよい (フィードバックが最も速い)。
* **順3** の Studio は CLI スモークと役割が重なるため、**(1) (2) が緑のあと**で Blueprint 再現性とブラウザ確認に使う。
* HTTPS 行は **自動ゲート (CI) の必須条件にしない** — [status.md](./status.md) の実装％・外部結合ゲート参照。

### localhost で可能 / 不可能 (S2J Webinar と同型)

| 観点 | Local / Studio (`localhost`) | 備考 |
| --- | --- | --- |
| プラグイン有効化、REST ルート登録 | **可能** | 段階1 `smoke-local.sh` |
| Gutenberg Fill 表示 (GatherPress イベント編集) | **可能** (GatherPress 必須) | `dist/` ビルド必須 |
| `GET/POST …/document` (本プラグイン REST) | **可能** | 定義カタログ不要の GET |
| **`catalogReady` / Save draft / Apply** | **条件付き** | **S2J Webinar 有効** + 定義 REST 成功 + `sources.length >= 1` |
| Webinar **Import** (`tracking-sources`) | **条件付き** | 上記 + `_s2j_webinar_id` + [`providerSupportsTrackingImport`](../src/domain/document.ts) (初版 **zoom**) |
| Gutenberg **日本語 UI** | **条件付き** | サイト **ja** + `languages/` に **`.mo` と JED `.json`** (`npm run makei18n`)。`.po` のみでは JS 未翻訳 ([`languages/README.md`](../languages/README.md)) |
| Zoom OAuth / 公衆 Webhook | **不可** (localhost) | Webinar 側と同様 HTTPS ステージングで検証 |
| QR PNG **Apply** | **可能** | 全行 URL + `catalogReady` で **200** + メディア。未充足 **400**、カタログ guard **503** (Integration + Studio U10)。旧 **501** `qr_not_implemented` は移行済み ([status.md §QR 反映](./status.md#status-qr-apply)) |

---

## 第1段階: Local (結合スモーク)

| # | 確認項目 | 期待 |
| --- | --- | --- |
| 1 | `composer install` + `npm run build:production` | `vendor/` と `dist/js/s2j-webinar-source-qr-gutenberg.js` |
| 2 | GatherPress 有効化 | エラーなく有効 |
| 3 | 本プラグイン有効化 | エラーなく有効 |
| 4 | GatherPress **イベント** 編集 | Document Settings パネル **S2J Webinar Source QR** |
| 5 | (任意) S2J Webinar 有効 + 定義 REST | Save draft が200、Import 表示 |

```bash
cd wp-content/plugins/s2j-webinar-source-qr
export WP_PATH="/path/to/site/app/public"
npm run test:smoke:local
```

---

## 第2段階: WordPress Studio (順3— 手動 UI)

**目的:** Blueprint 再現 + ブラウザで Gutenberg Document Settings / 4ステップモーダルを確認する。**`catalogReady` false** を含む **骨格 PASS (U1–U12)** が順3の完了条件。`catalogReady` true で Step2–3・Apply **400** を追加確認する場合は [`studio/README.md`](../studio/README.md) の定義 stub + post **12** (`wsq-smoke`, 2026-10-17)。Save draft **200** や Import は [順4 (E2/E4)](#順4-オプション-local-可-e2e4) (実 Webinar 定義 REST または E2 Pass 後)。

### 下準備

| # | 手順 |
| --- | --- |
| P1 | `npm run build:production` または `npm run dist:build` — `dist/js/s2j-webinar-source-qr-gutenberg.js` があること |
| P2 | **Studio:** Upload Blueprint → [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) ([`studio/README.md`](../studio/README.md)) |
| P2' | **Local 代替可:** GatherPress 有効 + 本プラグインを `wp-content/plugins/` に配置 (symlink 可)。Studio と **同じ手動表** でよい |
| P3 | 本プラグインを有効化。Studio / Local 管理画面を **1回** 開く (mu-plugin が下書きイベント **S2J Source QR smoke event** を追加) |
| P4 | CLI: `export WP_PATH='<サイト public ルート>'` → `npm run test:smoke:local` が **PASS** (段階1再確認) |
| P5 | **任意 (ja):** `.po` 更新後 **`npm run makei18n`** → **`languages/` ごと** サイトにコピー ([`studio/README.md`](../studio/README.md) §日本語 UI) |

**プラグイン配置 (Local 例):** Local サイト停止中は WP-CLI 不可。Site Shell から smoke を実行する。

### 手動 UI チェックリスト (順3必須)

GatherPress **イベント** 編集 (ブロックエディター) で実施。記録は [最新結果](#最新結果) に `段階2` 行を追加。

| # | 操作 | 期待 | PASS |
| --- | --- | --- | --- |
| U1 | Document Settings | パネル **S2J Webinar Source QR** (S2J Webinar 同型) が表示される | [x] |
| U2 | パネル内 **Open…** | モーダル **S2J Webinar Source QR** が開く | [x] |
| U3 | モーダル上部 | **Step1of 4** (または現在ステップ表示) | [x] |
| U4 | カタログ警告 | **Saving requires S2J Webinar and a source catalog.** — **コンパクト1行帯** (`Notice` 枠ではない)。`catalogReady` **false** 想定 | [x] |
| U5 | Step1→ **Next** | Step2に。ソース行 UI (定義未成立時は空または readOnly 想定) | [x] |
| U6 | Step2 | **1 行レイアウト:** Module color+Hex 横並び、Center icon、Output size。**Color** — 双方向；`!catalogReady` は `foreground_hex` 未確定。**Icon** — MediaUpload 常時操作可；`!catalogReady` は dirty なし。**Size** — `!catalogReady` は disabled | [x] |
| U7 | Step3 | ソース行ごと QR プレビュー (`POST …/document/preview`)。URL 空行は placeholder。Apply 同一描画の英文 intro。**任意:** 実機スキャンで登録 URL に遷移 | [x] |
| U8 | Step3→ **Next** | Step4— Apply 説明文言 | [x] |
| U9 | フッター | **Save draft** disabled。**Back** 使用可、Step4で **Next** disabled。**Apply** は Step4のみ primary 表示 ([admin_ui_spec.md](./admin_ui_spec.md) 上 `!catalogReady` では Apply も disabled — 見た目が primary のときは U10で確認) | [x] |
| U10 | Step4 **Apply** | 失敗時 **メタ ready にならない**・**モーダル開いたまま**・Notice。**503** (カタログ guard) / **400** `invalid_registration_url` (全行 URL 未充足) / Apply 成功時は **200** + ready (Integration 参照)。旧メモ **501** PNG 未実装は現行 Apply 実装と不一致 — CI で確認 | [x] |
| U11 | **Back** で Step1まで戻る | クラッシュ・白画面なし | [x] |
| U12 | モーダルを閉じる | イベント編集画面に戻れる (**X**) | [x] |
| U13 | **任意** — サイト言語 **ja** + `makei18n` 済み | モーダル・ボタンが **日本語** (例: `開く…` / `ドラフトを保存`)。正本 [`.po`](../languages/s2j-webinar-source-qr-ja.po) | [x] |

**U6:** `wsq-smoke` (2026-10-17) は定義 stub で **`catalogReady` true** — レイアウトと各コントロール表示・操作を目視。**`!catalogReady` 時** (Size disabled、色未確定、Save draft 不可) は Local 2026-10-14 探索 + [admin_ui_spec.md](./admin_ui_spec.md) / 実装 ([status.md](./status.md) Step2 行) と整合。

**U10:** `!catalogReady` で Apply が押せる場合は **503**。`catalogReady` だが **いずれかのソース行 URL が空** (U7 用 post 12: Web のみ) → **400** `One or more registration URLs are invalid.` — モーダル維持 ([u10-step4-apply-invalid-url-2026-10-17.png](./screenshots/u10-step4-apply-invalid-url-2026-10-17.png), Studio 2026-10-17)。**全行 URL 入力後の Apply** は Integration `test_apply_returns_200…` 参照。503 単体は **CI Integration** `catalog guard` テストで代替可。

**U13:** Studio `wsq-smoke` **2026-10-17** — `npm run makei18n` + `languages/` 同梱コピー後、post **12** 編集で Gutenberg モーダル **日本語表示** を目視確認 (`WPLANG=ja`)。**2026-10-18** Blueprint ゼロからサイト (`:8888`) でも **開く…** 〜 Step4・**ja UI** を再確認 ([証跡 `./screenshots/studio-2026-1018-zero-blueprint/`](#最新結果))。

### 順4 (オプション・ Local 可 E2/E4)

**S2J Webinar 有効** + イベント `_s2j_webinar_id` + 定義 REST200 (`sources.length >= 1`) のサイトでのみ。

| # | 操作 | 期待 |
| --- | --- | --- |
| E1 | モーダル open | Notice 消え、`catalogReady` true — Save draft 有効 |
| E2 | URL 入力 → **Save draft** | **200**、再 open で値保持 |
| E3 | Step4 **Apply** (全 URL 入力) | **200** + `status: ready` + 行ごと QR 添付 (Integration `test_apply_returns_200_and_ready_with_attachments`) |
| E5 | Webinar **Edit source tracking / QR** | Source QR **同一** `MaintenanceModal` が open (**Open…** と同 UI) |

**E2 (実 Webinar REST):** stub 無効 + Webinar P2a 有効サイトで `GET /s2j-webinar/v1/events/{post_id}/tracking-source-definitions` → **200**、`sources.length >= 1`。記録例 **2026-10-19** [最新結果](#最新結果) 行。

### 記録テンプレート

```markdown
| YYYY-MM-DD | 2 | Local / Studio | PASS / PARTIAL | U1–U12。任意 E1–E3。WP_PATH=… |
```

---

## 第3段階: PHPUnit

[S2J MediaLibrary Date Corrector](https://github.com/stein2nd/s2j-media-library-date-corrector) と同型。**wp-env / Docker は必須ではありません** (CI では引き続き利用可)。

### 3a. 単体 (PHP + Composer のみ)

```bash
composer install
npm run test:unit:php:unit-only
# 同等: composer test:unit
```

WordPress / MySQL 不要。`tests/phpunit/` (`includes/Domain/document.php` 等) を検証します。CI job **`phpunit-pure`** と同一です。

### 3b. 統合 (MySQL + 初回のみ Subversion)

**前提:** 開発サイト DB (`local` 等) とは **別名** のテスト DB。`.env.tests.example` を `.env.tests` にコピーして接続を合わせます。

```bash
composer install
npm run test:unit:php:local
```

`pretest:unit:php:local` で Composer install 後、`bin/phpunit-local.sh` が **unit → (必要なら) WP テスト lib インストール → integration** の順で実行します。初回は **MySQL** と **svn** (テスト lib 取得) が必要です。GatherPress (wp.org zip) と Webinar REST スタブはスクリプトが `tests/.wordpress/wp-content/plugins/` に用意します。

#### 統合 bootstrap (GatherPress / WP 6.7+)

[`tests/bootstrap.php`](../tests/bootstrap.php) は wp-env / ローカル MySQL の **同一** エントリです。GatherPress 有効化は [GatherPress #2426](https://github.com/GatherPress/gatherpress/issues/2426) の指摘に合わせ、次のとおりです。

| タイミング | 処理 |
| --- | --- |
| `plugins_loaded` (0) | `require_once` — `gatherpress/gatherpress.php` と Webinar REST スタブ。**`activate_plugin()` で GatherPress を有効化しない** (`after_setup_theme` 前のヘッダ翻訳 → WP 6.7+ Notice) |
| `init` (0) | `GatherPress\Core\Setup::get_instance()->activate_gatherpress_plugin( false )` — events 用 DB セットアップ (silent `activate_plugin` では activation hook が走らない問題の回避) |
| `init` (0) | Webinar スタブのみ `activate_plugin( …, true )` — `RestDocumentTest` の `deactivate_plugins()` 用 (スタブに Text Domain ヘッダなし) |

**統合スイート (現行):** `tests/Integration/` — **9** tests (`RestDocumentTest` **7** + `RestRoutesTest` **2**)。`npm run test:unit:php:local` は 3a **4** + 3b **9** = 計 **13** tests (**52** assertions)。

ローカル再確認 (2026-10-17): `npm run test:unit:php:local` — **WP 6.7+ Notice なし**、integration **9/9** PASS。

### 3b (代替) . wp-env — CI / Docker

```bash
npm install
npm run phpunit:wp-env
```

`.wp-env.json` で GatherPress + 本プラグイン + スタブを読み込み、**CI `phpunit-wp-env`** と同じ `tests/Integration/` を実行します。

---

## 最新結果

**手動 UI 証跡 (2026-10-17, Studio `wsq-smoke`):**

| ファイル | 内容 |
| --- | --- |
| [u7-step3-preview-studio-2026-10-17.png](./screenshots/u7-step3-preview-studio-2026-10-17.png) | Step3 — Web QR + SNS placeholder |
| [u7-step3-iphone-scan-example-com-2026-10-17.png](./screenshots/u7-step3-iphone-scan-example-com-2026-10-17.png) | iPhone スキャン → `example.com` |
| [u10-step4-apply-invalid-url-2026-10-17.png](./screenshots/u10-step4-apply-invalid-url-2026-10-17.png) | Step4 Apply → **400** 無効 URL Notice |

**Blueprint ゼロから (2026-10-18, Studio `s2j_webinarsouseqr_new_2026_1018_smoke-test-blueprint`, `:8888`):**

| ファイル | 内容 |
| --- | --- |
| [01-document-settings-ja.png](./screenshots/studio-2026-1018-zero-blueprint/01-document-settings-ja.png) | Document Settings — **S2J Webinar Source QR** + **開く…** (ja) |
| [02-step1-catalog-notice-ja.png](./screenshots/studio-2026-1018-zero-blueprint/02-step1-catalog-notice-ja.png) | Step1 — カタログ帯・ソース行なし・Import 説明 (`catalogReady` false) |
| [03-step2-layout-ja.png](./screenshots/studio-2026-1018-zero-blueprint/03-step2-layout-ja.png) | Step2 — Module color / Icon / Size 1 行 |
| [04-step4-apply-footer-ja.png](./screenshots/studio-2026-1018-zero-blueprint/04-step4-apply-footer-ja.png) | Step4 — **適用** primary・**ドラフトを保存** disabled |
| [05-modal-close-editor-ja.png](./screenshots/studio-2026-1018-zero-blueprint/05-modal-close-editor-ja.png) | モーダル閉じ → イベント編集 (`S2J Source QR smoke event`) |

**E5 handoff (2026-10-19, Studio `:8888`, ja UI):**

| ファイル | 内容 |
| --- | --- |
| [01-webinar-handoff-link-and-source-qr-open-ja.png](./screenshots/studio-2026-1019-handoff-e5/01-webinar-handoff-link-and-source-qr-open-ja.png) | **S2J Webinar** — **Edit source tracking / QR** と **S2J Webinar Source QR** — **開く…** (同一イベント Document Settings) |

**E7 / 順4 E1–E3 — 実 Webinar P2a (2026-10-19, Studio `:8888`, post **8** 開き直し, definitions stub **無効**, ja UI):**

| ファイル | 内容 |
| --- | --- |
| [01-webinar-document-settings-draft-channels3-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/01-webinar-document-settings-draft-channels3-ja.png) | **S2J Webinar** — `Document: draft · Channels: 3` (CLI draft 後)、**Edit source tracking / QR** |
| [02-step1-catalog-ready-web-sns-other-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/02-step1-catalog-ready-web-sns-other-ja.png) | Step1 — P2a 実定義 **3 行** (`example.com/e7-*` URL 入力済)、**ドラフトを保存** 有効 |
| [03-step3-preview-three-qr-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/03-step3-preview-three-qr-ja.png) | Step3 — 3 行 URL 後プレビュー QR |
| [04-step3-draft-saved-notice-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/04-step3-draft-saved-notice-ja.png) | **順4 E2** — **ドラフトを保存しました。** |
| [05-step4-apply-success-ready-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/05-step4-apply-success-ready-ja.png) | **順4 E3** — Step4 **適用** 直後 **適用しました。**・**文書のステータス: 準備完了** (P2a 実定義・3 行 URL) |
| [06-post-saved-sidebar-panels-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/06-post-saved-sidebar-panels-ja.png) | 投稿 **保存しました** 後 — Webinar サマリー + **開く…** |
| [07-post-reopen-sidebar-scroll-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/07-post-reopen-sidebar-scroll-ja.png) | post **8** 再 open — Document Settings (**S2J Webinar Source QR** パネル) |
| [08-step1-reopen-via-open-urls-persisted-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/08-step1-reopen-via-open-urls-persisted-ja.png) | **順4 E2** — **開く…** → Step1、`example.com/e7-*` **3 URL 保持** (P2a **WEB/SNS/OTHER**) |
| [09-post-saved-webinar-summary-ready-ja.png](./screenshots/studio-2026-1019-e7-real-p2a/09-post-saved-webinar-summary-ready-ja.png) | Apply 後・投稿 **保存しました** — Webinar サマリー **`Document: ready` · Channels: 3** (handoff 行) |

**Definitions stub + `catalogReady` true (2026-10-18, 同サイト `:8888`, post **8** `S2J Source QR U7 smoke`):**

| ファイル | 内容 |
| --- | --- |
| [01-step1-catalog-ready-ja.png](./screenshots/studio-2026-1018-catalog-stub/01-step1-catalog-ready-ja.png) | Step1 — Web/SNS 行・**ドラフトを保存** 有効 (stub 定義) |
| [02-step2-layout-u6-ja.png](./screenshots/studio-2026-1018-catalog-stub/02-step2-layout-u6-ja.png) | Step2 — **U6** 1 行レイアウト + Module color |
| [03-step3-preview-web-sns-placeholder-ja.png](./screenshots/studio-2026-1018-catalog-stub/03-step3-preview-web-sns-placeholder-ja.png) | Step3 — Web QR + SNS placeholder (Web URL のみ) |
| [04-step3-draft-saved-notice-ja.png](./screenshots/studio-2026-1018-catalog-stub/04-step3-draft-saved-notice-ja.png) | **E4** — **ドラフトを保存しました。** |
| [05-step3-preview-both-qr-ja.png](./screenshots/studio-2026-1018-catalog-stub/05-step3-preview-both-qr-ja.png) | Step3 — 両行 URL 後プレビュー |
| [06-step4-apply-success-ready-ja.png](./screenshots/studio-2026-1018-catalog-stub/06-step4-apply-success-ready-ja.png) | **E3 ブラウザ** — **適用しました。**・**準備完了** |

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-14 | 3a | CI | PASS | `composer test` (PR #1) |
| 2026-10-14 | 3b | CI (wp-env) | PASS | `RestDocumentTest` — draft **200** / catalog **503** / apply **200** (当時スイート) |
| 2026-10-14 | 1 | Local | PASS | `npm run test:smoke:local` (blockpluginbase) |
| 2026-10-14 | 2 | Local | PASS | 手動 **U1–U12** (`catalogReady` false 想定)。U10 **503** は Integration 代替 |
| 2026-10-16 | 3a | Local | PASS | `npm run test:unit:php:local` 内 — unit **4** tests, **13** assertions (WP 不要) |
| 2026-10-16 | 3b | Local (MySQL) | PASS | integration **6** tests (当時) — apply **200**、中央 icon、invalid icon **400** |
| 2026-10-17 | 3a+3b | Local (MySQL) | PASS | GatherPress [#2426](https://github.com/GatherPress/gatherpress/issues/2426) bootstrap — **Notice なし**; 3a **4** / 3b **9** = 計 **13** tests, **52** assertions |
| 2026-10-17 | 2 | Studio (`wsq-smoke`, `localhost:8885`) | PASS | **U6–U7, U10, U13** — 定義 stub・post **12**・JSX 修正後。U7 プレビュー + iPhone スキャン、U10 Apply **400**。**U13:** `makei18n` 後モーダル **ja** 表示。証跡 PNG は上表 (U13 は文言のみ) |
| 2026-10-17 | 0 | Local | PASS | **`npm run lint:php`** — PHPStan + PHPCS + WPCS (`prelint:php` → Composer dev 依存) |
| 2026-10-18 | 1 | Local (`blockpluginbase`) | PASS | `WP_PATH=…/app/public` — REST document route、プラグイン有効、dist チェック (meta Guard 等の変更後) |
| 2026-10-18 | 3a+3b | Local (MySQL) | PASS | meta Guard + 4xx Integration 拡張 — 3a **5** / 3b **14** = 計 **19** tests, **65** assertions |
| 2026-10-18 | 1+2 | Studio (`s2j_webinarsouseqr_new_2026_1018_smoke-test-blueprint`, `:8888`) | PASS | **Blueprint ゼロから** — GatherPress + **Alpha** (GitHub) → **ln** Source QR → smoke PASS。**U1–U12 + U13 (ja):** 開く… Step1–4・Back・**X** 閉じ。`catalogReady` false 想定どおり。証跡 [studio-2026-1018-zero-blueprint/](./screenshots/studio-2026-1018-zero-blueprint/) |
| 2026-10-18 | 2 / 4 | Studio (`:8888`, post **8**, definitions **stub**) | PASS | **U6–U7**、**E4** Save draft (再 open で URL 保持)、**E3** Apply **200** + **準備完了** (両行 URL 後)。定義は stub — **実 Webinar E2** は別。証跡 [studio-2026-1018-catalog-stub/](./screenshots/studio-2026-1018-catalog-stub/)。**U10 Apply 400** は wsq-smoke 2026-10-17 + Integration で代替可 |
| 2026-10-19 | E2 + 順4 E1–E3 | Studio (`:8888`, post **8**, stub **無効**) | PASS | **P2a** 定義 REST **200** (`sources` **3** / `studio-u7`)。**ブラウザ** Step1 **`catalogReady`** → Save draft → Apply **準備完了** → 再 **開く…** URL 保持 (`08`) → Webinar サマリー **`ready`** (`09`)。証跡 **01–09** [studio-2026-1019-e7-real-p2a/](./screenshots/studio-2026-1019-e7-real-p2a/)。WP_PATH=`~/Studio/s2j_webinarsouseqr_new_2026_1018_smoke-test-blueprint` |
| 2026-10-19 | handoff (実装) | Local | — | Source QR `addAction( s2j_webinar.open_source_qr )` → [`MaintenanceModalHost`](../src/gutenberg/index.tsx) |
| 2026-10-19 | **E5** handoff | Studio (`:8888`, ja UI) | PASS | Webinar **Edit source tracking / QR** → Source QR **同一** 4 ステップモーダル (**開く…** と同ダイアログ)。証跡 [studio-2026-1019-handoff-e5/](./screenshots/studio-2026-1019-handoff-e5/) |
