# WordPress Studio (段階2)

[S2J Webinar Source QR](../README.md) 用の **WordPress Studio Blueprint** です。環境の共通定義は [wp-plugin-spec: SMOKE_AND_WP_ENVIRONMENTS](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SMOKE_AND_WP_ENVIRONMENTS.md) を参照してください。

## Blueprint の選び方

| Blueprint | 用途 |
| --- | --- |
| [`smoke-test-blueprint.json`](./smoke-test-blueprint.json) | **本プラグイン** 向け。GatherPress を自動インストール。**S2J Webinar Source QR** (とフル UI なら **S2J Webinar**) は **手動配置** |
| [S2J Webinar `studio/smoke-test-blueprint.json`](https://github.com/stein2nd/s2j-webinar/blob/main/studio/smoke-test-blueprint.json) | Webinar 本体 + OAuth / 同期 / 定義 REST のスモーク。Source QR は **別途** 本リポジトリをコピー |

Source QR の **catalogReady** 検証には S2J Webinar の定義 REST が必要です。Studio だけでは Webinar を含めないため、フル結合は Local または Webinar Blueprint 併用を推奨します。

## 手順 (本 Blueprint)

1. `npm run dist` 済み (または `npm run build:production`) のプラグインを Studio サイトの `wp-content/plugins/s2j-webinar-source-qr` にコピー。
2. Studio → Upload Blueprint → [`smoke-test-blueprint.json`](./smoke-test-blueprint.json)。
3. プラグインを有効化。管理画面を開くと mu-plugin が下書きイベント1件を追加 ([`mu-plugin-smoke-seed.php`](./mu-plugin-smoke-seed.php) と Blueprint 内 `writeFile` を同期)。
4. (任意) **S2J Webinar** を同じサイトに配置・有効化し、イベントに `_s2j_webinar_id` を設定して定義 REST / Import を手動確認。
5. `WP_PATH='<WordPress ルート>' npm run test:smoke:local` が PASS。
6. **順3 (手動 UI):** [docs/test-results.md §第2段階](../docs/test-results.md#第2段階-wordpress-studio-順3--手動-ui) の **U1– U12** をブラウザで実施 → [最新結果](../docs/test-results.md#最新結果) に記録。
7. **U7 (Step3 プレビュー):** Webinar 定義 REST が無いサイトでは [`mu-plugin-definitions-stub.php`](./mu-plugin-definitions-stub.php) を `wp-content/mu-plugins/` にコピー（`wsq-smoke` 用）。イベント **post 12** (`S2J Source QR U7 smoke`) に `_s2j_webinar_id` とソース行 URL（Web あり / SNS 空）を用意。**Studio デスクトップ:** サイト `wsq-smoke` → 上記イベント編集、または auto-login  
   `http://localhost:8885/studio-auto-login?redirect_to=%2Fwp-admin%2Fpost.php%3Fpost%3D12%26action%3Dedit`  
   → 設定 → **S2J Webinar Source QR** → **Open…** → **Next** ×2 → Step3（Web QR + SNS placeholder + 英文 intro）。プラグイン rsync 後はエディタで **⌘⇧R**（Gutenberg JS キャッシュ）。証跡例: [docs/screenshots/u7-step3-preview-studio-2026-10-17.png](../docs/screenshots/u7-step3-preview-studio-2026-10-17.png)。

Local でも **同じ U1– U12** でよい (Blueprint 再現性の確認が Studio の追加価値)。

## 日本語 UI (Gutenberg)

翻訳の**正本は `.po` のみ**。PHP 用 **`.mo`** と JS 用 **`.json`** は **`npm run makei18n`**（`wp i18n make-mo` + `make-json`）で同じ `.po` から生成します。

1. Poedit 等で `languages/s2j-webinar-source-qr-ja.po` を更新
2. リポジトリルートで **`npm run makei18n`**（要 [WP-CLI](https://wp-cli.org/)。`build:production:gutenberg` 後は postbuild で **JSON だけ** 自動更新）
3. **`languages/` ごと** Studio へコピー（`.mo` と **`*-{hash}.json`** を含む）。`dist/` だけ rsync では Gutenberg は英語のまま
4. サイト言語 **日本語** (`WPLANG=ja` 等)。編集画面を **⌘⇧R**

`mu-plugin-smoke-seed.php` を編集したら Blueprint の `writeFile` データも更新してください (MediaLibrary Date Corrector / S2J Webinar と同じ運用)。
