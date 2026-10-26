# WordPress Studio (段階2)

[S2J Webinar Source QR](../README.md) 用の **WordPress Studio Blueprint** です。環境の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を参照してください。

## Blueprint の選び方

| Blueprint | 用途 |
| --- | --- |
| [`smoke-test-blueprint.json`](./smoke-test-blueprint.json) | **本プラグイン** 向け。GatherPress を自動インストール。**S2J Webinar Source QR** (とフル UI なら **S2J Webinar**) は **手動配置** |
| [S2J Webinar `studio/smoke-test-blueprint.json`](https://github.com/stein2nd/s2j-webinar/blob/main/studio/smoke-test-blueprint.json) | Webinar 本体 + OAuth / 同期 / 定義 REST のスモーク。Source QR は **別途** 本リポジトリをコピー |

Source QR の **catalogReady** 検証には **S2J Webinar 有効** + 定義 REST が必要です。Webinar なしで Source QR のみ有効のとき、イベント編集 **Document Settings** には **Install / Enable S2J Webinar** の Notice のみ ([admin_ui_spec.md#s2j-webinar-有効-クライアント](../docs/admin_ui_spec.md#s2j-webinar-有効-クライアント))。フル結合は Local または Webinar Blueprint 併用を推奨します。

## 手順 (本 Blueprint)

1. `npm run dist` 済み (または `npm run build:production`) のプラグインを Studio サイトの `wp-content/plugins/s2j-webinar-source-qr` にコピー。
2. Studio → Upload Blueprint → [`smoke-test-blueprint.json`](./smoke-test-blueprint.json)。
3. プラグインを有効化。管理画面を開くと mu-plugin が下書きイベント1件を追加 ([`mu-plugin-smoke-seed.php`](./mu-plugin-smoke-seed.php) と Blueprint 内 `writeFile` を同期)。
4. (任意) **S2J Webinar** を同じサイトに配置・有効化し、イベントに `_s2j_webinar_id` を設定して定義 REST / Import を手動確認。
5. `WP_PATH='<WordPress ルート>' npm run test:smoke:local` が PASS。
6. **順3 (手動 UI):** [docs/test-results.md §第2段階](../docs/test-results.md#第2段階-wordpress-studio-順3--手動-ui) の **U1– U12** をブラウザで実施 → [最新結果](../docs/test-results.md#最新結果) に記録。
7. **`catalogReady` true (U6–U7 / U10 / E4 / E2):** **S2J Webinar 有効** + 定義 REST が必要。
   * **実 Webinar (推奨・E2 / E7 / 順4):** **S2J Webinar** を symlink / コピーし、イベントに `_s2j_webinar_id` を設定。**definitions stub は置かない** (既に `s2j-wsq-studio-definitions-stub.php` がある場合は `.disabled` 等で無効化)。post **8** 開き直し → Step1 **WEB/SNS/OTHER** → Step3 プレビュー → **ドラフトを保存** → Step4 **適用** (**準備完了**) — 証跡 [studio-2026-1019-e7-real-p2a/](../docs/screenshots/studio-2026-1019-e7-real-p2a/) (**2026-10-19** `:8888`、`studio-u7`)。
   * **stub のみ (Webinar 未更新時):** [`mu-plugin-definitions-stub.php`](./mu-plugin-definitions-stub.php) を `wp-content/mu-plugins/s2j-wsq-studio-definitions-stub.php` にコピー。一括セットアップ:

   ```bash
   export WP_PATH='/Users/you/Studio/your-site'
   bash studio/setup-catalog-stub-site.sh
   ```

   イベント **S2J Source QR U7 smoke** (post ID はサイトごとに異なる — 例: `wsq-smoke` **12**、Blueprint 新規 `:8888` **8**) に `_s2j_webinar_id` = `studio-u7`、Web URL のみ / SNS 空。編集 URL 例 (`:8888`):

   `http://localhost:8888/wp-admin/post.php?post=8&action=edit`

   → **S2J Webinar Source QR** → **Open…** → Step2 レイアウト (**U6**)、Step3 プレビュー (**U7**)、Step4 Apply **400** (**U10**)、**ドラフトを保存** **200** 再 open (**E4**)。**⌘⇧R**  after JS 更新。証跡: [docs/screenshots/u7-step3-preview-studio-2026-10-17.png](../docs/screenshots/u7-step3-preview-studio-2026-10-17.png) (`wsq-smoke`)。

8. **E5 handoff:** 同一サイトで **S2J Webinar** + **Source QR** を有効化し、両方 `npm run build:production:gutenberg` 済みであること。GatherPress イベント編集 → Document Settings **Edit source tracking / QR** (Webinar) → Source QR **同一** 4 ステップモーダルが開く。**Open…** 経路と UI が同一であることを確認 → [test-results.md](../docs/test-results.md) に Pass 行。

Local でも **同じ U1– U12** でよい (Blueprint 再現性の確認が Studio の追加価値)。

## 日本語 UI (Gutenberg)

翻訳の**正本は `.po` のみ**。PHP 用 **`.mo`** と JS 用 **`.json`** は **`npm run makei18n`** (`wp i18n make-mo` + `make-json`) で同じ `.po` から生成します。

1. Poedit 等で `languages/s2j-webinar-source-qr-ja.po` を更新
2. リポジトリルートで **`npm run makei18n`** (要 [WP-CLI](https://wp-cli.org/)。`build:production:gutenberg` 後は postbuild で **JSON だけ** 自動更新)
3. **`languages/` ごと** Studio にコピー (`.mo` と **`*-{hash}.json`** を含む)。`dist/` だけ rsync では Gutenberg は英語のまま
4. サイト言語 **日本語** (`WPLANG=ja` 等)。編集画面を **⌘⇧R**

`mu-plugin-smoke-seed.php` を編集したら Blueprint の `writeFile` データも更新してください (MediaLibrary Date Corrector / S2J Webinar と同じ運用)。
