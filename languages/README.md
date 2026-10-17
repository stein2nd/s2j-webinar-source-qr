# 翻訳ファイル

## 正本は `.po` だけ

翻訳者が編集するのは **`s2j-webinar-source-qr-ja.po`**（テンプレートは `s2j-webinar-source-qr.pot`）です。PHP 用 **`.mo`** も Gutenberg 用 **JED `.json`** も、**同じ `.po` から自動生成**します（別ファイルを手書きしない）。

| 生成物 | コマンド | 実行時に読む層 |
| --- | --- | --- |
| `.mo` | `wp i18n make-mo` | PHP `__()` — `load_plugin_textdomain` |
| `*-{hash}.json` | `wp i18n make-json` | JS `__()` — `wp_set_script_translations` |

**一括:** リポジトリルートで

```bash
npm run makei18n
```

（内部で `makemo` → `makejson`。要 [WP-CLI](https://wp-cli.org/)）

Poedit で `.po` を保存したあとも、**`npm run makei18n`** を実行すれば `.mo` と `.json` が揃います（Poedit 単体の `.mo` 保存と二重になっても問題ありません）。

## 開発フロー

1. 英文変更後: `npm run build:production:gutenberg` → `npm run makepot` → Poedit で `.po` 更新
2. **`npm run makei18n`**
3. `languages/` ごと配布先・Studio にコピー

`npm run build:production:gutenberg` の **postbuild** では **`makejson` のみ**（ビルド直後の JS 参照と `.po` の msgid を JSON に反映）。`.mo` も更新したいときは明示的に **`makei18n`**。

## ランタイムの注意

WordPress は **JS 翻訳を `.po` から直接読みません**。必ず **JSON** を配信します。`.po` から賄う＝**ビルド／CI で JSON を生成して同梱する**、という意味になります。
