# S2J Webinar Source QR - ドキュメンテーション・ガバナンス

本ドキュメントは、ユーザー向け説明と仕様の **整合ルール** を定義します。

共通の要約は [wp-plugin-spec / SPECS.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/SPECS.md) §4です。ひな型は [DOCUMENTATION_GOVERNANCE_TEMPLATE.md](https://github.com/stein2nd/wp-plugin-spec/blob/main/docs/DOCUMENTATION_GOVERNANCE_TEMPLATE.md) です。

## 設計意図 (ゴール)

README と `docs/` の説明・命名が、実装と Webinar / Service 契約からずれないようにします。「正本はどれか」「完了したら何を freeze するか」を、毎回説明しなくてよくします。

## 1. Source of Truth

| 対象 | 正本 |
| --- | --- |
| Source Tracking **取り込み**写像 (`zoom` Adapter) | [s2j-webinar-service](https://github.com/stein2nd/s2j-webinar-service) の `docs/` (本プラグイン v0.0.1は Composer で require しない) |
| Webinar handoff、OAuth、取り込み GET、ソース定義 REST | [S2J Webinar `docs/`](https://github.com/stein2nd/s2j-webinar/tree/main/docs) (運用素材 v0.0.2) |
| メタキー、文書形、REST `code` (本プラグイン) | 本 `docs/` (`data_dictionary.md` / `persistence_spec.md` / `rest_api_spec.md` 等) |
| 4ステップ UI、Webinar REST 契約 (クライアント) | [admin_ui_spec.md](../admin_ui_spec.md) |
| 横断用語 (registration_url 等) | [S2J Webinar glossary_operational.md](https://github.com/stein2nd/s2j-webinar/blob/main/docs/glossary_operational.md) + [specs.md#用語](../specs.md#用語) |
| 最短手順・インストール | ルート `README.md` |
| 統合の見取り図 | [plugin_spec.md](../plugin_spec.md) (要約。細部の正本ではない) |
| 索引 | [specs.md](../specs.md) |

矛盾時は **規則・契約の正本** (取り込み写像は Service、定義・ GET は Webinar、メタ・保存・ UI は本 `docs/`) を直し、README と要約を追随させます。

## 2. 用語

* コード名・キー名は [data_dictionary.md](../data_dictionary.md) に従う。横断語は [specs.md#用語](../specs.md#用語) の表を優先する。
* 仕様書上の製品名は **S2J ウェビナー・ソース追跡 QR**。管理 UI の英語製品名は [overview.md](../overview.md) の UI 表記規約に従う (テキストドメイン `s2j-webinar-source-qr`)。
* **ソース名** と行 `id`、取り込み `source_name`、定義 REST の `source_name` を混同しない。初版は **S2J Webinar のソース定義一覧** が行の正本である (固定 `web` / `sns` / `other` だけに仕様を書き戻さない)。
* **文書 `status`** (`draft` / `ready`) は `_s2j_webinar_source_qr` 専用。レコード `_s2j_webinar_status` と混同しない。
* **パネル文書** / **メタ文書** の呼び分けは [persistence_spec.md](../persistence_spec.md) に従う。
* **本プラグイン REST** (`s2j-webinar-source-qr/v1`) と **Webinar REST** (`s2j-webinar/v1` の `tracking-sources` / `tracking-source-definitions` 等) を同じ節にまとめて書かない。役割分担は [rest_api_spec.md](../rest_api_spec.md) と [admin_ui_spec.md](../admin_ui_spec.md)。
* **`catalogReady`** は UI 導出 state の名前である。Webinar 定義 GET の成功と `sources` 非空を表す ([specs.md#用語](../specs.md#用語))。
* Webinar 定義 REST の **4xx** (例: 未連携) と、本プラグイン保存時の **503** (`catalog_unavailable`) を混同しない ([rest_api_spec.md](../rest_api_spec.md))。
* 画面の表示文言は、「適切なメッセージ文を表示する」と書き、国際化関数の経由を前提とする。
* FOP は初出で Functional Object-Oriented Programming と展開する。

## 3. Lint

* `@s2j/docs-linter` を SoT とする。
* ローカルと CI で同じ `npm run lint:docs` を使う。
* 対象は `README.md`、`CHANGELOG.md`、および `docs/` / `docs_mod/` 配下の仕様 Markdown である。

## 4. 分割ルール

* 新規仕様は [specs.md](../specs.md) のレイヤーに分類する。
* 必要になるまで、他製品にある層 (OpenAPI / SRE 等) を増やさない。
* Zoom パス・ OAuth token ・取り込み生 JSON を本プラグイン仕様に複製しない。Webinar / Service にリンクする。

## 5. 改訂フロー (`docs_mod` → `docs`)

* 確定仕様の正本は **`docs/`** である。
* 大きな改訂案は **`docs_mod/`** で起草し、レビュー・合意のあと **`docs/`** に反映する。
* 依存リポジトリからのリンクは、可能な限り **`docs/`** を指すように保つ。
* 小さな typo は **`docs/`** を直接直してよい。
* v0.0.1で **docs/** に写経したあとも **`docs_mod/README.md`** は削除せず、次改訂の入口として存置してよい ([../../docs_mod/README.md](../../docs_mod/README.md))。

## 6. イニシアチブ証跡 (archive)

実装・改修の区切りごとに、人が読める合格証跡を残します。PHPUnit / Jest の HTML / XML など機械成果物とは別です (カバレッジは `/coverage/` 等。gitignore)。

### 6.1. 命名

| 種類 | フォルダー | いつ使うか |
| --- | --- | --- |
| 実装イニシアチブ | `docs/archive/impl-<slug>/` | まだない能力を初めて入れる |
| 改修イニシアチブ | `docs/archive/mod-<slug>/` | すでに `docs/` にある仕様・振る舞いを変える |
| 仕様リライトの旧正本 | `docs/archive/spec-<slug>/` または簡潔な英文名 | 公開正本の一式を置き換えた場合の旧版 |

`<slug>` は短い kebab-case とする。SemVer はフォルダー名に入れず、`status.md` や CHANGELOG に書く。

### 6.2. 三点セット

作業中は `docs_mod/` に置き、完了時に archive にフリーズする。

| ファイル | 書くこと | 書かないこと |
| --- | --- | --- |
| `modification.md` | 目的、スコープ内外、タスク表、完了定義 | 長い仕様本文 (正本は `docs/`) |
| `status.md` | 進捗サマリー、完了条件のチェック、残ギャップ | 生のカバレッジ HTML |
| `test-results.md` | 仕様条件 ID ごとの PASS / WARN / FAIL | 生ログの丸貼り |

### 6.3. ライフサイクル

1. **開始** … `docs_mod/` に三点セットを置く (必要なら仕様ドラフトも)
2. **作業** … 合意した仕様は都度 `docs/` に反映する。証跡三点は `docs_mod/` で更新する
3. **フリーズ** … 下記をすべて満たしたら `docs/archive/impl-<slug>/` または `docs/archive/mod-<slug>/` にコピーして固定する
   * 該当する `docs/` 仕様が最新である
   * `test-results.md` に FAIL がない (WARN は理由付きのみ可)
   * CHANGELOG の unreleased に一行ある
4. **フリーズ後** … archive 配下は原則変更しない。続きは新しい `mod-*` (または `impl-*`) を切る
5. **片付け** … `docs_mod/` の三点は削除してよい (ディレクトリと [../../docs_mod/README.md](../../docs_mod/README.md) は残してよい)

### 6.4. いつ切るか

* コードまたは契約が動くイニシアチブでは、三点セットを切る。
* docs だけの整備 (正本写経のみ) は archive 任意とする。必要なら `spec-docs-v0-0-1` 等で旧版を freeze する。
* 初回実装は大きくまとめず、縦に切ってよい (例: `impl-skeleton` → `impl-qr-apply` → `impl-catalog-ui`)。

### 6.5. 全体 status との関係

* [status.md](../status.md) は製品全体の「いま」である。
* `docs/archive/.../status.md` は、そのイニシアチブ完了時点の凍結である。
* 全体 status に、進行中の `docs_mod/` と直近 archive へのリンクを短く置いてよい。進捗表の二重管理はしない。

索引は [archive/README.md](../archive/README.md) である。

## 7. `docs/archive/README.md` に置くもの

* 本ガバナンスへのリンク (規則の正本はこちら)
* 命名の一行要約 (`impl-` / `mod-` / 仕様リライト)
* 完了イニシアチブの索引表 (日付・フォルダー・種別・一言)

機械成果物は、archive に置かない。

## 8. `docs_mod/README.md` に置くもの

* 確定正本は **`docs/`** であること
* 起草中の仕様ドラフトと、進行中イニシアチブ三点の置き場であること
* 完了後は archive に freeze し、三点は削除してよいこと
* 詳細へのリンク (本ファイルと [../archive/README.md](../archive/README.md))
