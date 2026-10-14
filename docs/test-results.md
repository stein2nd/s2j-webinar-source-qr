# テスト戦略と結果記録

本プラグインの検証方針です。Local / Studio / PHPUnit の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を正とし、本ファイルは **このリポジトリ固有の手順** です。

## 段階的な導入 (3段階)

| 段階 | 目的 | 主なツール | 本リポジトリ |
| --- | --- | --- | --- |
| **1** | WordPress 上で最低限動くこと (結合スモーク) | Local、Site Shell、WP-CLI | `tests/smoke-local.sh` / `npm run test:smoke:local` |
| **2** | テストサイト構成の再現 (GatherPress 同梱) | **WordPress Studio**、Blueprints | [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) |
| **3** | PHP 自動テスト、PR 時のゲート | PHPUnit、GitHub Actions | `composer test` (純ロジック)。WP 統合は `npm run phpunit:wp-env` |

段階1・2は実 WordPress、段階3の **3a** は WordPress を起動しない純 PHPUnit です。

**依存:** 段階1・2では **[GatherPress](https://github.com/stein2nd/gatherpress)** を **有効化必須** です (wp.org 版では **GatherPress alpha** も必要な場合があります)。

## 推奨実施順 (S2J Webinar 同型・localhost 自動テスト優先)

**段階 (1/2/3)** と **環境 (localhost / HTTPS)** は別軸です。先に **localhost で完結する自動テスト** を揃え、HTTPS は後回しにします。

| 順 | 段階 | 環境 | 内容 | 本リポジトリ |
| --- | --- | --- | --- | --- |
| **0** | **3a** | **Local 可のみ** (WP 不要) | 純 PHPUnit・lint・CI と同型 | `composer test` / `npm run lint` |
| **1** | **1** | **Local 可のみ** (CLI) | Local **Site Shell** と Studio ルートの **`WP_PATH`** で同じスクリプト | `npm run test:smoke:local` |
| **2** | **3b** | **Local 可のみ** (Docker) | wp-env 統合・REST 登録など | `npm run phpunit:wp-env` |
| **3** | **2** | **Local 可のみ** | Blueprint 再現 + (1) 再実行 + **手動** UI (Fill / モーダル) | [studio/](../studio/) |
| **4** | **1–3** | **Local 可 (E2/E4/E6)** | Webinar 定義 REST 成立後の Save draft・Apply **501/200** | [status.md §外部結合](./status.md#status-external) |
| **5** | **2–3** | **要 HTTPS** | OAuth (E1)・実 Zoom 取り込み (E3)・ステージング UI | [test-results.md 最新結果](#最新結果) に Pass 記録 |

**補足**

* **段階1に HTTPS 版は通常なし** — スモークは Local / Studio の WordPress に対する WP-CLI のみ。
* **順 0** は段階番号より先に回してよい (フィードバックが最も速い)。
* **順 3** の Studio は CLI スモークと役割が重なるため、**(1)(2) が緑のあと**で Blueprint 再現性とブラウザ確認に使う。
* HTTPS 行は **自動ゲート (CI) の必須条件にしない** — [status.md](./status.md) の実装％・外部結合ゲート参照。

### localhost で可能 / 不可能 (S2J Webinar と同型)

| 観点 | Local / Studio (`localhost`) | 備考 |
| --- | --- | --- |
| プラグイン有効化、REST ルート登録 | **可能** | 段階1 `smoke-local.sh` |
| Gutenberg Fill 表示 (GatherPress イベント編集) | **可能** (GatherPress 必須) | `dist/` ビルド必須 |
| `GET/POST …/document` (本プラグイン REST) | **可能** | 定義カタログ不要の GET |
| **`catalogReady` / Save draft / Apply** | **条件付き** | **S2J Webinar 有効** + 定義 REST 成功 + `sources.length >= 1` |
| Webinar **Import** (`tracking-sources`) | **条件付き** | 上記 + `_s2j_webinar_id` + `provider: zoom` |
| Zoom OAuth / 公衆 Webhook | **不可** (localhost) | Webinar 側と同様 HTTPS ステージングで検証 |
| QR PNG **Apply** (501 まで) | **可能** | パイプライン未実装時は **501** `qr_not_implemented` が期待 |

---

## 第1段階: Local (結合スモーク)

| # | 確認項目 | 期待 |
| --- | --- | --- |
| 1 | `composer install` + `npm run build:production` | `vendor/` と `dist/js/s2j-webinar-source-qr-gutenberg.js` |
| 2 | GatherPress 有効化 | エラーなく有効 |
| 3 | 本プラグイン有効化 | エラーなく有効 |
| 4 | GatherPress **イベント** 編集 | Fill **Edit source tracking / QR** |
| 5 | (任意) S2J Webinar 有効 + 定義 REST | Save draft が 200、Import 表示 |

```bash
cd wp-content/plugins/s2j-webinar-source-qr
export WP_PATH="/path/to/site/app/public"
npm run test:smoke:local
```

---

## 第2段階: WordPress Studio

1. Blueprint → [`studio/smoke-test-blueprint.json`](../studio/smoke-test-blueprint.json) ([`studio/README.md`](../studio/README.md))。
2. **dist ビルド済み** 本プラグインを手動インストール・有効化。
3. (フル UI) **S2J Webinar** を同サイトに追加するか、[Webinar Blueprint](https://github.com/stein2nd/s2j-webinar/blob/main/studio/smoke-test-blueprint.json) で別サイトを用意。
4. 段階1チェックリスト + `npm run test:smoke:local`。

---

## 第3段階: PHPUnit

### 3a. 純 PHPUnit (CI・ローカル)

```bash
composer install
composer test
# または
npm run test:unit:php:local
```

WordPress 非起動。`includes/Domain/document.php` の正規化・マージを検証します。

### 3b. wp-env 統合 (拡張)

```bash
npm install
npm run phpunit:wp-env
```

`.wp-env.json` で GatherPress + 本プラグインを読み込み、`tests/Integration/` を実行します。Local DB とは別コンテナです。

---

## 最新結果

| 日付 | 段階 | 環境 | 結果 | メモ |
| --- | --- | --- | --- | --- |
| 2026-10-14 | 3a | CI | PASS | `composer test` (PR #1) |
| 2026-10-14 | 3b | CI (wp-env) | PASS | `RestDocumentTest` draft 200 / 503 / apply 501 (PR #1) |
| 2026-10-14 | 1 | Local | PASS | 既存記録 |
| (未記録) | 2 | Studio | — | Blueprint + 手動 UI |
