# LP 公開前チェック報告書 — KAMAKURA / 億万鳥者

- **実施日時**: 2026-09-05 21:41〜22:20 JST
- **対象**: 億万鳥者 5ページ（index / menu / course / terms / privacy）、KAMAKURA 3ページ（index / terms / privacy）＝ 計8ページ
- **基準**: Google Search Essentials（技術要件・主要ベストプラクティス）、Lighthouse（モバイル）、一般的なLP公開前チェック
- **本報告はチェックのみです。修正は一切行っていません。**

## 実行環境・使用ツール

| 項目 | 内容 |
|---|---|
| OS | macOS 26.5.2 (arm64) |
| Node.js | v24.16.0 / npm 11.13.0 |
| Playwright | 1.63.0（Chromium headless、390×844・モバイルエミュレーション） |
| Lighthouse | 13.4.1（**モバイル既定設定**、Google Chrome 経由、`--headless=new --disable-gpu`） |
| curl | 8.7.1 |
| 参照リポジトリ | `GROWX-Inc/lp-test-hama`（`okumanchoja/`）、`GROWX-Inc/lp-test-kamakura`（`main` および `feat/kamakura-prod-v2`） |

**Lighthouse は各ページ1回のみの実行**です。Performance 系スコアは実行ごとに変動します。

---

## サマリ

| 区分 | 件数 |
|---|---:|
| **要対応（公開前に直す）** | **2件** |
| **推奨（納品後でも可）** | **17件** |
| **問題なし** | **32件** |
| **未確認** | **5件** |

### 要対応 2件

| # | 内容 | 対象 | 作業者 |
|---|---|---|---|
| 要-1 | **億万鳥者の LP 3ページに利用規約・プライバシーポリシーへの導線が無い**。Google Maps Platform 利用規約はサイトからプライバシーポリシーへリンクすることを求めており、クチコミ表示機能を使う以上これは必須 | 億万 index / menu / course | Claude Code で自動可 |
| 要-2 | **`http://` が `https://` へリダイレクトしない**（200 を直接返す）。HSTS ヘッダも無し。同一コンテンツが http/https の両方で配信され、重複コンテンツとセキュリティの双方で問題 | ドメイン全体 | Xserver 上で手動（`.htaccess`） |

---

## A. 到達性・技術要件

### A-1. HTTPS 200 / http→https リダイレクト

| ページ | https | http | 判定 |
|---|---|---|---|
| 億万 index / menu / course / terms / privacy | 200 | **200（リダイレクトなし）** | **fail** |
| KAMAKURA index / terms / privacy | 200 | **200（リダイレクトなし）** | **fail** |

HTTPS 側は全8ページ 200 で **pass**。http 側は 301/302 を返さず本文を 200 で返します（`curl -I http://ifreagroup.co.jp/kamakura/` → `HTTP/1.1 200 OK`）。`Strict-Transport-Security` ヘッダもありません。

**→ 要-2。** 修正: ドキュメントルートの `.htaccess` に https へのリダイレクトを追加。**WordPress 本体と共用の `.htaccess` のため、Xserver 上で慎重に手動作業してください。**このLPだけの問題ではなくドメイン全体に影響します。

### A-2. noindex（**全ページ pass**）

| ページ | robots meta | 期待 | 判定 |
|---|---|---|---|
| 億万 index / menu / course | なし | インデックス許可 | pass |
| 億万 terms / privacy | `noindex` | noindex | pass |
| KAMAKURA index | なし | インデックス許可 | pass |
| KAMAKURA terms / privacy | `noindex` | noindex | pass |

### A-3. robots.txt（**pass**）

`https://ifreagroup.co.jp/robots.txt` は 200。`Disallow: /wp-admin/` のみで、**`/okumanchoja/` と `/kamakura/` は Disallow されていません**。`Sitemap: https://ifreagroup.co.jp/sitemap.xml` も宣言済み。

### A-4. lang / charset / viewport（**全8ページ pass**）

全ページで `<html lang="ja">`・`<meta charset>`・`<meta name="viewport">` を確認。

### A-5. title / description

**title は全8ページで固有かつ非空 — pass。**

| ページ | title | description |
|---|---|---|
| 億万 index | 億万鳥者 新宿本殿｜今までにない「億万長者」気分を味わえるアミューズメント居酒屋 | **なし** |
| 億万 menu | お品書き｜一億円で居酒屋建ててみた。億万鳥者 新宿本殿 | あり |
| 億万 course | コース｜一億円で居酒屋建ててみた。億万鳥者 新宿本殿 | あり |
| 億万 terms / privacy | 利用規約／プライバシーポリシー｜億万鳥者 新宿本殿 | なし（noindex のため不要） |
| KAMAKURA index | KAMAKURA \| かまくら個室ビストロ | **なし** |
| KAMAKURA terms / privacy | 利用規約／プライバシーポリシー｜かまくら個室ビストロ KAMAKURA | なし（noindex のため不要） |

**推奨-1: index 2ページに meta description を追加**（Lighthouse SEO でも指摘）。作業者: Claude Code で自動可。文案:

- 億万鳥者 `okumanchoja/index.html:513`（`</head>` の直前）
  ```html
  <meta name="description" content="全てのお客様を億万長者としておもてなし。Ａ５和牛の創作料理と非日常の演出が楽しめるアミューズメント居酒屋「億万鳥者 新宿本殿」。新宿三丁目から徒歩圏、ネット予約受付中。">
  ```
- KAMAKURA `index.html:393`（`</head>` の直前）
  ```html
  <meta name="description" content="かまくら個室ビストロ KAMAKURA。全席個室で愉しむ濃厚チーズ料理と創作ビストロメニュー。新宿店・錦糸町店で宴会・記念日・デートに。ネット予約受付中。">
  ```

### A-6. canonical（**全8ページ なし**）

**推奨-2: canonical を追加。** 特に A-1 の http/https 重複がある現状では重複コンテンツ対策として有効です。作業者: Claude Code で自動可。

```html
<link rel="canonical" href="https://ifreagroup.co.jp/okumanchoja/">
<link rel="canonical" href="https://ifreagroup.co.jp/kamakura/">
```

### A-7. OGP / favicon（**全8ページ なし**）

`og:title` / `og:description` / `og:image` / `og:url` および favicon が**全ページで未設定**です。

**推奨-3: OGP を追加。** LINE や X で URL を共有した際、サムネイル・タイトル・説明が一切出ません（URL がそのまま表示されます）。飲食店LPは LINE 共有の比率が高く、実利用への影響が大きい項目です。作業者: Claude Code で自動可（`og:image` 用の画像選定のみ要判断。既存の `img2/` や `assets/` から1枚指定すれば対応可能）。

**推奨-4: favicon を追加。** ブラウザタブ・ブックマークでの識別性。作業者: 画像用意は小野様側、設置は Claude Code。

### A-8. 見出し構造

| ページ | h1 数 | 判定 |
|---|---:|---|
| 億万 index | 1（「一億円で、居酒屋建ててみた。」） | pass |
| **億万 menu** | **0** | **fail** |
| **億万 course** | **0** | **fail** |
| 億万 terms / privacy | 1 | pass |
| KAMAKURA index | 1（「今日は、肩肘張らずに 愉しむ 夜。」） | pass |
| KAMAKURA terms / privacy | 1 | pass |

**推奨-5: 億万 menu / course に h1 が無い。** 最初の見出しが h2 です（`okumanchoja/menu.html:354`、`okumanchoja/course.html:380` の `<h2 class="rev">`）。修正案: この h2 を h1 に変更（CSS クラスはそのまま流用できるため見た目は変わりません）。作業者: Claude Code で自動可。

**推奨-6: 見出し順序の飛び（h2 → h4）。** Lighthouse `heading-order` で3ページが指摘。

| ページ | 該当 |
|---|---|
| 億万 menu | `<h4 class="mcat">`（h2 の次に h4） |
| 億万 course | `<h4 class="crs-sub">` |
| KAMAKURA index | h2 の次に h4 |

修正案: h4 → h3 に変更（CSS クラス名は据え置き、必要ならセレクタを `h3.mcat` に調整）。作業者: Claude Code で自動可。

### A-9. 画像の alt

| ページ | img 総数 | alt 属性なし | alt 空 | 判定 |
|---|---:|---:|---:|---|
| 億万 index | 12 | 0 | 1 | pass |
| **億万 menu** | 15 | 0 | **12** | **要検討** |
| 億万 course | 1 | 0 | 1 | pass |
| KAMAKURA index | 39 | 0 | 1 | pass |

**alt 属性が欠落している画像は0件**です（`alt=""` は装飾画像の正しい書き方）。

ただし **推奨-7: 億万 menu の料理写真12枚が `alt=""`** です（`okumanchoja/menu.html:375-377, 396-400, 454-456`）。例: `assets/menu_namaham.webp`、`assets_menu/meibutsu_rebanira_02.webp` など。これらは装飾ではなく**料理そのものを示す情報性のある画像**のため、`alt="Ａ５和牛の生ハム 殿サイズ"` のような説明を推奨します。作業者: Claude Code で自動可（alt 文言は既存の商品名 JS 定義から流用可能）。

なお各ページ1件ずつの「alt 空」は、JS テンプレート文字列内の `<img class="av" src="'+escAttr(au.photo)+'" alt="">`（gRev の投稿者アバター）で、**投稿者アイコンは装飾として正しく `alt=""`** です。**問題なし。**

### A-10. 混在コンテンツ（**全8ページ pass**）

`http://` で読み込む画像・スクリプト・CSS は**全ページ0件**。

---

## B. リンク・導線

### B-11. 全リンクの到達性・電話番号（**pass**）

全ページのリンクを抽出し、外部URLを実際に叩きました。

| URL | ステータス |
|---|---|
| ebica 3件（29221 / 29222 / 29223） | 200 |
| `maps.app.goo.gl` 2件 | 302 → 正しい店舗ページ |
| Instagram 3件 | 200 |
| Google ポリシー各種 4件 | 200 |

**404・リダイレクトループ・無効URLは0件。**

**電話番号（小野様回答との照合）**

| 対象 | LP の値 | 小野様回答 | 判定 |
|---|---|---|---|
| 億万鳥者 | `tel:0367099757`（= 03-6709-9757） | 03-6709-9757 | **pass** |
| KAMAKURA 新宿 | テキスト表記 `03-6380-5053`（tel: リンクなし） | 03-6380-5053 | 番号は pass |
| KAMAKURA 錦糸町 | テキスト表記 `03-6666-9950`（tel: リンクなし） | 03-6666-9950 | 番号は pass |

**推奨-8: KAMAKURA に `tel:` リンクが1件も無い。** 電話番号は本文テキストとしては正しく載っていますが、タップで発信できません。スマホ経由の予約導線として機会損失であり、`tel_click` の計測も発生しません（D-22 参照）。作業者: Claude Code で自動可。

### B-12. 予約リンクの遷移先（**pass**）

遷移先のページタイトルで確認しました。

| リンク | 遷移先タイトル | 判定 |
|---|---|---|
| 億万 `.../29223?affiid=sc01` | 【公式】一億円で居酒屋建ててみた。億万鳥者 新宿本殿 ネット予約 | **pass** |
| KAMAKURA 新宿 `.../29221?affiid=mail-magazine` | 【公式】かまくら個室ビストロKAMAKURA 新宿店 ネット予約 | **pass** |
| KAMAKURA 錦糸町 `.../29222?affiid=sc01` | 【公式】かまくら個室ビストロKAMAKURA 錦糸町店 ネット予約 | **pass** |

**推奨-9: `affiid` パラメータが不統一。** KAMAKURA 新宿だけ `affiid=mail-magazine`、他2件は `affiid=sc01` です。ebica 側の流入元集計が「メルマガ経由」に混ざる可能性があります。**意図的かどうか小野様に確認が必要**です。作業者: 判断は小野様側、修正は Claude Code で自動可。

### B-13. target="_blank" と rel="noopener"

| ページ | `_blank` | うち noopener あり | 判定 |
|---|---:|---:|---|
| 億万 index / menu / course | **0** | — | 下記参照 |
| 億万 terms | 2 | 2 | pass |
| 億万 privacy | 4 | 4 | pass |
| KAMAKURA index | 10 | 10 | pass |
| KAMAKURA terms | 2 | 2 | pass |
| KAMAKURA privacy | 4 | 4 | pass |

**`rel="noopener"` の欠落は0件 — pass。**

**推奨-10: 億万鳥者の LP 3ページは外部リンクが `target="_blank"` を持たず、同一タブで遷移します。** ebica 予約ページと Instagram が該当します（リンクは JS が `CONFIG` から生成、`okumanchoja/index.html:1030` 付近）。予約ページへ移動すると LP から離脱するため、別タブ推奨です。`_blank` を付ける場合は `rel="noopener"` も併せて必要です。作業者: Claude Code で自動可。

### B-14. 規約・プライバシーへの導線

| ページ | terms / privacy へのリンク | 判定 |
|---|---|---|
| **億万 index** | **なし** | **fail** |
| **億万 menu** | **なし** | **fail** |
| **億万 course** | **なし** | **fail** |
| KAMAKURA index | `terms.html` / `privacy.html` | **pass** |

**→ 要-1。** Google Maps Platform 利用規約はサイトからプライバシーポリシーへのリンクを求めており、クチコミ表示を行う以上は必須です。

またこの導線は**機能面でも必要**です。`gr/reviews-proxy.php:70` は次の fail-closed 判定を持ちます。

```php
if (API_KEY === '' || TERMS_URL === '' || PRIVACY_URL === '' || COUNTER_DIR === '') {
```

修正案: `okumanchoja/index.html:722` の `<small class="f-note">© IFREADINING Inc.</small>` の直前に、KAMAKURA と同じ形の導線を追加。menu / course のフッターも同様。作業者: **Claude Code で自動可**（KAMAKURA で実績のある実装をそのまま流用できます）。

```html
<div class="flinks"><a href="terms.html">利用規約</a><span>|</span><a href="privacy.html">プライバシーポリシー</a></div>
```

なお **terms / privacy ページ自身にはもう一方へのリンクがありません**（両ブランド共通）。規約ページ同士の相互リンクは慣例的にあると親切です。**推奨-11**。作業者: Claude Code で自動可。

### B-15. 画像・CSS・JS の読み込み（**全ページ pass**）

| ページ | 自サイト資産 | 4xx/5xx | 外部資産 | 4xx/5xx |
|---|---:|---:|---:|---:|
| 億万 index | 134 | **0** | 154 | **0** |
| 億万 menu | 19 | **0** | 85 | **0** |
| 億万 course | 5 | **0** | 80 | **0** |
| KAMAKURA index | 117 | **0** | 48 | **0** |

**欠落0件。** `frames*/` のフレーム画像・`img2/`・`assets_menu/` を含め全て 200 です。

初回計測で `ERR_ABORTED` が数件出ましたが、内訳は GA4 `/g/collect` ビーコンと YouTube のテレメトリのみで、**ページ離脱時にビーコンが中断される正常な挙動**です。資産の読み込み失敗ではありません。

---

## C. 表示・品質（Lighthouse モバイル）

### C-16. スコア

目安：Accessibility・Best Practices・SEO は90以上、Performance は70以上。

| ページ | Performance | Accessibility | Best Practices | SEO |
|---|---:|---:|---:|---:|
| 億万 index | **55** | 93 | 96 | 92 |
| 億万 menu | **55** | 93 | 100 | 100 |
| 億万 course | **55** | 96 | 100 | 100 |
| 億万 terms | 93 | 97 | 100 | **54** ※ |
| 億万 privacy | 93 | 97 | 100 | **54** ※ |
| KAMAKURA index | **55** | 100 | 96 | **83** |
| KAMAKURA terms | 93 | 97 | 100 | **54** ※ |
| KAMAKURA privacy | **59** | 97 | 100 | **54** ※ |

※ **terms / privacy の SEO 54 は「意図どおり」です。** 減点の主因は `Page is blocked from indexing`＝`noindex` で、A-2 の設計判断そのものです。**問題なし。**

**Accessibility・Best Practices は全8ページで基準（90）を達成。**

#### Performance 未達（LP 4ページ）の原因 上位3件

| ページ | 原因 |
|---|---|
| 億万 index | ① LCP 51.1秒 ② Speed Index 17.7秒 ③ 総ペイロード 8,460 KiB（大半が YouTube 埋め込みの動画・プレーヤーJS） |
| 億万 menu | ① LCP 15.3秒 ② Speed Index 13.6秒 ③ FCP 13.6秒 |
| 億万 course | ① LCP 13.1秒 ② Speed Index 13.1秒 ③ FCP 13.1秒 |
| KAMAKURA index | ① LCP 35.6秒 ② 総ペイロード 11,041 KiB（`img2/` の大判画像） ③ Speed Index 12.0秒 |
| KAMAKURA privacy | ① LCP 7.4秒（テキストのみのページとしては異常値。**1回計測のため測定変動の可能性が高い**。同構成の KAMAKURA terms は 2.55秒／93点） |

**推奨-12: LP 4ページの Performance 改善。** ただし次の点をご理解ください。

- Lighthouse モバイルは **CPU 4倍スロットリング＋低速4G** を強制した条件です。実機の 4G/5G ではこれより大幅に速くなります
- 億万鳥者は **YouTube 埋め込み**、KAMAKURA は **77枚のスクロール連動フレーム画像＋37枚の大判画像**が本質的な重さの原因で、いずれも**演出そのもの**です。削ると意図した体験が損なわれます
- 現実的な改善案: YouTube のファサード化（サムネイル画像＋クリックで読み込み）、`img2/` の WebP 統一と圧縮率見直し、フレーム画像の遅延読み込み

**演出を優先するか速度を優先するかは小野様の判断事項**です。作業者: 判断は小野様側、実装は Claude Code で自動可。

#### KAMAKURA index の SEO 83

- `Links are not crawlable` — ナビゲーションが `href` を持たず `onclick` のみ（`index.html:409-412`）
  ```html
  <a class="item" onclick="go('home')">トップ …</a>
  ```
  **推奨-13.** クローラーが辿れず、キーボード操作・スクリーンリーダーでもリンクとして認識されません。修正案: `href="#home"` 等を付与し、`go()` 側で `preventDefault()` する。作業者: Claude Code で自動可
- `Document does not have a meta description` — 推奨-1 と同一

#### 億万 index の Accessibility 93 / Best Practices 96

- `color-contrast`: `<small class="f-note">`（`okumanchoja/index.html:722`、CSS は同 `:299` の `color:#6d6250`）のコントラスト比が不足。**推奨-14.** 修正案: `#6d6250` → より明るい値へ。作業者: Claude Code で自動可
- `unminified-javascript`: インライン JS 約2KiB の削減余地（軽微）

### C-17. Core Web Vitals

Google の「良好」基準: LCP 2.5秒以内 / CLS 0.1以下 / INP 200ms以下（Lighthouse では INP の代替として TBT を使用）。

| ページ | LCP | 判定 | CLS | 判定 | TBT | 判定 |
|---|---:|---|---:|---|---:|---|
| 億万 index | 51.12s | **✕** | 0.000 | ○ | 0ms | ○ |
| 億万 menu | 15.28s | **✕** | 0.001 | ○ | 0ms | ○ |
| 億万 course | 13.10s | **✕** | 0.000 | ○ | 0ms | ○ |
| 億万 terms | 2.56s | △（僅差超過） | 0.010 | ○ | 0ms | ○ |
| 億万 privacy | 2.56s | △（僅差超過） | 0.002 | ○ | 0ms | ○ |
| KAMAKURA index | 35.64s | **✕** | 0.000 | ○ | 84ms | ○ |
| KAMAKURA terms | 2.55s | △（僅差超過） | 0.008 | ○ | 0ms | ○ |
| KAMAKURA privacy | 7.45s | **✕** | 0.000 | ○ | 0ms | ○ |

- **CLS は全8ページで 0.010 以下** — 基準 0.1 に対して極めて良好。レイアウトのガタつきがありません
- **TBT も全8ページで基準内**（最大 84ms）— 操作の応答性は良好
- **LCP のみが課題**で、原因は C-16 の Performance と同一です

**重要**: これは Lighthouse のラボ計測（スロットリングあり・各1回）です。実ユーザーの数値は Search Console の「ウェブに関する主な指標」レポートで公開後に確認してください（G-30 参照）。

### C-18. 主要画面幅での横スクロール（**全8ページ・全5幅 pass**）

375 / 390 / 430 / 768 / 1280px の5幅で `document.scrollWidth > clientWidth` を判定。

| ページ | 375 | 390 | 430 | 768 | 1280 |
|---|---|---|---|---|---|
| 全8ページ | なし | なし | なし | なし | なし |

**横スクロール・要素のはみ出しは全ページ・全幅で0件です。**

スクリーンショット40枚（8ページ×5幅）を `lp-check-20260905/screenshots/` に保存しました（命名: `<brand>_<page>_<width>.png`）。gRev のポップアップ表示例4枚も同ディレクトリにあります。

### C-19. JSエラー・console.error

| ページ | Playwright（Chromium headless） | Lighthouse（`--disable-gpu`） |
|---|---|---|
| 億万 index / menu / course | **0件** | 指摘なし |
| 億万 terms / privacy | **0件** | 指摘なし |
| **KAMAKURA index** | **0件** | `THREE.WebGLRenderer: Error creating WebGL context.` |
| KAMAKURA terms / privacy | **0件** | 指摘なし |

**Playwright での実測は全8ページ 0件 — pass。**

**推奨-15（要確認）: KAMAKURA index の WebGL フォールバック。** Lighthouse は `--disable-gpu` で実行したため WebGL コンテキストの生成に失敗し、Three.js がエラーを記録しました。Playwright（GPU 利用可）では 0件です。つまり**「WebGL が使えない環境ではエラーが出る」**ことは確実ですが、**その際にページが正常に見えるか（グレースフルデグラデーション）は未検証**です。省電力モードの端末・古い端末・WebGL 無効設定のブラウザで表示崩れが起きないか、実機での確認を推奨します。作業者: 実機確認は小野様側／GROWX、フォールバック実装が必要なら Claude Code。

### C-20. 仮置き文字列の残存（**全8ページ pass**）

`TODO` / `【` / `ダミー` / `Lorem` / `0000000000` / `dummy-note` / `要確認` を HTML・表示テキストの両面で検索しました。

| 検出 | 対象 | 判定 |
|---|---|---|
| `tel:0000000000` | 億万 index:729 / menu:554 / course:518 | **問題なし（下記）** |
| `dummy-note` | 億万 index:278 | **問題なし（下記）** |
| `【…】` 2件 | 億万 menu | **問題なし（下記）** |
| `【…】` 28件 | 億万 course | **問題なし（下記）** |
| その他すべて | 全ページ | 検出なし |

- **`tel:0000000000`**: `data-tel` 属性を持つ仮値で、`gmeas.js` 側の `document.querySelectorAll('[data-tel]').forEach(a => a.href='tel:'+CONFIG.TEL)` が上書きします。**実行時に `tel:0367099757` へ正しく置換されていることを Playwright の DOM 取得で確認済み**（3ページとも）。**問題なし**
- **`dummy-note`**: CSS クラス定義のみで、`class="dummy-note"` としての**使用箇所は全ページ0件**。画面には一切表示されません（未使用CSSが残っているだけ）。**問題なし**
- **`【…】`**: `【数量限定】`『【事前予約必須】』`【焼 物】``【先付け】``【和 牛】``【甘味】` など、**日本語のメニュー表記として正当な用法**です。プレースホルダではありません。**問題なし**
- KAMAKURA の規約2ページは、9/5 に `【2026年9月1日】` を `制定日：2026年9月1日` へ確定済み。**残存0件**

---

## D. 計測（GA4 / GTM）

### D-21. GTM 読み込みと page_view

| ページ | GTM-K8Z45R9G | page_view | 測定ID | brand | page_type | 判定 |
|---|---|---|---|---|---|---|
| 億万 index | あり | 送信 | G-5G81FCH20E | `okumanchoja` | `top` | **pass** |
| 億万 menu | あり | 送信 | G-5G81FCH20E | `okumanchoja` | `menu` | **pass** |
| 億万 course | あり | 送信 | G-5G81FCH20E | `okumanchoja` | `course` | **pass** |
| **億万 terms** | **なし** | なし | — | — | — | **fail** |
| **億万 privacy** | **なし** | なし | — | — | — | **fail** |
| KAMAKURA index | あり | 送信 | G-5G81FCH20E | `kamakura` | `top` | **pass** |
| **KAMAKURA terms** | **なし** | なし | — | — | — | **fail** |
| **KAMAKURA privacy** | **なし** | なし | — | — | — | **fail** |

GA4 の `/g/collect` リクエストを傍受し、**クエリと POST 本文の両方**からパラメータを抽出して判定しました。**LP 4ページは全て正しい `brand` / `page_type` で送信されています。**

**推奨-16: 規約4ページに GTM が入っていません。** これは計測導入時の設定（`config.json` の `html_files` が `index.html` のみ）によるもので、不具合ではなく**設定範囲の問題**です。規約ページの閲覧数を測る必要があるかは運用判断です。

- 測る場合: 4ページに GTM スニペットを追加（`page_type` は `terms` / `privacy`）。作業者: Claude Code で自動可
- 測らない場合: 現状のままで問題なし。その旨を仕様として明記

### D-22. イベントの発火と二重計上

各ページで実際にスクロール・クリックし、`dataLayer` への push 回数を数えました。**遷移は `preventDefault` で抑止**し、push のみを観測しています。

| ページ | scroll_reach | cta_click | tel_click | outbound_click | review_badge_click |
|---|---|---|---|---|---|
| 億万 index | 4回 `["concept","menu","sns","access"]` 重複なし | **1回** | **1回** | **1回** | **1回** |
| 億万 menu | 3種 `["shwSp","shwWg","fin"]` 重複なし | **1回** | **1回** | 対象要素なし ※1 | **1回** |
| 億万 course | 2種 `["crs","fin"]` 重複なし | **1回** | **1回** | 対象要素なし ※1 | **1回** |
| KAMAKURA index | 1種 `["welcome"]` 重複なし | **1回** ※2 | 対象要素なし ※3 | **1回** | **1回** |

**二重計上は1件もありません — pass。**

- `scroll_reach` は `section_id` 単位で重複ゼロ（各セクション1回のみ）
- ※1 億万の menu / course には Instagram リンクが無いため `outbound_click` の対象がありません。**仕様どおり**
- ※2 KAMAKURA の `cta_click` は固定CTAバー（`.cta a.a1`）で発火を確認（`cta_type:"reserve"` / `cta_label:"新宿店を予約"`）。ドロワー内の予約リンクは `opacity:0` の非表示状態、予約パネル内のリンクは幅高さ0のため、通常スクロールでは到達しません（画面切替後に表示される設計）
- ※3 KAMAKURA には `tel:` リンクが無いため `tel_click` は発火し得ません（**推奨-8** と同一の指摘）

両ブランドとも**二重計上を防ぐ実装が入っている**ことを確認しました。

- 億万 `gmeas.js`: `if(e.isTrusted!==false)` で、ヘッダー／ドロワーからのリレー（合成クリック）を除外
- KAMAKURA インライン gMeas: `/* 排他: 予約はcta_clickのみ */` — ebica リンクは `cta_click` のみを発火し `outbound_click` を発火しない

### D-23. traffic_type（**全LPページ pass**）

本番ホスト `ifreagroup.co.jp` からのアクセスで、`page_view` の `tt` パラメータは**全て空**でした。internal 扱いになっていません。

---

## E. 口コミ機能（gRev）

### E-24. バッジ表示とタップ挙動（**両ブランド pass**）

**API 消費は各ブランド1回のみ**に抑えて実施しました。

| ブランド | バッジ（`.gb-chip`） | proxy リクエスト | 応答 | ポップアップ |
|---|---|---|---|---|
| 億万鳥者 | 1件（可視）「Google Reviews 新宿本殿 クチコミを見る ›」 | **1回** HTTP 200 | `mode=full`（4,925 B） | **表示**（`.grv-ov open` 390×844）「新宿本殿 4.3★ 1073件のクチコミ」 |
| KAMAKURA | 2件（可視）「新宿店」「錦糸町店」 | **1回** HTTP 200 | `mode=full`（5,912 B） | **表示**（`.grv-ov open` 390×844）「新宿店/錦糸町店 4.3★ 972件」 |

タップ後も URL は変わらず（ページ内ポップアップ）、**FULL モードで正常に動作**しています。店舗名も正しく表示されました。

参考（本日 21:03 の本番検証時に取得した KAMAKURA のキャッシュ済み応答）: 新宿店 rating 4.3 / count 972 / reviews 5件、錦糸町店 rating 4.2 / count 592 / reviews 5件。

### E-25. 直叩き防御（**全項目 pass**）

| チェック | 億万鳥者 | KAMAKURA | 判定 |
|---|---|---|---|
| Referer なしで直叩き | `{"ok":true,"mode":"link"}` | `{"ok":true,"mode":"link"}` | **pass** |
| 不正な `store` キー | — | `{"ok":true,"mode":"link"}` | **pass** |
| `gr/` ディレクトリ一覧 | **403**（一覧表示なし） | **403**（一覧表示なし） | **pass** |
| PHP ソースの直接読み取り | 不可（JSON を返す＝実行される） | 不可 | **pass** |

Referer なしのリクエストは Origin 検査で弾かれ、**カウンタ加算に到達しないため API を消費しません**（`reviews-proxy.php` の判定順: 設定チェック → store チェック → Origin チェック → カウンタ）。設計どおりの fail-closed 動作です。

### E-26. 億万鳥者の規約・プライバシー導線（**fail**）

**B-14 と同一。→ 要-1。** 億万鳥者の LP 3ページには導線がありません。

---

## F. 情報の一貫性

### F-27. NAP（店名・住所・電話）

| ブランド | LP 本文の住所 | LP / 規約の電話 | 小野様回答 | 判定 |
|---|---|---|---|---|
| 億万鳥者 | 東京都新宿区新宿3-36-12 杉忠ビル 4F | 03-6709-9757 | 03-6709-9757 | **pass** |
| KAMAKURA 新宿 | 東京都新宿区新宿4丁目1-13 田園新宿ビル 7階 | 03-6380-5053 | 03-6380-5053 | **pass** |
| KAMAKURA 錦糸町 | 東京都墨田区江東橋3丁目11-3 ソシアル錦糸町ビル B1 | 03-6666-9950 | 03-6666-9950 | **pass** |

**電話番号は LP・規約ページ・小野様回答の三者で完全一致しています。**

規約ページの「所在地」は `東京都新宿区西新宿7-22-33 Polar 西新宿ビル1階` で LP の店舗住所とは異なりますが、これは**法人（株式会社IFREA DINING）の所在地**であり、規約ページとして正しい記載です。**不一致ではありません。**

**未確認-1: Google ビジネスプロフィール上の表記との照合は未実施です。小野様側でご確認ください。** NAP は GBP・LP・各種ポータルで表記を揃えることがローカルSEO上重要です（丁目の漢数字／算用数字、ビル名の表記ゆれ等も含む）。

### F-28. 規約ページの整合（**pass**）

| 項目 | 億万 terms | 億万 privacy | KAMAKURA terms | KAMAKURA privacy |
|---|---|---|---|---|
| 法人名 | 株式会社IFREA DINING | 同左 | 同左 | 同左 |
| 所在地 | 東京都新宿区西新宿7-22-33 Polar 西新宿ビル1階 | 同左 | 同左 | 同左 |
| 連絡先 | 億万鳥者 新宿 1件 | 同左 | **KAMAKURA 新宿・錦糸町 2件** | 同左 |
| 制定日 | 2026年9月1日 | 同左 | 同左 | 同左 |

**両ブランドで法人名・所在地・制定日が整合。連絡先は仕様どおり億万1件・KAMAKURA 2件。** `【】` や `<span class="todo">` の残存も全4ページで0件です。

### F-29. 著作権表記

| ページ | © 表記 | 判定 |
|---|---|---|
| 億万 index / menu / course | `© IFREADINING Inc.` | pass |
| **億万 terms / privacy** | **なし** | **fail** |
| KAMAKURA index | `© IFREADINING Inc.` | pass |
| **KAMAKURA terms / privacy** | **なし** | **fail** |

**推奨-17: 規約4ページに © 表記がありません。** 修正案: `<a class="back" href="index.html">サイトに戻る</a>`（KAMAKURA `terms.html:112` / 億万 `okumanchoja/terms.html:111` 等）の直後に追加。作業者: Claude Code で自動可。

```html
<div class="meta" style="border-top:none;margin-top:8px"><small>© IFREADINING Inc.</small></div>
```

---

## G. 公開後に必要なもの（**すべて未実施**）

| # | 項目 | 状態 | 作業者 |
|---|---|---|---|
| G-30 | **Google Search Console への登録とサイトマップ送信** | **未実施** | 小野様のGoogleアカウントで登録 → GTM 経由で所有権確認 |
| G-31 | **GA4 レポートでの数値確認** | **未実施**（リアルタイムは即時、通常レポートは反映まで24〜48時間） | GROWX |
| G-32 | **Looker Studio のブランド別ダッシュボード** | **未実施**（`brand` ディメンションで分離する想定） | GROWX |

- **未確認-2**: G-30 — Search Console 未登録のため、インデックス状況・カバレッジ・実ユーザーの Core Web Vitals は確認できません
- **未確認-3**: G-31 — GA4 の通常レポートでの実データ着地は未確認です（本チェックで確認したのは**送信されていること**までです）
- **未確認-4**: G-32 — 未作成
- **未確認-5**: F-27 — Google ビジネスプロフィールとの NAP 照合（小野様側）

なお `robots.txt` に `Sitemap: https://ifreagroup.co.jp/sitemap.xml` が宣言されていますが、**このサイトマップに `/okumanchoja/` と `/kamakura/` が含まれているかは未確認**です（WordPress 生成のサイトマップのため、静的ディレクトリは含まれない可能性が高い）。G-30 の際にあわせてご確認ください。

---

## 対応一覧（優先度順）

### 要対応（公開前）

| # | 内容 | 対象 | 作業者 |
|---|---|---|---|
| 要-1 | 億万鳥者 LP 3ページに規約・プライバシー導線を追加 | `okumanchoja/index.html:722` 他 | **Claude Code で自動可** |
| 要-2 | `http://` → `https://` リダイレクトの設定 | ドキュメントルート `.htaccess` | **Xserver 上で手動**（WP と共用のため要注意） |

### 推奨（納品後でも可）

| # | 内容 | 作業者 |
|---|---|---|
| 推奨-1 | meta description を index 2ページに追加（文案は本文に記載） | Claude Code |
| 推奨-2 | canonical を全ページに追加 | Claude Code |
| 推奨-3 | OGP（og:title/description/image/url）を追加 | Claude Code（画像選定のみ要判断） |
| 推奨-4 | favicon を追加 | 画像は小野様／設置は Claude Code |
| 推奨-5 | 億万 menu / course に h1 を設定 | Claude Code |
| 推奨-6 | 見出し順序の飛び（h2→h4）を修正 | Claude Code |
| 推奨-7 | 億万 menu の料理写真12枚に alt を設定 | Claude Code |
| 推奨-8 | KAMAKURA に `tel:` リンクを追加 | Claude Code |
| 推奨-9 | ebica の `affiid` 不統一の確認 | **小野様（判断）** |
| 推奨-10 | 億万の外部リンクを `target="_blank" rel="noopener"` に | Claude Code |
| 推奨-11 | 規約ページ同士の相互リンク | Claude Code |
| 推奨-12 | LP 4ページの Performance 改善（YouTube ファサード化・画像最適化） | **小野様（演出との優先度判断）** ／ 実装 Claude Code |
| 推奨-13 | KAMAKURA ナビに `href` を付与（crawlable / a11y） | Claude Code |
| 推奨-14 | 億万 index フッター注記のコントラスト改善 | Claude Code |
| 推奨-15 | WebGL 非対応環境での KAMAKURA 表示確認 | **実機確認は小野様／GROWX** |
| 推奨-16 | 規約4ページへの GTM 導入要否の判断 | **小野様（運用判断）** |
| 推奨-17 | 規約4ページに © 表記を追加 | Claude Code |

---

## 保存物

```
~/Desktop/outputs/
├── LP公開前チェック_KAMAKURA_億万鳥者_20260905.md   ← 本書
└── lp-check-20260905/
    ├── lighthouse/     Lighthouse 結果 JSON 8件 ＋ run.log
    ├── screenshots/    44枚（8ページ×5幅＝40枚 ＋ gRev ポップアップ4枚）
    └── raw/            取得HTML 8件・robots.txt・runtime.json・links.json
```

---

*作成: 2026-09-05 / チェックのみ実施。修正は行っていません。*
