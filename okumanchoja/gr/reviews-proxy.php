<?php
/**
 * gRev 受付係: Google Places API (New) 中継 v1.0 2026-08-27
 * 濱田側リスク指摘v2.0 (2026-08-27) チェックリスト準拠
 *
 * 役割: LPからのタップ時リクエストを受け、カウンタ・防御を通過した場合のみ
 *       Places APIへ問い合わせて整形結果を返す。それ以外は {"mode":"link"} を返す。
 * 設置: 公開ディレクトリ内 /gr/reviews-proxy.php
 * カウンタ: ドキュメントルート外 (COUNTER_DIR) に保存。
 */

/* ============================================================
 * 設定 (デプロイ時に設定する箇所は【要設定】)
 * ============================================================ */

/* 【要設定】APIキー: Xserver設置時に濱田側が直接記入。チャット/LINEに平文で流さない */
const API_KEY = '';

/* 【要設定】規約ページの公開URL。両方が空でない場合のみFULL応答を許可(順序事故ガード) */
const TERMS_URL   = '';
const PRIVACY_URL = '';

/* 【要設定】カウンタ保存先: ドキュメントルート外の絶対パス。
 * Xserver例: /home/{サーバーID}/{ドメイン}/gr_private  (public_htmlの外)
 * 空のままなら fail-closed で常にlink応答になる */
const COUNTER_DIR = '';

/* 【要設定】自サイトのオリジン(Origin/Referer検査用)。本番ドメイン確定後に追記 */
const ALLOWED_ORIGINS = array(
  'https://growx-inc.github.io',
  /* 'https://ifreagroup.co.jp', ← 本番ドメイン確定後に有効化 */
);

/* place_id: 規約上、保存が許可されている唯一の例外フィールド */
const PLACES = array(
  /* 億万は1店舗。place_idは濱田側取得分（2026-09-01） */
  'okuman' => array('place_id' => 'ChIJj2p-viSNGGARJajVhtqMIb0', 'expect' => '億万鳥者'),
);

/* ガード閾値 */
const DAILY_LIMIT   = 25;   /* 日次: 25回 (25×31=775 ≪ 月間無料枠1,000) */
const MONTHLY_LIMIT = 775;  /* 月次の総枠ガード */
const IP_LIMIT      = 3;    /* 同一IP 60秒あたり */
const IP_WINDOW_SEC = 60;
const API_TIMEOUT_S = 8;

/* ============================================================
 * 共通: キャッシュ禁止ヘッダー + JSON応答
 * ============================================================ */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

function respond_link($reason) {
  /* linkモード応答: フロントはリンクバッジ表示に切替。理由はログ用途のみ(クライアントに詳細は出さない) */
  echo json_encode(array('ok' => true, 'mode' => 'link'));
  exit;
}
function respond_full($store) {
  echo json_encode(array('ok' => true, 'mode' => 'full', 'store' => $store), JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
 * 0. 順序事故ガード: 規約ページ未公開 or キー未設定なら常にlink
 * ============================================================ */
if (API_KEY === '' || TERMS_URL === '' || PRIVACY_URL === '' || COUNTER_DIR === '') {
  respond_link('not_configured');
}

/* ============================================================
 * 1. 入力検査
 * ============================================================ */
$storeKey = isset($_GET['store']) ? (string)$_GET['store'] : '';
if (!isset(PLACES[$storeKey])) { respond_link('bad_store'); }

/* ============================================================
 * 2. Origin / Referer 検査 (両方欠落 or 不一致は拒否)
 * ============================================================ */
function origin_ok() {
  $cand = array();
  if (!empty($_SERVER['HTTP_ORIGIN']))  { $cand[] = $_SERVER['HTTP_ORIGIN']; }
  if (!empty($_SERVER['HTTP_REFERER'])) { $cand[] = $_SERVER['HTTP_REFERER']; }
  if (!$cand) { return false; } /* どちらも無いリクエスト(直叩き・多くのbot)は拒否 */
  foreach ($cand as $c) {
    foreach (ALLOWED_ORIGINS as $o) {
      if (strpos($c, $o) === 0) { return true; }
    }
  }
  return false;
}
if (!origin_ok()) { respond_link('origin'); }

/* ============================================================
 * 3. カウンタ基盤 (flock排他・fail-closed)
 * ============================================================ */
function counter_bump($file, $limit, $windowKey) {
  /* $windowKey が変わったらカウンタをリセット。上限内なら加算してtrue。
   * 読めない・書けない・ロックできない場合は false (fail-closed)。 */
  $fp = @fopen($file, 'c+');
  if ($fp === false) { return false; }
  if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }
  $raw = stream_get_contents($fp);
  $d = json_decode($raw, true);
  if (!is_array($d) || !isset($d['k']) || $d['k'] !== $windowKey) {
    $d = array('k' => $windowKey, 'n' => 0, 'exhausted_at' => null);
  }
  if ($d['n'] >= $limit) {
    if ($d['exhausted_at'] === null) {
      $d['exhausted_at'] = gmdate('c'); /* 枯渇時刻ログ(3-6) */
      counter_write($fp, $d);
    }
    flock($fp, LOCK_UN); fclose($fp);
    return false;
  }
  /* API呼び出しの「前」に加算・以後減算しない(3-2) */
  $d['n'] += 1;
  $ok = counter_write($fp, $d);
  flock($fp, LOCK_UN); fclose($fp);
  return $ok; /* 書き込み失敗も fail-closed */
}
function counter_write($fp, $d) {
  if (ftruncate($fp, 0) === false) { return false; }
  rewind($fp);
  return fwrite($fp, json_encode($d)) !== false;
}

if (!is_dir(COUNTER_DIR) || !is_writable(COUNTER_DIR)) { respond_link('counter_dir'); }

/* ============================================================
 * 4. IPレート制限 (同一IP 60秒3回・排他/fail-closedは共通関数)
 * ============================================================ */
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($ip === '') { respond_link('no_ip'); }
$ipWindow = (string)floor(time() / IP_WINDOW_SEC); /* 60秒窓 */
$ipFile = COUNTER_DIR . '/ip_' . hash('sha256', $ip) . '.json';
if (!counter_bump($ipFile, IP_LIMIT, $ipWindow)) { respond_link('ip_rate'); }

/* ============================================================
 * 5. 日次・月次カウンタ
 *    月境界は America/Los_Angeles 基準 (Googleのリセット基準に合わせる 3-3)
 * ============================================================ */
$pt = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
$dayKey   = $pt->format('Y-m-d');
$monthKey = $pt->format('Y-m');
if (!counter_bump(COUNTER_DIR . '/daily.json',   DAILY_LIMIT,   $dayKey))   { respond_link('daily_limit'); }
if (!counter_bump(COUNTER_DIR . '/monthly.json', MONTHLY_LIMIT, $monthKey)) { respond_link('monthly_limit'); }

/* ============================================================
 * 6. Places API (New) 呼び出し
 *    languageCode=ja 明示指定(2-5) / FieldMaskにdisplayName含む(4-F)
 * ============================================================ */
$placeId = PLACES[$storeKey]['place_id'];
$url = 'https://places.googleapis.com/v1/places/' . rawurlencode($placeId) . '?languageCode=ja';
$fields = 'id,displayName,rating,userRatingCount,googleMapsUri,reviews';

$ch = curl_init($url);
curl_setopt_array($ch, array(
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => API_TIMEOUT_S,
  CURLOPT_HTTPHEADER     => array(
    'X-Goog-Api-Key: ' . API_KEY,
    'X-Goog-FieldMask: ' . $fields,
  ),
));
$body = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false || $http !== 200) { respond_link('api_error_' . $http); }
$p = json_decode($body, true);
if (!is_array($p) || !isset($p['rating']) || !isset($p['userRatingCount'])) { respond_link('api_shape'); }

/* ============================================================
 * 7. 整形して返す (保存は一切しない。整形→即応答)
 * ============================================================ */
function s($v) { return is_string($v) ? $v : ''; }
$reviews = array();
if (isset($p['reviews']) && is_array($p['reviews'])) {
  foreach ($p['reviews'] as $r) {
    $text     = isset($r['text']['text']) ? s($r['text']['text']) : '';
    $textLang = isset($r['text']['languageCode']) ? s($r['text']['languageCode']) : '';
    $origLang = isset($r['originalText']['languageCode']) ? s($r['originalText']['languageCode']) : $textLang;
    $reviews[] = array(
      'rating'       => isset($r['rating']) ? (float)$r['rating'] : 0,
      'text'         => $text,
      'translated'   => ($textLang !== '' && $origLang !== '' && $textLang !== $origLang), /* 翻訳告知(2-4) */
      'relativeTime' => isset($r['relativePublishTimeDescription']) ? s($r['relativePublishTimeDescription']) : '',
      'mapsUri'      => isset($r['googleMapsUri']) ? s($r['googleMapsUri']) : '',
      'author'       => array(
        'name'  => isset($r['authorAttribution']['displayName']) ? s($r['authorAttribution']['displayName']) : '',
        'photo' => isset($r['authorAttribution']['photoUri'])    ? s($r['authorAttribution']['photoUri'])    : '',
        'uri'   => isset($r['authorAttribution']['uri'])         ? s($r['authorAttribution']['uri'])         : '',
      ),
    );
  }
}
respond_full(array(
  'name'    => isset($p['displayName']['text']) ? s($p['displayName']['text']) : '',
  'rating'  => (float)$p['rating'],
  'count'   => (int)$p['userRatingCount'],
  'mapsUri' => isset($p['googleMapsUri']) ? s($p['googleMapsUri']) : '',
  'reviews' => $reviews,
));
