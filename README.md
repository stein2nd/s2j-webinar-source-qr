# S2J ウェビナー・ソース追跡 QR

GatherPress イベント向けの **ソース追跡 URL + QR コード** コンパニオン・プラグインです (仕様書上の名称: **S2J ウェビナー・ソース追跡 QR**)。

* 仕様 (正本・**本プラグイン v0.0.1**): [docs/](./docs/)
* 仕様改訂案 (存置): [docs_mod/](./docs_mod/)
* 連携、handoff (**S2J Webinar 仕様 v0.0.2**): [s2j-webinar / docs/operational_material_index.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/operational_material_index.md)

**v0.0.1骨格:** PHP (`includes/`)、イベント編集 Fill (`src/gutenberg/`)。`npm run build:dev:gutenberg` でアセットを生成します。実装は [docs/](docs/) 正本に追随します。

**テスト (3段階):** [docs/test-results.md](./docs/test-results.md) — Local スモーク (`npm run test:smoke:local`)、WordPress Studio ([studio/](./studio/))、PHPUnit **`composer test` (Docker 不要)**。wp-env 統合 (`npm run phpunit:wp-env`) は **Docker 必須** — ない場合は PR の CI `phpunit-wp-env` で確認。
