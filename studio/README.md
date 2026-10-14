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
5. `WP_PATH='<WordPress ルート>' npm run test:smoke:local` と [docs/test-results.md](../docs/test-results.md) の段階2チェックリスト。

`mu-plugin-smoke-seed.php` を編集したら Blueprint の `writeFile` データも更新してください (MediaLibrary Date Corrector / S2J Webinar と同じ運用)。
