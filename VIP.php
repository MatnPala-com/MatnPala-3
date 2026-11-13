<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && !defined('MATNPALA_ENGINE_BOOTSTRAP')) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Not Found');
}
if (defined('MATNPALA_VIP_LOADED')) return;
define('MATNPALA_VIP_LOADED', true);

if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);
if (DEBUG_MODE) { ini_set('display_errors', '1'); error_reporting(E_ALL); }

/* Fallback mb_* */
if (!function_exists('mb_strlen')) {
    // UTF-8-safe fallbacks for hosts without ext-mbstring.
    function mb_strlen($s, $e = null) {
        if (!is_string($s) || $s === '') return 0;
        if (preg_match_all('/./us', $s, $m) !== false) return count($m[0]);
        return strlen($s);
    }
    function mb_substr($s, $st, $l = null, $e = null) {
        if (!is_string($s)) return '';
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars)) return $l === null ? substr($s, $st) : substr($s, $st, $l);
        $count = count($chars);
        if ($st < 0) $st = max(0, $count + $st);
        if ($l === null) $slice = array_slice($chars, $st);
        elseif ($l < 0) $slice = array_slice($chars, $st, max(0, $count - $st + $l));
        else $slice = array_slice($chars, $st, $l);
        return implode('', $slice);
    }
    function mb_strtolower($s, $e = null) { return is_string($s) ? strtolower($s) : ''; }
    function mb_strtoupper($s, $e = null) { return is_string($s) ? strtoupper($s) : ''; }
    function mb_strpos($h, $n, $o = 0, $e = null) {
        if (!is_string($h) || !is_string($n)) return false;
        $tail = $o > 0 ? mb_substr($h, $o) : $h;
        $pos = strpos($tail, $n);
        if ($pos === false) return false;
        return $o + mb_strlen(substr($tail, 0, $pos));
    }
    function mb_stripos($h, $n, $o = 0, $e = null) {
        if (!is_string($h) || !is_string($n)) return false;
        return mb_strpos(strtolower($h), strtolower($n), $o);
    }
    function mb_check_encoding($s, $e = null) {
        return is_string($s) && preg_match('//u', $s) === 1;
    }
    function mb_convert_encoding($s, $t, $f = null) {
        if (!is_string($s)) return '';
        if (function_exists('iconv')) {
            $converted = @iconv($f ?: 'UTF-8', $t, $s);
            if ($converted !== false) return $converted;
        }
        return $s;
    }
}

/* ثابت‌ها */
if (!defined('DB_PATH'))          define('DB_PATH', '/home/matnpala/secure_data/gshvjfahv');
if (!defined('FEEDBACK_PATH'))    define('FEEDBACK_PATH', '/home/matnpala/secure_data/jbddjbkewgbhkewbjfwekfe');
if (!defined('MSG_COUNTER_FILE')) define('MSG_COUNTER_FILE', '/home/matnpala/secure_data/bjjbbj.txt');
if (!defined('WEIGHT_LANG_PATH')) define('WEIGHT_LANG_PATH', __DIR__ . '/lang/Weight');

/* ═══════════ پیکربندی WeightEngine ═══════════ */
if (!defined('WEIGHT_BOOST_ALPHA'))   define('WEIGHT_BOOST_ALPHA', 0.20);
if (!defined('WEIGHT_BOOST_MAX_SENT')) define('WEIGHT_BOOST_MAX_SENT', 500);

/* ═══════════ پیکربندی Semantic ═══════════ */
if (!defined('SEMANTIC_ENABLED'))       define('SEMANTIC_ENABLED', true);
if (!defined('SEMANTIC_ENDPOINT'))      define('SEMANTIC_ENDPOINT', 'http://127.0.0.1:8765');
if (!defined('SEMANTIC_TIMEOUT'))       define('SEMANTIC_TIMEOUT', 8);
if (!defined('SEMANTIC_MAX_SENT'))      define('SEMANTIC_MAX_SENT', 600);
if (!defined('SEMANTIC_MIN_LEN'))       define('SEMANTIC_MIN_LEN', 120);
if (!defined('SEMANTIC_ALPHA'))         define('SEMANTIC_ALPHA', 0.32);
if (!defined('SEMANTIC_AVAIL_TTL'))     define('SEMANTIC_AVAIL_TTL', 60);

/* ═══════════ پیکربندی Abstractive ═══════════ */
if (!defined('ABSTRACTIVE_ENABLED'))    define('ABSTRACTIVE_ENABLED', true);
if (!defined('ABSTRACTIVE_ENDPOINT'))   define('ABSTRACTIVE_ENDPOINT', 'http://127.0.0.1:8766');
if (!defined('ABSTRACTIVE_TIMEOUT'))    define('ABSTRACTIVE_TIMEOUT', 25);
if (!defined('ABSTRACTIVE_MIN_INPUT'))  define('ABSTRACTIVE_MIN_INPUT', 200);
if (!defined('ABSTRACTIVE_MAX_INPUT'))  define('ABSTRACTIVE_MAX_INPUT', 6000);
if (!defined('ABSTRACTIVE_RATIO'))      define('ABSTRACTIVE_RATIO', 0.28);
if (!defined('ABSTRACTIVE_AVAIL_TTL'))  define('ABSTRACTIVE_AVAIL_TTL', 60);

/* ═══════════ پیکربندی Summarizer 3.0 ═══════════ */
if (!defined('SUMMARIZER_VERSION'))      define('SUMMARIZER_VERSION', '5.0.0');
if (!defined('SUMMARIZER_MAX_PASSES'))   define('SUMMARIZER_MAX_PASSES', 4);
if (!defined('SUMMARIZER_ENABLE_BM25'))  define('SUMMARIZER_ENABLE_BM25', true);
if (!defined('SUMMARIZER_BM25_K1'))      define('SUMMARIZER_BM25_K1', 1.5);
if (!defined('SUMMARIZER_BM25_B'))       define('SUMMARIZER_BM25_B', 0.75);
if (!defined('SUMMARIZER_ENTITY_CACHE')) define('SUMMARIZER_ENTITY_CACHE', 512);

/* Session */
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', '86400');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
    session_name('MPSESSID');
    session_start();
}
if (PHP_SAPI !== 'cli') {
    if (!isset($_SESSION['created'])) $_SESSION['created'] = time();
    elseif (time() - $_SESSION['created'] > 3600) $_SESSION['created'] = time();
}

/* ═══════════ DB ═══════════ */
function db(): PDO {
    static $i = null;
    if ($i instanceof PDO) return $i;
    $d = dirname(DB_PATH);
    if (!is_dir($d)) @mkdir($d, 0700, true);
    $i = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $i->exec('PRAGMA journal_mode=WAL');
    $i->exec('PRAGMA foreign_keys=ON');
    $i->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE NOT NULL, pass_hash TEXT NOT NULL, created_at INTEGER NOT NULL)');
    $i->exec('CREATE TABLE IF NOT EXISTS rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip TEXT NOT NULL, uid INTEGER, endpoint TEXT NOT NULL, ts INTEGER NOT NULL)');
    $i->exec('CREATE INDEX IF NOT EXISTS idx_rate_ip_ts ON rate_limits(ip, endpoint, ts)');
    return $i;
}

/* ═══════════ Feedback ═══════════ */
function feedback_load_all(): array {
    $p = FEEDBACK_PATH;
    if (!file_exists($p)) return ['version' => 1, 'records' => []];
    $f = @fopen($p, 'r');
    if (!$f) return ['version' => 1, 'records' => []];
    @flock($f, LOCK_SH);
    $r = stream_get_contents($f);
    @flock($f, LOCK_UN);
    fclose($f);
    if ($r === false || $r === '') return ['version' => 1, 'records' => []];
    $d = json_decode($r, true);
    if (!is_array($d) || !isset($d['records']) || !is_array($d['records'])) return ['version' => 1, 'records' => []];
    return $d;
}

function feedback_append_record(array $rec): bool {
    $p = FEEDBACK_PATH;
    $d = dirname($p);
    if (!is_dir($d)) @mkdir($d, 0700, true);
    $lf = $p . '.lock';
    $l = @fopen($lf, 'c');
    if (!$l) return false;
    @flock($l, LOCK_EX);
    $data = ['version' => 1, 'records' => []];
    if (file_exists($p)) {
        $r = @file_get_contents($p);
        if ($r !== false && $r !== '') {
            $dec = json_decode($r, true);
            if (is_array($dec) && isset($dec['records']) && is_array($dec['records'])) $data = $dec;
        }
    }
    $rep = false;
    if (isset($rec['sid'])) {
        foreach ($data['records'] as $k => $x) {
            if (isset($x['sid']) && $x['sid'] === $rec['sid'] && isset($x['uid']) && (int)$x['uid'] === (int)($rec['uid'] ?? 0)) {
                $rec['id'] = $x['id'] ?? ($rec['id'] ?? null);
                $rec['created_at'] = $x['created_at'] ?? ($rec['created_at'] ?? time());
                $data['records'][$k] = $rec;
                $rep = true;
                break;
            }
        }
    }
    if (!$rep) $data['records'][] = $rec;
    if (count($data['records']) > 5000) $data['records'] = array_slice($data['records'], -5000);
    $t = $p . '.tmp.' . bin2hex(random_bytes(4));
    $j = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $ok = @file_put_contents($t, $j, LOCK_EX) !== false;
    if ($ok) {
        @chmod($t, 0600);
        if (!@rename($t, $p)) { @unlink($t); $ok = false; }
    } else @unlink($t);
    @flock($l, LOCK_UN);
    fclose($l);
    return $ok;
}

/* ═══════════ Message Counter ═══════════ */
function msg_counter_read(): int {
    if (!is_file(MSG_COUNTER_FILE)) return 0;
    $fh = @fopen(MSG_COUNTER_FILE, 'r');
    if (!$fh) return 0;
    @flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    @flock($fh, LOCK_UN);
    @fclose($fh);
    if ($raw === false) return 0;
    $n = (int)trim((string)$raw);
    return $n > 0 ? $n : 0;
}

function msg_counter_increment(): void {
    $dir = dirname(MSG_COUNTER_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    $lockFile = MSG_COUNTER_FILE . '.lock';
    $lock = @fopen($lockFile, 'c');
    if (!$lock) return;
    @flock($lock, LOCK_EX);
    $current = 0;
    if (is_file(MSG_COUNTER_FILE)) {
        $raw = @file_get_contents(MSG_COUNTER_FILE);
        if ($raw !== false) $current = max(0, (int)trim((string)$raw));
    }
    $next = $current + 1;
    $tmp = MSG_COUNTER_FILE . '.tmp.' . bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, (string)$next, LOCK_EX);
    if ($ok !== false) {
        @chmod($tmp, 0600);
        if (!@rename($tmp, MSG_COUNTER_FILE)) @unlink($tmp);
    } else @unlink($tmp);
    @flock($lock, LOCK_UN);
    @fclose($lock);
}

/* ═══════════ Rate Limit / Origin / CSRF / Token ═══════════ */
function rate_limit(string $ep, int $max = 30, ?int $uid = null): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $n = time();
    $c = $n - 60;
    $p = db();
    if (random_int(1, 20) === 1) $p->prepare('DELETE FROM rate_limits WHERE ts < ?')->execute([$n - 300]);
    $s = $p->prepare('SELECT COUNT(*) c FROM rate_limits WHERE ip=? AND endpoint=? AND ts>?');
    $s->execute([$ip, $ep, $c]);
    $r = $s->fetch();
    if ($r && (int)$r['c'] >= $max) return false;
    $p->prepare('INSERT INTO rate_limits (ip,uid,endpoint,ts) VALUES (?,?,?,?)')->execute([$ip, $uid, $ep, $n]);
    return true;
}

function check_origin(): bool {
    $h = $_SERVER['HTTP_HOST'] ?? '';
    $h = preg_replace('/^www\./i', '', preg_replace('/:\d+$/', '', $h));
    $checked = false;
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $k) {
        if (!empty($_SERVER[$k])) {
            $u = parse_url($_SERVER[$k], PHP_URL_HOST);
            $checked = true;
            if ($u === null || $u === false || $u === '') return false;
            $u = preg_replace('/^www\./i', '', $u);
            if (strcasecmp($u, $h) !== 0) return false;
            return true;
        }
    }
    return !$checked;
}

if (PHP_SAPI !== 'cli' && empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf(): string { return PHP_SAPI === 'cli' ? '' : (string)($_SESSION['csrf'] ?? ''); }

function check_csrf(): bool {
    if (PHP_SAPI === 'cli') return true;
    if (empty($_POST['csrf'])) return false;
    if (empty($_SESSION['csrf'])) return false;
    return hash_equals((string)$_SESSION['csrf'], (string)$_POST['csrf']);
}

function ensure_transport_token(): string {
    if (PHP_SAPI === 'cli') return '';
    if (empty($_SESSION['tk']) || empty($_SESSION['tk_ts']) || time() - $_SESSION['tk_ts'] > 540) {
        $_SESSION['tk'] = bin2hex(random_bytes(24));
        $_SESSION['tk_ts'] = time();
    }
    return $_SESSION['tk'];
}
function transport_token(): string { return ensure_transport_token(); }
function verify_transport_token(string $t): bool {
    if (PHP_SAPI === 'cli') return true;
    if (empty($_SESSION['tk']) || empty($_SESSION['tk_ts']) || time() - $_SESSION['tk_ts'] > 600) {
        ensure_transport_token();
        return false;
    }
    return hash_equals($_SESSION['tk'], $t);
}

/* ═══════════ Auth ═══════════ */
function current_user(): ?array {
    if (PHP_SAPI === 'cli') return null;
    if (empty($_SESSION['uid'])) return null;
    static $u = null, $l = false;
    if ($l) return $u;
    $l = true;
    $s = db()->prepare('SELECT id,email,created_at FROM users WHERE id=?');
    $s->execute([$_SESSION['uid']]);
    $u = $s->fetch() ?: null;
    return $u;
}

function auth_register(string $em, string $pw): array {
    $em = strtolower(trim($em));
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'ایمیل معتبر نیست.'];
    if (mb_strlen($pw) < 8) return ['ok' => false, 'error' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.'];
    if (mb_strlen($pw) > 200) return ['ok' => false, 'error' => 'رمز عبور بیش از حد طولانی است.'];
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/\d/', $pw)) return ['ok' => false, 'error' => 'رمز عبور باید شامل حرف و عدد باشد.'];
    try {
        $s = db()->prepare('INSERT INTO users (email,pass_hash,created_at) VALUES (?,?,?)');
        $s->execute([$em, password_hash($pw, PASSWORD_DEFAULT), time()]);
        $u = (int)db()->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['uid'] = $u;
        $_SESSION['created'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        $_SESSION['tk'] = bin2hex(random_bytes(24));
        $_SESSION['tk_ts'] = time();
        session_write_close();
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'ثبت‌نام انجام نشد. لطفاً دوباره تلاش کنید.'];
    }
}

function auth_login(string $em, string $pw): array {
    $em = strtolower(trim($em));
    $s = db()->prepare('SELECT id,pass_hash FROM users WHERE email=?');
    $s->execute([$em]);
    $r = $s->fetch();
    if (!$r || !password_verify($pw, $r['pass_hash'])) return ['ok' => false, 'error' => 'ایمیل یا رمز عبور اشتباه است.'];
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$r['id'];
    $_SESSION['created'] = time();
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['tk'] = bin2hex(random_bytes(24));
    $_SESSION['tk_ts'] = time();
    session_write_close();
    return ['ok' => true];
}

function secure_logout(): void {
    if (PHP_SAPI === 'cli') return;
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => ($p['path'] ?? '/'),
            'domain' => ($p['domain'] ?? ''),
            'secure' => ($p['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    session_destroy();
}

/* ═══════════ Sanitizers ═══════════ */
function sanitize_extracted_text(string $t): string {
    if (!mb_check_encoding($t, 'UTF-8')) $t = mb_convert_encoding($t, 'UTF-8', 'UTF-8');
    $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $t);
    $t = preg_replace('/<\s*\/?\s*(script|iframe|object|embed|applet|meta|link|style|base|form|input|button|svg|math|video|audio|source|track|frame|frameset|body|html|head)\b[^>]*>/i', ' ', $t);
    $t = preg_replace('/<\s*\?\s*php\b/i', '‹?php', $t);
    $t = preg_replace('/<\s*\?\s*=/i', '‹?=', $t);
    $t = preg_replace('/\bon[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', ' ', $t);
    $t = preg_replace('/\b(javascript|vbscript)\s*:/i', '$1_', $t);
    $t = preg_replace('/[A-Za-z0-9+\/]{500,}={0,2}/', ' ', $t);
    if (mb_strlen($t) > 400000) $t = mb_substr($t, 0, 400000);
    return $t;
}

/* sanitize_feedback با word-boundary (رفع false-positive) */
function sanitize_feedback(string $t): string {
    $bad = ['کیر','کص','کس','جنده','کون','کونی','گوه','گوهی','خارکصه','مادرجنده','حرومزاده','حروم','لاشی','دیوث','ایناموس','ریدم','ریدی','کصخل','کیرم','کیرتو'];
    foreach ($bad as $x) {
        $pat = '/(?<![\p{L}\p{N}])' . preg_quote($x, '/') . '(?![\p{L}\p{N}])/u';
        if (preg_match($pat, $t)) return '[واژگان حذف شد]';
    }
    return $t;
}

/* ═══════════ LangData ═══════════ */
final class LangData {
    private static $c = [], $b = null, $m = null;
    public static function setBasePath(string $p): void { self::$b = rtrim($p, '/'); }
    public static function get(string $c): array {
        if (isset(self::$c[$c])) return self::$c[$c];
        $c = preg_replace('/[^a-z]/', '', strtolower($c));
        if (!in_array($c, ['fa', 'en', 'fr', 'es'], true)) $c = 'en';
        $b = self::$b ?: __DIR__ . '/lang';
        $p = $b . '/' . $c . '.json';
        if (!file_exists($p)) $p = $b . '/en.json';
        $r = @file_get_contents($p);
        $d = $r !== false ? json_decode($r, true) : null;
        if (!is_array($d)) {
            $d = ['code'=>$c,'name'=>$c,'rtl'=>($c==='fa'),'stopwords'=>[],'negative'=>[],'positive'=>[],'boost'=>[],'cue_phrases'=>[],'conjunctions'=>[],'discourse'=>[],'sentence_endings'=>['.','!','?'],'min_sentence_length'=>5,'max_sentence_length'=>500,'ideal_sentence_length'=>150,'rtl_numerals'=>false];
        }
        $def = ['stopwords'=>[],'negative'=>[],'positive'=>[],'boost'=>[],'cue_phrases'=>[],'conjunctions'=>[],'discourse'=>[],'sentence_endings'=>['.','!','?'],'min_sentence_length'=>5,'max_sentence_length'=>500,'ideal_sentence_length'=>150,'rtl_numerals'=>false,'rtl'=>false];
        foreach ($def as $k => $v) if (!isset($d[$k])) $d[$k] = $v;
        return self::$c[$c] = $d;
    }
    public static function detect(string $t): string {
        $s = ['fa'=>0,'en'=>0,'fr'=>0,'es'=>0];
        $s['fa'] = preg_match_all('/[\x{0600}-\x{06FF}]/u', $t);
        $fr = preg_match_all('/[àâçéèêëîïôûùüÿœ]/ui', $t);
        $es = preg_match_all('/[áéíóúñ¿¡ü]/ui', $t);
        $s['fr'] = $fr;
        $s['es'] = $es;
        $lt = preg_match_all('/[a-zA-Z]/u', $t);
        $s['en'] = max(0, $lt - $fr - $es);
        arsort($s);
        $b = key($s);
        $tp = (int)current($s);
        $tot = (int)$s['fa'] + (int)$lt;
        if ($tot === 0) return 'en';
        $th = $tot < 10 ? 1 : ($tot < 30 ? 2 : ($tot < 80 ? 4 : 5));
        if ($tp < $th) return 'en';
        return $b;
    }
    public static function mergedStopwords(): array {
        if (self::$m !== null) return self::$m;
        $m = [];
        foreach (['fa', 'en', 'fr', 'es'] as $c) {
            foreach (self::get($c)['stopwords'] as $w) $m[mb_strtolower($w)] = true;
        }
        return self::$m = $m;
    }
}

/* ═══════════ Correcter ═══════════ */
final class Correcter {
    private $lc, $d = [], $ch = [], $t0;
    public function __construct(?string $lc = 'fa') { $this->lc = $lc ?: 'fa'; $this->t0 = microtime(true); $this->loadDict(); }
    private function loadDict(): void {
        $p = __DIR__ . '/lang/fa_corrections.json';
        $d = null;
        if (file_exists($p)) {
            $r = @file_get_contents($p);
            if ($r !== false) $d = json_decode($r, true);
        }
        if (!is_array($d)) $d = [];
        $m = $d['misspell'] ?? [];
        if (!is_array($m)) $m = [];
        $pEn = __DIR__ . '/lang/en_corrections.json';
        if (file_exists($pEn)) {
            $rEn = @file_get_contents($pEn);
            $dEn = $rEn !== false ? json_decode($rEn, true) : null;
            if (is_array($dEn) && isset($dEn['misspell']) && is_array($dEn['misspell'])) {
                $m = array_merge($dEn['misspell'], $m);
            }
        }
        foreach ($m as $w => $r) {
            $wn = $this->nk((string)$w);
            if ($wn === '') continue;
            $rn = (string)$r;
            if ($this->nk($rn) === $wn) continue;
            $this->d[$wn] = $rn;
        }
    }
    private function nk(string $w): string {
        $w = trim($w);
        if ($w === '') return '';
        $w = str_replace(['ي','ك','ۀ','ة','ﻻ','ﻷ'], ['ی','ک','ه','ه','لا','لا'], $w);
        $w = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $w);
        return mb_strtolower($w);
    }
    public function correct(string $t, array $o = []): array {
        $this->ch = [];
        $orig = $t;
        $sd = $o['spell'] ?? true;
        $sg = $o['grammar'] ?? true;
        $sn = $o['numbers'] ?? true;
        $sn2 = $o['normalize'] ?? true;
        $nt = (string)($o['number_target'] ?? 'fa');
        if ($sn2) $t = $this->normChars($t);
        if ($sn && $nt !== 'keep') $t = $this->normNums($t, $nt);
        if ($sd) $t = $this->appDict($t);
        if ($sg) $t = $this->appGram($t);
        $t = $this->clean($t);
        return ['success'=>true,'corrected'=>$t,'changes'=>$this->ch,'stats'=>['original_length'=>mb_strlen($orig),'corrected_length'=>mb_strlen($t),'changes_count'=>count($this->ch),'processing_time'=>round(microtime(true)-$this->t0,4)]];
    }
    private function normChars(string $t): string {
        $p = ['ي'=>'ی','ك'=>'ک','ﻰ'=>'ی','ﻯ'=>'ی','ﻱ'=>'ی','ﻲ'=>'ی','ﻳ'=>'ی','ﻴ'=>'ی','ۀ'=>'ه','ة'=>'ه','ﻩ'=>'ه','ﻪ'=>'ه','أ'=>'ا','إ'=>'ا','آ'=>'آ','ؤ'=>'و','ئ'=>'ی','ﻻ'=>'لا','ﻷ'=>'لا'];
        foreach ($p as $f => $to) {
            if (mb_strpos($t, $f) !== false) {
                $c = 0;
                $t = str_replace($f, $to, $t, $c);
                if ($c > 0 && ($f === 'ي' || $f === 'ك')) $this->ch[] = ['type'=>'char','from'=>$f,'to'=>$to,'note'=>$f === 'ي' ? 'ی عربی به ی فارسی' : 'ک عربی به ک فارسی'];
            }
        }
        $t = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $t);
        $t = preg_replace('/\x{0640}+/u', '', $t);
        return $t;
    }
    private function normNums(string $t, string $tgt): string {
        $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $en = ['0','1','2','3','4','5','6','7','8','9'];
        $b = $t;
        if ($tgt === 'fa') { $t = str_replace($en, $fa, $t); $t = str_replace($ar, $fa, $t); }
        elseif ($tgt === 'en') { $t = str_replace($fa, $en, $t); $t = str_replace($ar, $en, $t); }
        if ($b !== $t) $this->ch[] = ['type'=>'number','to'=>$tgt,'note'=>'یکسان‌سازی ارقام'];
        return $t;
    }
    private function appDict(string $t): string {
        if (empty($this->d)) return $t;
        $parts = preg_split('/(\s+)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) return $t;
        $out = '';
        foreach ($parts as $p) {
            if ($p === '' || preg_match('/^\s+$/u', $p)) { $out .= $p; continue; }
            $l = ''; $tr = ''; $c = $p;
            if (preg_match('/^([^\p{L}\p{N}]*)(.*?)([^\p{L}\p{N}]*)$/u', $p, $m)) { $l = $m[1]; $c = $m[2]; $tr = $m[3]; }
            if ($c === '') { $out .= $p; continue; }
            $k = $this->nk($c);
            if (isset($this->d[$k])) {
                $f = $this->d[$k];
                if ($f !== $c) { $this->ch[] = ['type'=>'spelling','from'=>$c,'to'=>$f,'note'=>'غلط رایج']; $c = $f; }
            }
            $out .= $l . $c . $tr;
        }
        return $out;
    }
    private function appGram(string $t): string { $t = $this->fixZ($t); $t = $this->fixSp($t); $t = $this->fixP($t); $t = $this->unify($t); return $t; }
    private function fixZ(string $t): string {
        $z = "\xE2\x80\x8C";
        $t = preg_replace_callback('/\bمی\s+([\p{L}\x{200C}]{2,})/u', function ($m) use ($z) {
            $f = 'می' . $z . $m[1];
            $this->ch[] = ['type'=>'grammar','from'=>$m[0],'to'=>$f,'note'=>'نیم‌فاصله «می‌»'];
            return $f;
        }, $t);
        $t = preg_replace_callback('/\bنمی\s+([\p{L}\x{200C}]{2,})/u', function ($m) use ($z) {
            $f = 'نمی' . $z . $m[1];
            $this->ch[] = ['type'=>'grammar','from'=>$m[0],'to'=>$f,'note'=>'نیم‌فاصله «نمی‌»'];
            return $f;
        }, $t);
        $t = preg_replace_callback('/([\p{L}]{3,})\s+ها\b/u', function ($m) use ($z) {
            $f = $m[1] . $z . 'ها';
            $this->ch[] = ['type'=>'grammar','from'=>$m[0],'to'=>$f,'note'=>'نیم‌فاصله «ها»'];
            return $f;
        }, $t);
        $t = preg_replace_callback('/([\p{L}]{2,})\s+(تر|ترین)\b/u', function ($m) use ($z) {
            $f = $m[1] . $z . $m[2];
            $this->ch[] = ['type'=>'grammar','from'=>$m[0],'to'=>$f,'note'=>'نیم‌فاصله «تر/ترین»'];
            return $f;
        }, $t);
        return $t;
    }
    private function fixSp(string $t): string {
        $b = $t;
        $t = preg_replace('/([\)\]»}])([\p{L}])/u', '$1 $2', $t);
        $t = preg_replace('/([\p{L}])([\(\[\{«])/u', '$1 $2', $t);
        $t = preg_replace('/[ \t]+/u', ' ', $t);
        $t = preg_replace('/\s+([،؛:!؟\.\)\]\}»])/u', '$1', $t);
        $t = preg_replace('/([،؛:!؟\.\(\[\{«])(?=[^\s\d])/u', '$1 ', $t);
        $t = preg_replace('/([،؛])\s*/u', '$1 ', $t);
        $t = preg_replace('/[ \t]+\n/u', "\n", $t);
        $t = preg_replace('/\n[ \t]+/u', "\n", $t);
        if ($b !== $t) $this->ch[] = ['type'=>'grammar','note'=>'اصلاح فاصله‌گذاری'];
        return $t;
    }
    private function fixP(string $t): string {
        $b = $t;
        $t = preg_replace('/\.{3,}/u', '…', $t);
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $t)) {
            $t = preg_replace('/(?<=[\x{0600}-\x{06FF}])\s*,\s*/u', '، ', $t);
            $t = preg_replace('/(?<=[\x{0600}-\x{06FF}])\s*;\s*/u', '؛ ', $t);
        }
        if ($b !== $t) $this->ch[] = ['type'=>'grammar','note'=>'یکسان‌سازی نشانه‌گذاری'];
        return $t;
    }
    private function unify(string $t): string {
        $p = ['آنها'=>'آن‌ها','انها'=>'آن‌ها','اینا'=>'این‌ها','اونا'=>'آن‌ها','اون‌ها'=>'آن‌ها','اینها'=>'این‌ها','همه‌ی'=>'همهٔ','هیچ کدام'=>'هیچ‌کدام','هیچ کس'=>'هیچ‌کس','بخاطر'=>'به‌خاطر','درمورد'=>'در مورد','در باره'=>'درباره'];
        foreach ($p as $f => $to) $t = preg_replace('/' . preg_quote($f, '/') . '/u', $to, $t);
        return $t;
    }
    private function clean(string $t): string {
        $t = preg_replace('/[ ]{2,}/u', ' ', $t);
        $t = preg_replace('/\n{3,}/u', "\n\n", $t);
        return trim($t);
    }
}

/* ═══════════ AutoRatio ═══════════ */
final class AutoRatio {
    public static function compute(string $t, array $l, string $lc, array $s): float {
        $ln=(int)$s['length']; $sc=(int)$s['sentence_count']; $wc=(int)$s['word_count'];
        $aw=(int)$s['avg_words_per_sentence']; $pc=(int)$s['paragraph_count'];
        $dd=(float)$s['digit_density']; $ed=(float)$s['entity_density'];
        $qc=(int)$s['question_count']; $bc=(int)$s['bullet_count'];
        $rr=(float)$s['repeat_ratio']; $nr=(float)$s['noise_ratio'];
        $ir=(float)$s['intro_ratio']; $ll=(bool)$s['list_like']; $it=(bool)$s['informal_tone'];
        if ($ln < 60) $b = 0.70; elseif ($ln < 200) $b = 0.60; elseif ($ln < 600) $b = 0.55;
        elseif ($ln < 1500) $b = 0.50; elseif ($ln < 4000) $b = 0.45; elseif ($ln < 10000) $b = 0.42;
        else $b = 0.40;
        if ($sc <= 2) $b += 0.15; elseif ($sc <= 4) $b += 0.08; elseif ($sc <= 8) $b += 0.03;
        elseif ($sc >= 60) $b -= 0.02; elseif ($sc >= 30) $b -= 0.01;
        if ($aw > 0) {
            if ($aw < 6) $b -= 0.05; elseif ($aw < 10) $b -= 0.02;
            elseif ($aw > 30) $b += 0.05; elseif ($aw > 22) $b += 0.02;
        }
        if ($dd > 0.05) $b += 0.05; elseif ($dd > 0.02) $b += 0.02;
        if ($ed > 0.06) $b += 0.04; elseif ($ed > 0.03) $b += 0.02;
        if ($qc > 0 && $sc > 0) { $q = $qc / $sc; if ($q > 0.25) $b -= 0.08; elseif ($q > 0.10) $b -= 0.04; }
        if ($ll || $bc >= 3) $b -= 0.03;
        if ($rr > 0.20) $b -= 0.10; elseif ($rr > 0.10) $b -= 0.05; elseif ($rr > 0.05) $b -= 0.02;
        if ($nr > 0.30) $b -= 0.08; elseif ($nr > 0.15) $b -= 0.04;
        if ($ir > 0.15) $b += 0.05; elseif ($ir > 0.08) $b += 0.02;
        if ($it) $b -= 0.03;
        if ($pc >= 5 && !$it) $b += 0.03;
        if (preg_match('/(درس|فصل|بخش|فلسفه|تاریخ|علمی|آموزشی|تعریف|مفهوم|نکته|نمونه)/u', $t)) $b += 0.05;
        $p = preg_match_all('/(سقراط|افلاطون|ارسطو|هراکلیتوس|پارمنیدس|فیثاغورس|تالس|سوفیست|سهروردی|ابن سینا|فارابی|ملاصدرا|دکارت|کانت|هگل|نیچه)/u', $t);
        if ($p >= 3) $b += 0.05; elseif ($p >= 1) $b += 0.02;
        $pEn = preg_match_all('/(Socrates|Plato|Aristotle|Heraclitus|Parmenides|Pythagoras|Thales|Sophist|Descartes|Kant|Hegel|Nietzsche|philosophy|history|science|education|definition|concept)/ui', $t);
        if ($pEn >= 3) $b += 0.05; elseif ($pEn >= 1) $b += 0.02;
        $b = max(0.20, min(0.85, $b));
        $r = round($b * 20) / 20;
        return max(0.20, min(0.85, $r));
    }
    public static function analyze(string $t, string $lc): array {
        $ln = mb_strlen($t);
        preg_match_all('/[^\s]+/u', $t, $wm);
        $wc = count($wm[0]);
        $ss = preg_split('/(?<=[.!?؟。！？])\s+/u', $t);
        $ss = array_values(array_filter($ss, function ($x) { return trim($x) !== ''; }));
        $sc = max(1, count($ss));
        $ps = preg_split('/\n\s*\n/u', $t);
        $ps = array_values(array_filter($ps, function ($x) { return trim($x) !== ''; }));
        $pc = max(1, count($ps));
        $aw = (int)round($wc / $sc);
        $dm = preg_match_all('/[\d۰-۹]+/u', $t);
        $dd = $ln > 0 ? $dm / max(1, $wc) : 0;
        $em = preg_match_all('/\b[A-Z][a-z]{2,}\b/u', $t);
        $em += preg_match_all('/(مدنند|مخالف|ارای مخالف|از جمله|ادامه|شامل)/u', $t);
        $ed = $wc > 0 ? $em / $wc : 0;
        $qc = preg_match_all('/[?؟]/u', $t);
        $bc = preg_match_all('/^\s*[•\-*▪◦‣⁃]\s+/mu', $t);
        $seen = []; $dup = 0;
        foreach ($ss as $x) {
            $n = preg_replace('/\s+/u', ' ', trim($x));
            $n = mb_substr($n, 0, 80);
            if ($n === '') continue;
            $h = md5($n);
            if (isset($seen[$h])) $dup++; else $seen[$h] = true;
        }
        $rr = $sc > 0 ? $dup / $sc : 0;
        $ls = preg_split('/\n/u', $t);
        $no = 0; $to = 0;
        foreach ($ls as $x) {
            $x = trim($x);
            if ($x === '') continue;
            $to++;
            $wc2 = count(preg_split('/\s+/u', $x));
            if ($wc2 <= 2 || preg_match('/^[\s\-_=~*#•·─━═]+$/u', $x)) $no++;
        }
        $nr = $to > 0 ? $no / $to : 0;
        $in = 0;
        foreach ($ss as $x) if (preg_match('/(مقدمه از|نوعی از|عبارت است از|را .{1,30} می‌نامند|را .{1,30} گویند|تعریف|گفته می‌شود|محلول می‌نامند|ممکن است|می‌توانند|is defined as|refers to|means that|is called|it is said|may be|can be)/ui', $x)) $in++;
        $ir = $sc > 0 ? $in / $sc : 0;
        $it = false;
        if ($lc === 'fa') {
            $ih = preg_match_all('/(می‌خوام|می‌تونم|می‌کنم|دخترا|پسرا|بچه‌ها|داداش|سلام|خوبی|چطوری|دوست داری|بگو|بیا|برو|بخند|چیه|کیه)/u', $t);
            $it = $ih >= 2 || ($wc > 0 && $ih / max(1, $wc) > 0.04);
        } elseif ($lc === 'en') {
            $ih = preg_match_all('/(gonna|wanna|gotta|yeah|yep|nope|hey|hi|hello|dude|buddy|cool|awesome|lol|ok|okay|kinda|sorta|ain\'t|can\'t|won\'t|don\'t)/ui', $t);
            $it = $ih >= 2 || ($wc > 0 && $ih / max(1, $wc) > 0.04);
        }
        $ll = ($bc >= 3) || (preg_match_all('/^\s*[\d۰-۹]+[\.\)]/mu', $t) >= 3);
        return ['length'=>$ln,'word_count'=>$wc,'sentence_count'=>$sc,'paragraph_count'=>$pc,'avg_words_per_sentence'=>$aw,'digit_density'=>$dd,'entity_density'=>$ed,'question_count'=>$qc,'bullet_count'=>$bc,'repeat_ratio'=>$rr,'noise_ratio'=>$nr,'intro_ratio'=>$ir,'list_like'=>$ll,'informal_tone'=>$it];
    }
}

/* ═══════════ WeightEngine load ═══════════ */
if (!class_exists('WeightEngine', false)) {
    $__wf = __DIR__ . '/Weight.php';
    if (is_file($__wf)) require_once $__wf;
    unset($__wf);
}

if (!class_exists('WeightEngine', false)) {
    final class WeightEngine {
        public function __construct(?string $lang = 'fa', string $path = '') {}
        public function score(array $sentences, array $options = []): array {
            $w = [];
            foreach ($sentences as $i => $_) $w[$i] = 0.5;
            return ['success'=>true,'weights'=>$w,'topic_scores'=>[],'dominant_topic'=>null,'dominant_topics'=>[],'signals'=>array_fill(0,count($sentences),[]),'doc_type'=>'general','diagnostics'=>['reason'=>'stub_engine']];
        }
        public function scoreText(string $text, array $options = []): array { return $this->score([$text], $options); }
        public function scoreSentence(string $sentence): array { return ['weight'=>0.5,'signals'=>[],'topics'=>[]]; }
        public function listTopics(): array { return []; }
        public function detectTopics(string $text, int $top = 5): array { return []; }
    }
}

/* ═══════════ SemanticBridge ═══════════ */
final class SemanticBridge
{
    private static ?bool $available = null;
    private static int $checkedAt = 0;

    public static function isAvailable(): bool
    {
        if (!SEMANTIC_ENABLED) return false;
        $now = time();
        if (self::$available !== null && ($now - self::$checkedAt) < SEMANTIC_AVAIL_TTL) {
            return self::$available;
        }
        self::$checkedAt = $now;
        $r = self::request('/health', [], 1.5);
        self::$available = is_array($r) && !empty($r['ok']);
        return self::$available;
    }

    public static function scoreSentences(array $sentences): ?array
    {
        if (count($sentences) < 3) return null;
        if (count($sentences) > SEMANTIC_MAX_SENT) {
            $sentences = array_slice($sentences, 0, SEMANTIC_MAX_SENT);
        }
        $joined = implode(' ', $sentences);
        if (mb_strlen($joined) < SEMANTIC_MIN_LEN) return null;

        $r = self::request('/score', ['sentences' => array_values($sentences)], SEMANTIC_TIMEOUT);
        if (!is_array($r) || empty($r['ok'])) return null;

        $w = $r['weights'] ?? [];
        $c = $r['clusters'] ?? [];
        if (!is_array($w) || count($w) !== count($sentences)) return null;

        $out = ['weights' => [], 'clusters' => []];
        foreach ($sentences as $i => $_) {
            $out['weights'][$i] = max(0.0, min(1.0, (float)($w[$i] ?? 0.0)));
            $out['clusters'][$i] = (int)($c[$i] ?? 0);
        }
        return $out;
    }

    private static function request(string $path, array $body, float $timeout): ?array
    {
        $url = rtrim(SEMANTIC_ENDPOINT, '/') . $path;
        $payload = empty($body) ? null : json_encode($body, JSON_UNESCAPED_UNICODE);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int)ceil($timeout),
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_POST           => $payload !== null,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            ]);
            $res = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res === false || $code < 200 || $code >= 300) return null;
            $d = json_decode((string)$res, true);
            return is_array($d) ? $d : null;
        }
        if ($payload === null) return null;
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => $timeout,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) return null;
        $d = json_decode($res, true);
        return is_array($d) ? $d : null;
    }
}

/* ═══════════ AbstractiveBridge ═══════════ */
final class AbstractiveBridge
{
    private static ?bool $available = null;
    private static int $checkedAt = 0;

    public static function isAvailable(): bool
    {
        if (!ABSTRACTIVE_ENABLED) return false;
        $now = time();
        if (self::$available !== null && ($now - self::$checkedAt) < ABSTRACTIVE_AVAIL_TTL) {
            return self::$available;
        }
        self::$checkedAt = $now;
        $r = self::request('/health', [], 1.5);
        self::$available = is_array($r) && !empty($r['ok']);
        return self::$available;
    }

    public static function summarize(array $sentences, array $opts = []): ?string
    {
        if (empty($sentences)) return null;
        $joined = implode(' ', $sentences);
        $len = mb_strlen($joined);
        if ($len < ABSTRACTIVE_MIN_INPUT) return null;
        if ($len > ABSTRACTIVE_MAX_INPUT) $joined = mb_substr($joined, 0, ABSTRACTIVE_MAX_INPUT);

        $body = [
            'sentences' => array_values($sentences),
            'lang'      => $opts['lang'] ?? 'fa',
            'ratio'     => $opts['ratio'] ?? ABSTRACTIVE_RATIO,
            'doctype'   => $opts['doctype'] ?? 'general',
        ];
        $r = self::request('/summarize', $body, ABSTRACTIVE_TIMEOUT);
        if (!is_array($r) || empty($r['ok'])) return null;
        $s = trim((string)($r['summary'] ?? ''));
        if ($s === '' || mb_strlen($s) < 20) return null;
        return $s;
    }

    private static function request(string $path, array $body, float $timeout): ?array
    {
        $url = rtrim(ABSTRACTIVE_ENDPOINT, '/') . $path;
        $payload = empty($body) ? null : json_encode($body, JSON_UNESCAPED_UNICODE);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int)ceil($timeout),
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_POST           => $payload !== null,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            ]);
            $res = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($res === false || $code < 200 || $code >= 300) return null;
            $d = json_decode((string)$res, true);
            return is_array($d) ? $d : null;
        }
        if ($payload === null) return null;
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => $timeout,
            ],
        ]);
        $res = @file_get_contents($url, false, $ctx);
        if ($res === false) return null;
        $d = json_decode($res, true);
        return is_array($d) ? $d : null;
    }
}

/* ═══════════ weight_detect_lang ═══════════ */
if (!function_exists('weight_detect_lang')) {
    function weight_detect_lang(string $text): string {
        $fa = preg_match_all('/[\x{0600}-\x{06FF}]/u', $text);
        $en = preg_match_all('/[a-zA-Z]/u', $text);
        $tot = $fa + $en;
        if ($tot === 0) return 'fa';
        if ($fa === 0) return 'en';
        $ratio = $fa / max(1, $tot);
        if ($ratio >= 0.30) return 'fa';
        return 'en';
    }
}

/* ═══════════ weight_boost_scores ═══════════ */
if (!function_exists('weight_boost_scores')) {
    function weight_boost_scores(array $sentences, array $baseScores, float $alpha = WEIGHT_BOOST_ALPHA): array
    {
        if (!class_exists('WeightEngine', false)) return $baseScores;
        if (empty($sentences) || empty($baseScores)) return $baseScores;
        if (count($sentences) > WEIGHT_BOOST_MAX_SENT) return $baseScores;
        if (mb_strlen(implode(' ', $sentences)) < 75) return $baseScores;

        $joined = implode(' ', $sentences);
        $wLang = weight_detect_lang($joined);

        static $cache = [];
        $sample = '';
        $n = count($sentences);
        $step = max(1, (int)($n / 8));
        for ($i = 0; $i < $n; $i += $step) $sample .= $sentences[$i] . "\x1F";
        $key = crc32($sample) . ':' . $n . ':' . $alpha . ':' . $wLang;

        if (isset($cache[$key])) return $cache[$key];

        try {
            $engine = new WeightEngine($wLang, WEIGHT_LANG_PATH);
            $result = $engine->score($sentences);
            if (($result['diagnostics']['reason'] ?? '') === 'stub_engine') return $baseScores;
            $w = $result['weights'] ?? [];
            if (!is_array($w) || count($w) !== count($sentences)) return $baseScores;

            $vals = array_values($w);
            sort($vals);
            $n = count($vals);
            $p10 = $vals[max(0, (int)($n * 0.10))] ?? 0.0;
            $p90 = $vals[min($n - 1, (int)($n * 0.90))] ?? 1.0;
            $range = $p90 - $p10;

            // ★ رژیم توزیع باریک: w را همان‌طور که هست با base ترکیب کن
            // (بدون نرمال‌سازی دوم که نویز را تقویت می‌کند)
            if ($range < 0.05) {
                $out = $baseScores;
                foreach ($baseScores as $i => $base) {
                    $raw = $w[$i] ?? 0.0;
                    $val = (1.0 - $alpha) * (float)$base + $alpha * $raw;
                    $out[$i] = max(0.0, min(1.0, $val));
                }
                if (count($cache) > 64) $cache = array_slice($cache, -32, null, true);
                $cache[$key] = $out;
                return $out;
            }

            // رژیم توزیع پهن: نرمال percentile سخت‌گیرانه
            $out = $baseScores;
            foreach ($baseScores as $i => $base) {
                $raw = $w[$i] ?? 0.0;
                $norm = max(0.0, min(1.0, ($raw - $p10) / $range));
                $val = (1.0 - $alpha) * (float)$base + $alpha * $norm;
                $out[$i] = max(0.0, min(1.0, $val));
            }

            if (count($cache) > 64) $cache = array_slice($cache, -32, null, true);
            $cache[$key] = $out;
            return $out;
        } catch (Throwable $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('[weight_boost_scores] ' . $e->getMessage());
            }
            return $baseScores;
        }
    }
}

/* ═══════════ tri_boost_scores ═══════════ */
if (!function_exists('tri_boost_scores')) {
    function tri_boost_scores(array $sentences, array $baseScores, ?array &$outClusters = null): array
    {
        $weights = weight_boost_scores($sentences, $baseScores, WEIGHT_BOOST_ALPHA);
        $outClusters = null;

        if (!SemanticBridge::isAvailable()) return $weights;

        $sem = SemanticBridge::scoreSentences($sentences);
        if ($sem === null) return $weights;

        $semW = $sem['weights'];
        if (count($semW) !== count($sentences)) return $weights;

        $alpha = SEMANTIC_ALPHA;
        $out = $weights;
        foreach ($weights as $i => $base) {
            $sv = $semW[$i] ?? 0.0;
            $val = (1.0 - $alpha) * (float)$base + $alpha * $sv;
            $out[$i] = max(0.0, min(1.0, $val));
        }

        $outClusters = $sem['clusters'];
        return $out;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   ✅ Summarizer 3.0 — موتور خلاصه‌سازی حرفه‌ای
   ═══════════════════════════════════════════════════════════════════════════ */
final class Summarizer {
    private $lang, $lc;
    private $ss=[], $ps=[], $sp=[], $tk=[], $st=[], $v=[], $eb=[], $df=[], $sc=[];
    private $kp=[], $sks=[], $tp=[], $stp=[], $hlp=[], $rb=[], $ct=[], $es=[];
    private $posS=[], $lenS=[], $isI=[], $isB=[], $lm=false;
    private $rr2=false, $t0, $mode='mid';

    private $ED=384, $TD=0.85, $TI=50, $ML=0.65, $MSW=5, $MXSW=70, $IW=22;
    private $RMP=4, $TST=0.10, $MTS=2, $TQW=0.40, $MXS=5000, $MXP=2000;
    private $BM25_K1=1.5, $BM25_B=0.75, $MAX_PASSES=4, $MINL=75;

    private $stp2=[], $up=[], $ar=null, $mki=[], $ivs=[], $head=[], $bnd=[], $mk=[];
    private $entCache=[], $docType='general';
    private $weightBoosted=false, $semanticApplied=false, $abstractiveApplied=false;
    private $semClusters=null;

    private $bm25=[], $idfCache=[], $avgDocLen=0.0;
    private $passes=0;

    private const CACHE_ENT_MAX = 512;
    private const CACHE_BM25_IDF = 2048;

    public function __construct() { $this->t0 = microtime(true); }

    private function finalize(array $x): array {
        if (!empty($x['success'])) msg_counter_increment();
        return $x;
    }

    public function run(string $t, ?float $r = null, ?string $lc = null): array {
        try {
            return $this->runInternal($t, $r, $lc);
        } catch (Throwable $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('[Summarizer::run] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            }
            $fallback = $this->safeFallback($t);
            $this->lc = $this->lc ?: 'fa';
            $this->lang = LangData::get($this->lc);
            $x = $this->bRes($t, $fallback, $r ?? 0.5, 0, 0, 'fallback');
            $x['stats']['error_recovered'] = true;
            $x['stats']['error_message'] = $e->getMessage();
            return $x;
        }
    }

    private function safeFallback(string $t): string {
        $clean = sanitize_extracted_text($t);
        $parts = preg_split('/(?<=[.!?؟。！？])\s+/u', $clean);
        if (!is_array($parts)) return mb_substr($clean, 0, 200);
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') continue;
            $out[] = $p;
            if (count($out) >= 2) break;
        }
        $s = implode(' ', $out);
        if (mb_strlen($s) < 20) $s = mb_substr($clean, 0, 200);
        return $s;
    }

    private function resetRunState(): void {
        $this->ss=[]; $this->ps=[]; $this->sp=[]; $this->tk=[]; $this->st=[];
        $this->v=[]; $this->eb=[]; $this->df=[]; $this->sc=[]; $this->kp=[];
        $this->sks=[]; $this->tp=[]; $this->stp=[]; $this->hlp=[]; $this->rb=[];
        $this->ct=[]; $this->es=[]; $this->posS=[]; $this->lenS=[]; $this->isI=[];
        $this->isB=[]; $this->stp2=[]; $this->up=[]; $this->ar=null; $this->mki=[];
        $this->ivs=[]; $this->head=[]; $this->bnd=[]; $this->mk=[]; $this->entCache=[];
        $this->bm25=[]; $this->idfCache=[]; $this->avgDocLen=0.0; $this->passes=0;
        $this->docType='general'; $this->weightBoosted=false; $this->semanticApplied=false;
        $this->abstractiveApplied=false; $this->semClusters=null; $this->lm=false;
        $this->rr2=false; $this->mode='mid'; $this->t0=microtime(true);
    }

    private function runInternal(string $t, ?float $r, ?string $lc): array {
        $this->resetRunState();
        $this->lc = $lc ?: LangData::detect($t);
        $this->lang = LangData::get($this->lc);
        $orig = $t;
        $t = $this->normEsc($t);
        $this->up = [];
        $t = $this->protUrls($t);
        $t = $this->protectMarkup($t);
        $t = $this->aggClean($t);
        $t = $this->stripMeta($t);
        $t = $this->stripNoise($t);
        $t = trim($t);
        $tl = mb_strlen($t);
        $this->detMode($tl);
        $at = str_replace(
            array_merge(array_keys($this->up), array_keys($this->mk)),
            array_merge(array_values($this->up), array_values($this->mk)),
            $t
        );
        $st = AutoRatio::analyze($at, $this->lc);
        $ar = AutoRatio::compute($at, $this->lang, $this->lc, $st);
        $this->ar = $ar;
        $rl = mb_strlen(trim($orig));
        if ($rl < $this->MINL) return $this->notSumResult($orig, $rl);
        if ($r === null) $r = $ar;
        elseif ($this->rr2) { if ($r < 0.55) $r = 0.55; elseif ($r > 0.70) $r = 0.70; }
        $r = max(0.15, min(0.90, (float)$r));

        if ($tl === 0) {
            $x = $this->bRes($orig, $orig, $r, 0, 0, $this->mode);
            $x['stats']['auto_ratio'] = $ar;
            $x['stats']['analysis'] = $this->pubStats($st);
            return $this->finalize($x);
        }

        $this->splitS($t);
        $n = count($this->ss);

        if ($n === 0) {
            $x = $this->bRes($orig, $t, $r, 0, 0, $this->mode);
            $x['stats']['auto_ratio'] = $ar;
            $x['stats']['analysis'] = $this->pubStats($st);
            return $this->finalize($x);
        }

        if ($n === 1) {
            $s = $this->resUrls($this->restoreMarkup($this->ss[0]));
            if (mb_strlen($s) < 30 || $r >= 0.85) {
                $x = $this->bRes($orig, $s, $r, 1, 1, $this->mode);
                $x['stats']['auto_ratio'] = $ar;
                $x['stats']['analysis'] = $this->pubStats($st);
                return $this->finalize($x);
            }
            $c = $this->compressSentence($s, $r);
            $x = $this->bRes($orig, $c, $r, 1, 1, $this->mode);
            $x['stats']['auto_ratio'] = $ar;
            $x['stats']['analysis'] = $this->pubStats($st);
            return $this->finalize($x);
        }

        if ($n === 2) {
            $this->scoreBasic();
            $this->sc = tri_boost_scores($this->ss, $this->sc, $this->semClusters);
            if ($this->semClusters !== null) $this->semanticApplied = true;
            $k = $this->compK(2, $r);
            $sel = $this->selInit($k);
            sort($sel);
            if ($r < 0.5 && count($sel) === 1) {
                $c = $this->compressSentence($this->resUrls($this->restoreMarkup($this->ss[$sel[0]])), $r);
                $x = $this->bRes($orig, $c, $r, 2, 1, $this->mode);
                $x['stats']['auto_ratio'] = $ar;
                $x['stats']['analysis'] = $this->pubStats($st);
                return $this->finalize($x);
            }
            if ($r >= 0.85) {
                $joined = $this->restoreMarkup(implode(' ', $this->ss));
                $x = $this->bRes($orig, $this->resUrls($joined), $r, 2, 2, $this->mode);
                $x['stats']['auto_ratio'] = $ar;
                $x['stats']['analysis'] = $this->pubStats($st);
                return $this->finalize($x);
            }
            $j = $this->joinSec($sel);
            $x = $this->bRes($orig, $j, $r, 2, count($sel), $this->mode);
            $x['stats']['auto_ratio'] = $ar;
            $x['stats']['analysis'] = $this->pubStats($st);
            return $this->finalize($x);
        }

        /* ─── Pipeline اصلی ─── */
        $this->tok();
        $this->buildVectors();
        $this->buildEmbeddings();
        if (SUMMARIZER_ENABLE_BM25) $this->buildBM25();
        $this->extKP();
        $this->segTop();
        $this->detCoref();
        $this->scoreEnt();
        $this->detIB();
        $this->classAll();
        $this->detMKS();
        $this->detHeadings();
        $this->detNonSumm();
        $this->detDocType();
        $this->scoreSS();

        $k = $this->compK($n, $r);
        $k = max(1, min($n, $k));

        $sel = $this->selInit($k);
        $sel = $this->balBul($sel, $k);
        $sel = $this->fillTQ($sel, $k);
        $sel = $this->detQA($sel);
        $sel = $this->resCG($sel);
        $sel = $this->remRedClusters($sel);
        $sel = $this->remNonSumm($sel);
        $sel = $this->balTop($sel, $k);
        $sel = $this->remOrph($sel);
        $sel = $this->enfR($sel, $n, $r);
        $sel = $this->ensMin($sel, $n, $tl);
        $sel = $this->mrgFrag($sel);
        $sel = $this->ordRead($sel);
        $sel = $this->coherenceRepair($sel, $n, $r);
        // Adaptive coverage repair: spend a small part of the compression budget
        // on high-information facts that the first-pass ranking omitted.
        $sel = $this->coverageRepair($sel, $k, $n, $r);
        // Preserve the logical skeleton: thesis/premises/objections/replies/conclusion
        // for philosophical texts, and definition/fact/cause/effect/conclusion for general texts.
        $sel = $this->structureRepair($sel, $n, $r);

        $sm = $this->compSum($sel);

        /* ─── Multi-pass MMR ─── */
        $this->passes = 1;
        while ($this->passes < $this->MAX_PASSES) {
            $ratio = $tl > 0 ? mb_strlen($sm) / $tl : 1.0;
            if ($ratio <= 0.75) break;
            $newSel = $this->mmrPrune($sel, $n, $r);
            if ($newSel === null || count($newSel) >= count($sel)) break;
            $sm2 = $this->compSum($newSel);
            $beforeQuality = $this->assessQuality($sel, $sm);
            $afterQuality = $this->assessQuality($newSel, $sm2);
            $coverageSafe = ($afterQuality['coverage'] ?? 0) >= (($beforeQuality['coverage'] ?? 0) - 2.0);
            $factsSafe = ($afterQuality['accuracy_proxy'] ?? 0) >= (($beforeQuality['accuracy_proxy'] ?? 0) - 2.0);
            $structureSafe = ($afterQuality['argument_structure'] ?? 0) >= (($beforeQuality['argument_structure'] ?? 0) - 3.0);
            if (mb_strlen(trim($sm2)) >= 20 && mb_strlen($sm2) < mb_strlen($sm)
                && $coverageSafe && $factsSafe && $structureSafe) {
                $sm = $sm2;
                $sel = $newSel;
                $this->passes++;
            } else {
                break;
            }
        }

        /* ─── Fallback ─── */
        if (mb_strlen(trim($sm)) < 20) {
            $ranked = $this->sc;
            arsort($ranked);
            $fb = [];
            foreach (array_keys($ranked) as $i) {
                if (count($fb) >= 3) break;
                $tx = trim($this->resUrls($this->restoreMarkup($this->ss[$i])));
                if ($tx !== '') $fb[$i] = $tx;
            }
            ksort($fb);
            $sm = implode(' ', $fb);
        }
        if (mb_strlen(trim($sm)) < 20) {
            $sm = mb_substr($orig, 0, 200);
            if (mb_strlen($orig) > 200) $sm .= '...';
        }

        /* ─── Abstractive ─── */
        $extractedSentences = [];
        foreach ($sel as $i) {
            if (isset($this->ss[$i])) $extractedSentences[] = $this->restoreMarkup($this->ss[$i]);
        }
        if (AbstractiveBridge::isAvailable() && !empty($extractedSentences)) {
            $abs = AbstractiveBridge::summarize($extractedSentences, [
                'lang'    => $this->lc,
                'ratio'   => $r,
                'doctype' => $this->docType,
            ]);
            if ($abs !== null) {
                $absClean = $this->resUrls($this->restoreMarkup($abs));
                if (mb_strlen($absClean) >= 30 && $this->validateAbstractive($absClean, $extractedSentences)) {
                    $sm = $absClean;
                    $this->abstractiveApplied = true;
                }
            }
        }

        /* ─── Quality assessment ─── */
        $quality = $this->assessQuality($sel, $sm);

        $x = $this->bRes($orig, $sm, $r, $n, count($sel), $this->mode);
        $x['stats']['auto_ratio'] = $ar;
        $x['stats']['analysis'] = $this->pubStats($st);
        $x['stats']['weight_boosted'] = $this->weightBoosted;
        $x['stats']['weight_alpha'] = WEIGHT_BOOST_ALPHA;
        $x['stats']['semantic_applied'] = $this->semanticApplied;
        $x['stats']['semantic_alpha'] = SEMANTIC_ALPHA;
        $x['stats']['abstractive_applied'] = $this->abstractiveApplied;
        $x['stats']['passes'] = $this->passes;
        $x['stats']['doc_type'] = $this->docType;
        $x['stats']['bm25_enabled'] = SUMMARIZER_ENABLE_BM25;
        $x['stats']['quality'] = $quality;
        $x['stats']['version'] = SUMMARIZER_VERSION;
        return $this->finalize($x);
    }

    private function coherenceRepair(array $sel, int $n, float $r): array {
        $sel = array_values(array_unique($sel));
        sort($sel);
        if (count($sel) < 2) return $sel;
        $limit = min($n, max(count($sel), (int)ceil($n * min(0.85, max(0.45, $r) + 0.20))));
        $markers = '/^(با این حال|با وجود این|از سوی دیگر|در مقابل|اما|ولی|زیرا|چون|بنابراین|در نتیجه|از این رو|به همین دلیل|در نهایت|همچنین|علاوه بر این|با فرض|فرض کنیم|در پاسخ|اعتراض|نقد|however|nevertheless|nonetheless|on the other hand|in contrast|but|because|therefore|thus|consequently|as a result|moreover|furthermore|in addition|objection|counterargument|in reply|premise)\b/ui';
        $add = [];
        for ($p = 1; $p < count($sel); $p++) {
            $left = $sel[$p - 1]; $right = $sel[$p];
            if ($right - $left <= 1) continue;
            for ($candidate = $left + 1; $candidate < $right; $candidate++) {
                $isDiscourseBridge = (bool)preg_match($markers, trim($this->ss[$candidate] ?? ''));
                $hasAntecedent = !empty($this->hlp[$candidate])
                    && isset($this->ct[$candidate])
                    && $this->ct[$candidate] !== null
                    && in_array($this->ct[$candidate], $sel, true);
                if ($isDiscourseBridge || $hasAntecedent) {
                    $add[] = $candidate;
                    break;
                }
            }
        }
        foreach ($add as $idx) {
            if (count($sel) >= $limit) break;
            if (!in_array($idx, $sel, true)) $sel[] = $idx;
        }
        sort($sel);
        return array_values(array_unique($sel));
    }

    private function coverageRepair(array $sel, int $k, int $n, float $r): array {
        if ($n <= 2 || count($sel) >= $n) return array_values(array_unique($sel));
        $sel = array_values(array_unique($sel));

        $df = [];
        foreach ($this->st as $terms) {
            foreach (array_unique($terms) as $term) $df[$term] = ($df[$term] ?? 0) + 1;
        }
        if (!$df) return $sel;

        $weights = [];
        $totalWeight = 0.0;
        foreach ($df as $term => $freq) {
            $weights[$term] = 1.0 + log(1.0 + $n / max(1, $freq));
            $totalWeight += $weights[$term];
        }
        $covered = [];
        foreach ($sel as $idx) foreach ($this->st[$idx] ?? [] as $term) $covered[$term] = true;

        $coverage = function () use (&$covered, $weights, $totalWeight): float {
            if ($totalWeight <= 0) return 1.0;
            $sum = 0.0;
            foreach ($covered as $term => $_) $sum += $weights[$term] ?? 0.0;
            return min(1.0, $sum / $totalWeight);
        };

        // Permit up to 15 percentage points of additional source sentences,
        // but never exceed the normal 85% extraction ceiling.
        $coverageHeadroom = ($this->docType === 'philosophy') ? 0.25 : 0.20;
        $coverageCeiling = ($this->docType === 'philosophy') ? 0.90 : 0.88;
        $coverageGoal = ($this->docType === 'philosophy') ? 0.94 : 0.92;
        $targetCount = max($k, (int)ceil($n * min($coverageCeiling, max(0.40, $r) + $coverageHeadroom)));
        $targetCount = min($n, $targetCount);
        $currentCoverage = $coverage();

        while (count($sel) < $targetCount && $currentCoverage < $coverageGoal) {
            $best = null;
            $bestGain = 0.0;
            foreach ($this->ss as $idx => $sentence) {
                if (in_array($idx, $sel, true)) continue;
                $gain = 0.0;
                foreach (array_unique($this->st[$idx] ?? []) as $term) {
                    if (!isset($covered[$term])) $gain += $weights[$term] ?? 0.0;
                }
                if ($gain <= 0.0) continue;

                $type = $this->stp2[$idx]['type'] ?? 'normal';
                $bonus = 0.0;
                if (in_array($type, ['statistical','definition','conclusion','classification'], true)) $bonus += 0.08;
                if ($this->docType === 'philosophy' && preg_match('/(تز|مدعا|دعوی|استدلال|مقدمه|فرض|اصل|تعریف|مفهوم|ماهیت|چیستی|اعتراض|نقد|پاسخ|نتیجه|بنابراین|thesis|claim|argument|premise|assumption|principle|definition|concept|nature|objection|counterargument|reply|conclusion|therefore)/ui', mb_strtolower($sentence))) $bonus += 0.22;
                if (!empty($this->head[$idx]) || in_array($idx, $this->mki, true)) $bonus += 0.10;
                foreach ($sel as $chosenIdx) {
                    if (abs($idx - $chosenIdx) === 1) { $bonus += 0.12; break; }
                }
                if (!empty($this->hlp[$idx]) && !empty($this->ct[$idx])) {
                    if (!in_array($this->ct[$idx], $sel, true)) $bonus -= 0.05;
                }
                $base = $this->sc[$idx] ?? 0.0;
                $gainScore = $gain * (1.0 + $bonus) + 0.025 * max(0.0, $base);
                if ($gainScore > $bestGain) {
                    $bestGain = $gainScore;
                    $best = $idx;
                }
            }
            if ($best === null) break;
            $sel[] = $best;
            foreach (array_unique($this->st[$best] ?? []) as $term) $covered[$term] = true;
            $currentCoverage = $coverage();
        }

        sort($sel);
        return array_values(array_unique($sel));
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Multi-pass MMR pruning
       ═══════════════════════════════════════════════════════════════════════ */
    private function mmrPrune(array $sel, int $n, float $r): ?array {
        $target = max(2, (int)ceil($n * min($r, 0.55)));
        if (count($sel) <= $target) return null;
        $ranked = [];
        foreach ($sel as $s) $ranked[$s] = $this->sc[$s] ?? 0;
        arsort($ranked);
        $kept = [];
        foreach (array_keys($ranked) as $s) {
            if (count($kept) >= $target) break;
            $maxSim = 0.0;
            foreach ($kept as $k) {
                $sim = $this->comb($s, $k);
                if ($sim > $maxSim) $maxSim = $sim;
            }
            $mmr = $this->ML * ($this->sc[$s] ?? 0) - (1 - $this->ML) * $maxSim;
            $locked = ($s === 0 || $s === $n - 1 || !empty($this->head[$s])
                       || in_array($s, $this->mki, true));
            if ($locked || $mmr > -0.5) {
                $kept[] = $s;
            }
        }
        if (count($kept) >= count($sel)) return null;
        sort($kept);
        return $kept;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Sentence compressor برای n=1,2
       ═══════════════════════════════════════════════════════════════════════ */
    private function compressSentence(string $s, float $r): string {
        $w = preg_split('/[\s\x{200C}]+/u', $s);
        if (!is_array($w) || count($w) < 4) return $s;
        $w = array_values(array_filter($w, function ($x) { return $x !== '' && $x !== null; }));
        if (count($w) < 4) return $s;
        $sw = LangData::mergedStopwords();
        $cl = [];
        $hasQuote = (bool)preg_match('/[:؛]\s*["«]/u', $s);
        $hasList = (bool)preg_match('/[•\-*▪◦‣⁃]/u', $s);
        $protected = ($hasQuote || $hasList);
        $softMax = $protected ? 0.78 : max(0.45, $r);
        foreach ($w as $x) {
            $nn = preg_replace('/[^\p{L}\p{N}]/u', '', $x);
            $nl = mb_strtolower($nn);
            $ln = mb_strlen($nn);
            $iss = isset($sw[$nl]);
            $isNum = (bool)preg_match('/^[\d۰-۹]+$/u', $nn);
            $isUrl = (bool)preg_match('#^(URLPH|MKPH)\d+X$#', $nn);
            $c = 'filler';
            if ($isUrl) $c = 'essential';
            elseif ($isNum) $c = 'essential';
            elseif ($ln >= 6) $c = 'essential';
            elseif ($ln >= 5 && !$iss) $c = 'essential';
            elseif ($ln >= 4 && !$iss) $c = 'important';
            elseif ($iss) $c = 'connector';
            elseif ($ln >= 3) $c = 'normal';
            $cl[] = ['raw'=>$x, 'class'=>$c];
        }
        $tw = count($cl);
        $kt = max(3, (int)round($tw * $softMax));
        $kt = min($kt, $tw);
        $ki = [];
        foreach ($cl as $i => $x) if ($x['class'] === 'essential') $ki[] = $i;
        if (count($ki) < $kt) foreach ($cl as $i => $x) {
            if (count($ki) >= $kt) break;
            if (in_array($i, $ki, true)) continue;
            if ($x['class'] === 'important') $ki[] = $i;
        }
        if (count($ki) < $kt) foreach ($cl as $i => $x) {
            if (count($ki) >= $kt) break;
            if (in_array($i, $ki, true)) continue;
            if ($x['class'] !== 'connector') continue;
            $hn = false;
            if ($i > 0 && in_array($i - 1, $ki, true)) $hn = true;
            if ($i < $tw - 1 && in_array($i + 1, $ki, true)) $hn = true;
            if ($hn) $ki[] = $i;
        }
        if (count($ki) < $kt) foreach ($cl as $i => $x) {
            if (count($ki) >= $kt) break;
            if (in_array($i, $ki, true)) continue;
            if ($x['class'] === 'normal') $ki[] = $i;
        }
        if (count($ki) < $kt) foreach ($cl as $i => $x) {
            if (count($ki) >= $kt) break;
            if (in_array($i, $ki, true)) continue;
            $ki[] = $i;
        }
        sort($ki);
        $out = [];
        for ($i = 0; $i < $tw; $i++) if (in_array($i, $ki, true)) $out[] = $cl[$i]['raw'];
        $r2 = trim(preg_replace('/\s+/u', ' ', implode(' ', $out)));
        if (mb_strlen($r2) < 10) return $s;
        if (!preg_match('/[.؟!。！？]$/u', $r2)) $r2 .= '.';
        return $r2;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       BM25
       ═══════════════════════════════════════════════════════════════════════ */
    private function buildBM25(): void {
        $this->bm25 = []; $this->avgDocLen = 0.0; $this->idfCache = [];
        $n = count($this->st);
        if ($n === 0) return;
        $total = 0;
        foreach ($this->st as $i => $w) {
            $tf = array_count_values($w);
            $this->bm25[$i] = $tf;
            $total += count($w);
        }
        $this->avgDocLen = $total / max(1, $n);
        $this->BM25_K1 = SUMMARIZER_BM25_K1;
        $this->BM25_B  = SUMMARIZER_BM25_B;
    }

    private function bm25Score(int $doc, array $queryTerms): float {
        if (empty($this->bm25[$doc])) return 0.0;
        $k1 = $this->BM25_K1; $b = $this->BM25_B;
        $dl = count($this->st[$doc] ?? []);
        $N = count($this->st);
        $score = 0.0;
        foreach ($queryTerms as $term) {
            if (!isset($this->df[$term])) continue;
            $df = $this->df[$term];
            if ($df === 0) continue;
            if (!isset($this->idfCache[$term])) {
                $this->idfCache[$term] = log(1 + ($N - $df + 0.5) / ($df + 0.5));
                if (count($this->idfCache) > self::CACHE_BM25_IDF) {
                    $this->idfCache = array_slice($this->idfCache, -((int)(self::CACHE_BM25_IDF / 2)), null, true);
                }
            }
            $idf = $this->idfCache[$term];
            $tf = $this->bm25[$doc][$term] ?? 0;
            if ($tf === 0) continue;
            $num = $tf * ($k1 + 1);
            $den = $tf + $k1 * (1 - $b + $b * ($dl / max($this->avgDocLen, 1e-9)));
            $score += $idf * ($num / max($den, 1e-9));
        }
        return $score;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Embedding بهبودیافته
       ═══════════════════════════════════════════════════════════════════════ */
    private function buildEmbeddings(): void {
        $this->eb = [];
        $d = $this->ED;
        foreach ($this->st as $i => $w) {
            $vec = array_fill(0, $d, 0.0);
            foreach ($w as $x) {
                $h1 = crc32('w:' . $x);
                $h2 = crc32('b:' . mb_substr($x, 0, 2));
                $vec[$h1 % $d] += 1.0;
                $vec[$h2 % $d] += 0.5;
            }
            $cnt = count($w);
            for ($j = 0; $j < $cnt - 1; $j++) {
                $bg = $w[$j] . '_' . $w[$j + 1];
                $h3 = crc32('g:' . $bg);
                $vec[$h3 % $d] += 1.2;
            }
            $n = 0.0;
            foreach ($vec as $v) $n += $v * $v;
            $n = sqrt($n);
            if ($n > 0) foreach ($vec as $k => $v) $vec[$k] = $v / $n;
            $this->eb[$i] = $vec;
        }
    }

    private function buildVectors(): void {
        $this->df = [];
        $N = count($this->tk);
        foreach ($this->st as $w) foreach (array_unique($w) as $x) $this->df[$x] = ($this->df[$x] ?? 0) + 1;
        $this->v = [];
        foreach ($this->st as $i => $w) {
            $tf = array_count_values($w);
            $tt = array_sum($tf) ?: 1;
            $vec = [];
            foreach ($tf as $x => $c) {
                $tn = $c / $tt;
                $idf = log(($N + 1) / (($this->df[$x] ?? 0) + 1)) + 1;
                $vec[$x] = $tn * $idf;
            }
            $n = 0.0;
            foreach ($vec as $v) $n += $v * $v;
            $n = sqrt($n);
            if ($n > 0) foreach ($vec as $x => $v) $vec[$x] = $v / $n;
            $this->v[$i] = $vec;
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Redundancy removal با Union-Find
       ═══════════════════════════════════════════════════════════════════════ */
    private function remRedClusters(array $sel): array {
        if (count($sel) <= 1) return array_values(array_unique($sel));
        $ranked = array_values(array_unique($sel));
        usort($ranked, function ($a, $b) {
            $sa = $this->sc[$a] ?? 0.0;
            $sb = $this->sc[$b] ?? 0.0;
            if ($sa === $sb) return $a <=> $b;
            return $sb <=> $sa;
        });
        $kept = [];
        foreach ($ranked as $candidate) {
            $duplicate = false;
            foreach ($kept as $chosen) {
                if ($this->isRed((int)$candidate, (int)$chosen)) { $duplicate = true; break; }
            }
            if (!$duplicate) $kept[] = $candidate;
        }
        sort($kept);
        return $kept;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Coreference با پنجره پویا
       ═══════════════════════════════════════════════════════════════════════ */
    private function detCoref(): void {
        $this->hlp = []; $this->rb = []; $this->ct = [];
        $ps = [
            'fa'=>['این','آن','این‌ها','آن‌ها','همین','همان','چنین','چنان','این‌گونه','آن‌گونه','فرض‌کنید','فرض‌کن'],
            'en'=>['this','that','these','those','such','it','its','they','them','their','which','who','where','when'],
            'fr'=>['ce','cet','cette','ces','celui','celle','ceux','celles','il','elle','ils','elles'],
            'es'=>['este','esta','estos','estas','ese','esa','esos','esas','aquel','aquella','aquellos','aquellas']
        ];
        $pr = $ps[$this->lc] ?? $ps['en'];
        foreach ($this->ss as $i => $s) {
            $st = mb_strtolower(trim($s));
            $sp = false;
            foreach ($pr as $p) {
                $nd = mb_strtolower($p);
                if (mb_strpos($st, $nd) === 0) { $sp = true; break; }
            }
            $this->hlp[$i] = $sp;
            $this->rb[$i] = [];
            $this->ct[$i] = null;
        }
        $n = count($this->ss);
        $window = $n < 20 ? 5 : ($n < 60 ? 7 : 10);
        for ($i = 1; $i < $n; $i++) {
            if (!$this->hlp[$i]) continue;
            $br = null; $bs = 0.0;
            for ($ri = $i - 1; $ri >= max(0, $i - $window); $ri--) {
                $s = $this->comb($i, $ri);
                if ($s > $bs) { $bs = $s; $br = $ri; }
            }
            if ($br !== null && $bs > 0.10) {
                $this->ct[$i] = $br;
                $this->rb[$br][] = $i;
            }
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       scoreSS — ترکیب همه سیگنال‌ها با BM25
       ═══════════════════════════════════════════════════════════════════════ */
    private function scoreSS(): void {
        $n = count($this->ss);
        if ($n === 0) return;
        $tfs = [];
        foreach ($this->v as $i => $vec) { $s = 0.0; foreach ($vec as $v) $s += $v; $tfs[$i] = $s; }
        $cen = $this->compCen();
        $posS = [];
        for ($i = 0; $i < $n; $i++) {
            $p = $i / max($n - 1, 1);
            $g1 = exp(-pow($p - 0.0, 2) / (2 * 0.20 * 0.20));
            $g2 = exp(-pow($p - 1.0, 2) / (2 * 0.15 * 0.15));
            $g3 = exp(-pow($p - 0.5, 2) / (2 * 0.30 * 0.30)) * 0.4;
            $posS[$i] = max($g1, $g2) + $g3;
        }
        $this->posS = $posS;
        $lenS = [];
        for ($i = 0; $i < $n; $i++) {
            $wc = count($this->tk[$i]);
            $dv = abs($wc - $this->IW) / max($this->IW, 1);
            $sc = max(0.3, exp(-$dv * $dv * 1.2));
            if ($wc < $this->MSW) $sc *= 0.4;
            if ($wc > $this->MXSW) $sc *= 0.5;
            $lenS[$i] = $sc;
        }
        $this->lenS = $lenS;
        $ss = [];
        $bs = array_flip(array_map('mb_strtolower', $this->lang['boost']));
        $ns = array_flip(array_map('mb_strtolower', $this->lang['negative']));
        $ps = array_flip(array_map('mb_strtolower', $this->lang['positive']));
        foreach ($this->tk as $i => $w) {
            $bc = 0; $nc = 0; $pc = 0;
            foreach ($w as $x) { if (isset($bs[$x])) $bc++; if (isset($ns[$x])) $nc++; if (isset($ps[$x])) $pc++; }
            $ss[$i] = min(2.5, 1.0 + 0.15 * $bc + 0.05 * $pc + 0.08 * $nc);
        }
        $cs = [];
        $cu = $this->lang['cue_phrases'] ?? [];
        foreach ($this->ss as $i => $s) {
            $lo = mb_strtolower($s);
            $h = 0;
            foreach ($cu as $c) if (mb_strpos($lo, mb_strtolower($c)) !== false) $h++;
            $cs[$i] = 1.0 + 0.20 * min(3, $h);
        }
        $entitiesBySent = []; $entityDF = [];
        for ($i = 0; $i < $n; $i++) {
            $e = $this->extractEntities($this->ss[$i]);
            $entitiesBySent[$i] = $e;
            foreach ($e as $x) $entityDF[$x] = ($entityDF[$x] ?? 0) + 1;
        }
        $N = max(1, $n);
        $entityIdf = [];
        foreach ($entityDF as $k => $df) $entityIdf[$k] = log(($N + 1) / ($df + 0.5));
        $entScore = [];
        for ($i = 0; $i < $n; $i++) {
            $sc = 0.0;
            foreach ($entitiesBySent[$i] as $e) {
                $idf = $entityIdf[$e] ?? 0;
                $sc += $idf;
                if (strpos($e, 'PERS:') === 0 || strpos($e, 'ORG:') === 0 || strpos($e, 'YR:') === 0) $sc += 0.5;
            }
            $entScore[$i] = $sc;
        }
        $maxEnt = !empty($entScore) ? max($entScore) : 1.0;
        if ($maxEnt > 0) foreach ($entScore as $i => $v) $entScore[$i] = $v / $maxEnt;

        $qTerms = [];
        if (!empty($this->kp)) {
            $top = array_slice(array_keys($this->kp), 0, 12);
            foreach ($top as $phrase) foreach (explode(' ', $phrase) as $t) $qTerms[$t] = true;
        }
        $qArr = array_keys($qTerms);
        $bmScores = [];
        $maxBm = 1.0;
        if (!empty($qArr) && !empty($this->bm25)) {
            foreach ($this->st as $i => $_) $bmScores[$i] = $this->bm25Score($i, $qArr);
            $maxBm = !empty($bmScores) ? max($bmScores) : 1.0;
            if ($maxBm <= 0) $maxBm = 1.0;
        }

        $this->sc = [];
        for ($i = 0; $i < $n; $i++) {
            $sc = 0.0;
            $sc += 0.10 * $tfs[$i];
            $sc += 0.08 * $cen[$i];
            $sc += 0.04 * $posS[$i];
            $sc += 0.03 * $lenS[$i];
            $sc += 0.03 * ($ss[$i] - 1.0);
            $sc += 0.03 * ($cs[$i] - 1.0);
            $sc += 0.05 * $this->sks[$i];
            $sc += 0.08 * ($this->es[$i] - 1.0);
            $sc += 0.30 * $entScore[$i];
            if (!empty($bmScores) && isset($bmScores[$i]) && $maxBm > 0) {
                $sc += 0.10 * ($bmScores[$i] / $maxBm);
            }
            if ($i === 0) $sc += 0.50;
            if ($i === $n - 1 && $n > 3) $sc += 0.35;
            if ($this->head[$i]) $sc += 0.70;
            if ($this->isI[$i]) $sc += 0.45;
            if ($this->docType === 'academic') { if ($i < 3) $sc += 0.30; if ($i > $n - 4) $sc += 0.25; }
            elseif ($this->docType === 'philosophy') {
                $argumentText = mb_strtolower($this->ss[$i]);
                if (preg_match('/(مدعا|دعوی|تز|thesis|claim|argue|استدلال|argument|برهان|proof|مقدمه|premise|فرض|assumption|اصل|principle)/ui', $argumentText)) $sc += 1.05;
                if (preg_match('/(تعریف|مفهوم|ماهیت|چیستی|معنا|definition|concept|nature|meaning)/ui', $argumentText)) $sc += 0.85;
                if (preg_match('/(اعتراض|نقد|مخالفت|در مقابل|با این حال|اما|objection|criticism|counterargument|however|nevertheless)/ui', $argumentText)) $sc += 0.95;
                if (preg_match('/(پاسخ|ردّ|نتیجه|بنابراین|در نتیجه|reply|rebuttal|conclusion|therefore|thus)/ui', $argumentText)) $sc += 0.90;
            }
            elseif ($this->docType === 'news') { if ($i === 0) $sc += 0.40; if (preg_match('/[«"].{5,}[»"]/u', $this->ss[$i])) $sc += 0.25; }
            elseif ($this->docType === 'narrative') { if ($i === 0) $sc += 0.35; }
            $ti = $this->stp2[$i] ?? ['type'=>'normal','bonus'=>0.0];
            $sc += $ti['bonus'] * 0.6;
            if (in_array($i, $this->mki, true)) $sc += 1.8;
            $sc -= $this->ivs[$i] * 0.8;
            $this->sc[$i] = max(0.0, $sc);
        }

        $before = $this->sc;
        $this->sc = tri_boost_scores($this->ss, $this->sc, $this->semClusters);
        if ($this->sc !== $before) $this->weightBoosted = true;
        if ($this->semClusters !== null) $this->semanticApplied = true;
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Quality self-assessment
       ═══════════════════════════════════════════════════════════════════════ */
    private function validateAbstractive(string $candidate, array $sourceSentences): bool {
        $source = implode(' ', $sourceSentences);
        preg_match_all('/[0-9۰-۹٠-٩]+(?:[.,٫][0-9۰-۹٠-٩]+)?/u', $source, $sm);
        preg_match_all('/[0-9۰-۹٠-٩]+(?:[.,٫][0-9۰-۹٠-٩]+)?/u', $candidate, $cm);
        $sourceNumbers = array_unique($sm[0] ?? []);
        $candidateNumbers = array_unique($cm[0] ?? []);
        if ($sourceNumbers !== $candidateNumbers) return false;

        $sourceEntities = $this->extractEntities($source);
        $candidateEntities = $this->extractEntities($candidate);
        foreach ($sourceEntities as $entity) {
            if (strpos($entity, 'NUM:') === 0 || strpos($entity, 'YR:') === 0
                || strpos($entity, 'PERS:') === 0 || strpos($entity, 'ORG:') === 0) {
                if (!in_array($entity, $candidateEntities, true)) return false;
            }
        }
        $neg = '/(?:\bnot\b|\bno\b|\bnever\b|\bwithout\b|نیست|نبود|نمی\p{L}+|هرگز|بدون|عدم)/ui';
        if ((bool)preg_match($neg, $source) !== (bool)preg_match($neg, $candidate)) return false;
        return true;
    }

    private function assessQuality(array $sel, string $summary): array {
        $tokenize = function (string $text): array {
            preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $m);
            return array_values(array_filter($m[0] ?? [], function ($x) { return mb_strlen($x) >= 2; }));
        };

        $sourceDf = []; $sourceEntities = []; $sourceNumbers = []; $coreTerms = [];
        foreach ($this->ss as $i => $sentence) {
            $terms = array_unique($this->st[$i] ?? $tokenize($sentence));
            foreach ($terms as $term) $sourceDf[$term] = ($sourceDf[$term] ?? 0) + 1;
            foreach ($this->extractEntities($sentence) as $entity) $sourceEntities[$entity] = true;
            preg_match_all('/[0-9۰-۹٠-٩]+(?:[.,٫][0-9۰-۹٠-٩]+)?/u', $sentence, $nm);
            foreach ($nm[0] ?? [] as $num) $sourceNumbers[$num] = true;
            $type = $this->stp2[$i]['type'] ?? 'normal';
            if (in_array($type, ['statistical','definition','conclusion','classification'], true)
                || !empty($this->head[$i]) || in_array($i, $this->mki, true)) {
                foreach ($terms as $term) $coreTerms[$term] = true;
            }
        }

        $summaryTokens = $tokenize($summary);
        $summarySet = array_fill_keys(array_unique($summaryTokens), true);
        $totalWeight = 0.0; $coveredWeight = 0.0;
        foreach ($sourceDf as $term => $df) {
            $weight = 1.0 + log(1.0 + count($this->ss) / max(1, $df));
            $totalWeight += $weight;
            if (isset($summarySet[$term])) $coveredWeight += $weight;
        }
        $coverage = $totalWeight > 0 ? $coveredWeight / $totalWeight : 0.0;
        $coreHit = 0;
        foreach ($coreTerms as $term => $_) if (isset($summarySet[$term])) $coreHit++;
        $coreCoverage = count($coreTerms) ? $coreHit / count($coreTerms) : $coverage;

        $summaryEntities = [];
        foreach ($this->extractEntities($summary) as $entity) $summaryEntities[$entity] = true;
        preg_match_all('/[0-9۰-۹٠-٩]+(?:[.,٫][0-9۰-۹٠-٩]+)?/u', $summary, $sn);
        $summaryNumbers = array_fill_keys($sn[0] ?? [], true);
        $entityRecall = count($sourceEntities) ? count(array_intersect_key($sourceEntities, $summaryEntities)) / count($sourceEntities) : 1.0;
        $numberRecall = count($sourceNumbers) ? count(array_intersect_key($sourceNumbers, $summaryNumbers)) / count($sourceNumbers) : 1.0;

        $summarySentences = preg_split('/(?<=[.!?؟。！？…])\s+/u', trim($summary));
        $summarySentences = array_values(array_filter($summarySentences ?: [], function ($x) { return trim($x) !== ''; }));

        $pairRedundancy = [];
        for ($i = 0; $i < count($summarySentences); $i++) {
            $a = array_unique($tokenize($summarySentences[$i]));
            for ($j = $i + 1; $j < count($summarySentences); $j++) {
                $b = array_unique($tokenize($summarySentences[$j]));
                if (!$a || !$b) continue;
                $union = count(array_unique(array_merge($a, $b)));
                $jac = $union ? count(array_intersect($a, $b)) / $union : 0.0;
                $pairRedundancy[] = max(0.0, ($jac - 0.35) / 0.65);
            }
        }
        $redundancy = $pairRedundancy ? array_sum($pairRedundancy) / count($pairRedundancy) : 0.0;
        $nonRedundancy = 1.0 - min(1.0, $redundancy);

        $cohesion = 1.0;
        if (count($summarySentences) > 1) {
            $lexicalLinks = [];
            $sourcePositions = [];
            foreach ($summarySentences as $summarySentence) {
                $summaryTerms = array_unique($tokenize($summarySentence));
                $bestIndex = null; $bestMatch = 0.0;
                foreach ($this->ss as $sourceIndex => $sourceSentence) {
                    $sourceTerms = array_unique($tokenize($sourceSentence));
                    if (!$summaryTerms || !$sourceTerms) continue;
                    $union = count(array_unique(array_merge($summaryTerms, $sourceTerms)));
                    $match = $union ? count(array_intersect($summaryTerms, $sourceTerms)) / $union : 0.0;
                    if ($match > $bestMatch) { $bestMatch = $match; $bestIndex = $sourceIndex; }
                }
                if ($bestIndex !== null) $sourcePositions[] = $bestIndex;
            }
            for ($i = 1; $i < count($summarySentences); $i++) {
                $a = array_unique($tokenize($summarySentences[$i-1]));
                $b = array_unique($tokenize($summarySentences[$i]));
                $u = count(array_unique(array_merge($a, $b)));
                $lexicalLinks[] = $u ? count(array_intersect($a, $b)) / $u : 0.0;
            }
            $lexical = $lexicalLinks ? array_sum($lexicalLinks) / count($lexicalLinks) : 0.0;
            $continuityLinks = [];
            for ($i = 1; $i < count($sourcePositions); $i++) {
                $gap = $sourcePositions[$i] - $sourcePositions[$i-1];
                if ($gap <= 0) $continuityLinks[] = 0.0;
                else $continuityLinks[] = exp(-0.25 * max(0, $gap - 1));
            }
            $continuity = $continuityLinks ? array_sum($continuityLinks) / count($continuityLinks) : 0.0;
            $cohesion = 0.25 * $lexical + 0.75 * $continuity;
        }

        $refPattern = '/^(این‌ها|این|آن‌ها|آن|همین|همان|چنین|it|they|these|those|this|that|he|she)\b/ui';
        $refs = 0; $resolved = 0; $seenTerms = [];
        foreach ($summarySentences as $sentence) {
            $trimmed = ltrim($sentence);
            if (preg_match($refPattern, $trimmed)) {
                $refs++;
                if (!empty($seenTerms)) $resolved++;
            }
            foreach ($tokenize($sentence) as $term) $seenTerms[$term] = true;
        }
        $selfContainment = $refs ? $resolved / $refs : 1.0;

        $ordered = array_values(array_unique($sel));
        sort($ordered);
        $orderScore = 1.0;
        $selectedTypes = [];
        foreach ($ordered as $idx) $selectedTypes[] = $this->stp2[$idx]['type'] ?? 'normal';
        $sourceHasConclusion = in_array('conclusion', array_column($this->stp2, 'type'), true);
        $summaryHasConclusion = in_array('conclusion', $selectedTypes, true);
        $structureScore = $sourceHasConclusion ? ($summaryHasConclusion ? 1.0 : 0.65) : 0.90;
        if (count($ordered) > 1) {
            $ascending = 0;
            for ($i = 1; $i < count($ordered); $i++) if ($ordered[$i] > $ordered[$i-1]) $ascending++;
            $orderScore = $ascending / max(1, count($ordered)-1);
        }

        $sourceRoles = [];
        $summaryRoles = [];
        foreach ($this->ss as $sentence) {
            $role = $this->argumentRole($sentence);
            if ($role !== 'normal') $sourceRoles[$role] = true;
        }
        foreach ($summarySentences as $sentence) {
            $role = $this->argumentRole($sentence);
            if ($role !== 'normal') $summaryRoles[$role] = true;
        }
        $roleCoverage = count($sourceRoles)
            ? count(array_intersect_key($sourceRoles, $summaryRoles)) / count($sourceRoles)
            : 1.0;

        $values = [
            'coherence' => $cohesion,
            'coverage' => $coverage,
            'accuracy_proxy' => 0.65 * $entityRecall + 0.35 * $numberRecall,
            'non_redundancy' => $nonRedundancy,
            'referential_self_containment' => $selfContainment,
            'argument_structure' => 0.45 * $structureScore + 0.30 * $orderScore + 0.25 * $roleCoverage,
            'core_concept_coverage' => $coreCoverage
        ];
        $scores = [];
        foreach ($values as $key => $value) $scores[$key] = round(max(0.0, min(1.0, $value)) * 100, 1);

        return [
            'scores' => $scores,
            'coherence' => $scores['coherence'],
            'coverage' => $scores['coverage'],
            'accuracy_proxy' => $scores['accuracy_proxy'],
            'non_redundancy' => $scores['non_redundancy'],
            'referential_self_containment' => $scores['referential_self_containment'],
            'argument_structure' => $scores['argument_structure'],
            'source_sentences' => count($this->ss),
            'summary_sentences' => count($summarySentences),
            'unique_source_concepts' => count($sourceDf),
            'core_concepts' => count($coreTerms),
            'source_structural_roles' => count($sourceRoles),
            'preserved_structural_roles' => count(array_intersect_key($sourceRoles, $summaryRoles)),
            'structural_role_coverage' => round($roleCoverage * 100, 1),
            'document_type' => $this->docType,
            'entities_preserved' => round($entityRecall * 100, 1),
            'numbers_preserved' => round($numberRecall * 100, 1),
            'measurement_note' => 'Heuristic diagnostics only; factual correctness and human-rated coherence require an external gold-standard evaluation set.'
        ];
    }

    /* ═══════════════════════════════════════════════════════════════════════
       Helpers مشترک
       ═══════════════════════════════════════════════════════════════════════ */
    private function protectMarkup(string $t): string {
        $this->mk = [];
        $t = preg_replace_callback('/(\*\*[^\*\n]{1,200}\*\*)/u', function ($m) {
            $k = 'MKPH' . count($this->mk) . 'X';
            $this->mk[$k] = $m[1];
            return $k;
        }, $t);
        $t = preg_replace_callback('/(\*[^\*\n]{1,120}\*)/u', function ($m) {
            $k = 'MKPH' . count($this->mk) . 'X';
            $this->mk[$k] = $m[1];
            return $k;
        }, $t);
        return $t;
    }

    private function restoreMarkup(string $t): string {
        if (empty($this->mk)) return $t;
        return str_replace(array_keys($this->mk), array_values($this->mk), $t);
    }

    private function notSumResult(string $o, int $l): array {
        return ['success'=>false,'error'=>'not_summarizable','message'=>'محتوای قابل خلاصه‌سازی ارائه نشده است. لطفاً متن بلندتری (حداقل ۷۵ کاراکتر) وارد کنید.','summary'=>'','stats'=>['original_length'=>$l,'summary_length'=>0,'compression_ratio'=>0,'target_compression'=>0,'total_sentences'=>0,'sentences_extracted'=>0,'processing_time'=>round(microtime(true)-$this->t0,3),'language'=>$this->lc,'language_name'=>$this->lang['name']??$this->lc,'paragraphs'=>0,'topics'=>0,'mode'=>'rejected','restricted_ratio'=>false,'rejected'=>true,'min_required'=>$this->MINL,'version'=>SUMMARIZER_VERSION]];
    }

    private function pubStats(array $s): array {
        return ['length'=>$s['length'],'sentences'=>$s['sentence_count'],'paragraphs'=>$s['paragraph_count'],'words'=>$s['word_count'],'avg_words_per_sentence'=>$s['avg_words_per_sentence'],'repeat_ratio'=>round($s['repeat_ratio'],3),'noise_ratio'=>round($s['noise_ratio'],3),'intro_ratio'=>round($s['intro_ratio'],3),'list_like'=>$s['list_like'],'informal_tone'=>$s['informal_tone']];
    }

    private function protUrls(string $t): string {
        $this->up = [];
        $t = preg_replace_callback('#https?://[^\s<>"\']+#u', function ($m) {
            $k = 'URLPH' . count($this->up) . 'X';
            $this->up[$k] = $m[0];
            return $k;
        }, $t);
        return $t;
    }

    private function resUrls(string $t): string {
        if (empty($this->up)) return $t;
        return str_replace(array_keys($this->up), array_values($this->up), $t);
    }

    private function normEsc(string $t): string {
        return str_replace(['\\r\\n','\\n\\r','\\n','\\r','\\t'], ["\n","\n","\n","\n","\t"], $t);
    }

    private function aggClean(string $t): string {
        $t = preg_replace('/\(\s*شکل\s*[\d۰-۹]+\s*\)/u', ' ', $t);
        $t = preg_replace('/\(\s*جدول\s*[\d۰-۹]+\s*\)/u', ' ', $t);
        $t = preg_replace('/\(\s*نمودار\s*[\d۰-۹]+\s*\)/u', ' ', $t);
        $t = preg_replace('/\(\s*تصویر\s*[\d۰-۹]+\s*\)/u', ' ', $t);
        $t = preg_replace('/\(\s*[الفبجدهوزحطیکلمنصعضسشصضطظعغفق]\s*\)/u', ' ', $t);
        $t = preg_replace('/\(\s*(figure|table|chart|image|fig\.?)\s*[\d۰-۹]+\s*\)/ui', ' ', $t);
        $t = preg_replace('/(\p{L})\.(\p{L})/u', '$1' . "\xE2\x80\x8C" . '$2', $t);
        $t = preg_replace('/\)\s+([\p{L}]{1,2})\b/u', ')$1', $t);
        $t = preg_replace('/([^\s])\1{3,}/u', '$1$1$1', $t);
        $t = preg_replace('/؛\s*؛+/u', '؛', $t);
        $t = preg_replace('/\.\s*\.+/u', '.', $t);
        $t = preg_replace('/\s+([،؛:!؟\.\)\]\}»])/u', '$1', $t);
        $t = preg_replace('/\n{3,}/u', "\n\n", $t);
        return trim($t);
    }

    private function detMode(int $l): void {
        $this->rr2 = ($l < 100);
        if ($l < 15) { $this->mode='minimal'; $this->MSW=1; $this->MXSW=200; $this->IW=8; $this->ML=0.85; $this->TST=0.03; }
        elseif ($l < 100) { $this->mode='micro'; $this->MSW=2; $this->MXSW=150; $this->IW=10; $this->ML=0.80; $this->TST=0.04; }
        elseif ($l < 300) { $this->mode='tiny'; $this->MSW=3; $this->MXSW=120; $this->IW=13; $this->ML=0.75; $this->TST=0.06; }
        elseif ($l < 1000) { $this->mode='short'; $this->MSW=4; $this->MXSW=90; $this->IW=16; $this->ML=0.72; $this->TST=0.08; }
        elseif ($l < 4000) $this->mode='mid';
        elseif ($l < 15000) { $this->mode='long'; $this->TI=40; $this->ML=0.60; $this->TST=0.12; }
        else { $this->mode='huge'; $this->TI=30; $this->ML=0.55; $this->TST=0.15; $this->MXS=10000; }
    }

    private function compK(int $n, float $r): int {
        $t = (int)round($n * $r);
        switch ($this->mode) {
            case 'minimal': $t = max(1, min(2, $t)); break;
            case 'micro': $t = max(1, min(3, $t)); break;
            case 'tiny': $t = max(1, min(5, $t)); break;
            case 'short': $t = max(2, min(12, $t)); break;
            case 'mid': $t = max(3, min(25, $t)); break;
            case 'long': $t = max(5, min(60, $t)); break;
            case 'huge': $t = max(8, min(120, $t)); break;
        }
        $t = min($t, (int)ceil($n * 0.85));
        return max(1, $t);
    }

    private function scoreBasic(): void {
        $n = count($this->ss);
        $this->sc = [];
        for ($i = 0; $i < $n; $i++) {
            $s = $this->ss[$i];
            $sc = 1.0;
            $l = mb_strlen($s);
            if ($l > 40) $sc += 0.3;
            if ($l > 80) $sc += 0.3;
            if (preg_match('/\d/u', $s)) $sc += 0.4;
            if (preg_match('/[A-Z][a-z]{2,}/', $s)) $sc += 0.3;
            if ($i === 0) $sc += 0.25;
            if ($i === $n - 1) $sc += 0.15;
            $this->sc[$i] = $sc;
        }
        $this->sp = array_fill(0, $n, 0);
        $this->ps = [0 => range(0, $n - 1)];
        $this->isI = array_fill(0, $n, false);
        $this->isB = array_fill(0, $n, false);
        $this->lm = false;
        $this->tp = [0 => range(0, $n - 1)];
        $this->stp = array_fill(0, $n, 0);
        $this->hlp = array_fill(0, $n, false);
        $this->ct = array_fill(0, $n, null);
        $this->es = array_fill(0, $n, 1.0);
        $this->sks = array_fill(0, $n, 0.0);
        $this->stp2 = array_fill(0, $n, ['type'=>'normal','bonus'=>0.0]);
        $this->mki = [];
        $this->ivs = array_fill(0, $n, 0.0);
        $this->head = array_fill(0, $n, false);
        $this->bnd = array_fill(0, $n, false);
    }

    private function stripMeta(string $t): string {
        $p = ['/\(\s*(توضیح|اخبار|هشدار|نکته)\s*[:؛][^)]*\)/us','/\(\s*(توضیح|اخبار|هشدار)\s*[:؛][^\n)]*$/um','/\b(rated|rating|feedback|امتیاز|بازخورد)\b[^\n]*/um','/\(\s*(note|warning|caution|update|source)\s*[:;][^)]*\)/ius','/\(\s*\)/u','/\[\s*\]/u'];
        foreach ($p as $x) $t = preg_replace($x, '', $t);
        $t = preg_replace('/[ \t]+/u', ' ', $t);
        $t = preg_replace('/\n{3,}/u', "\n\n", $t);
        return trim($t);
    }

    private function stripNoise(string $t): string {
        $t = preg_replace('/^[\s\-_=~*#│┃═]+$/mu', '', $t);
        $t = preg_replace('/^\s*[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}]+\s*$/mu', '', $t);
        $t = preg_replace('/(?<![\/\w])@\w{2,}(?![\w\/])/u', '', $t);
        $t = preg_replace_callback('/https?:\/\/\S{200,}/u', function($m) {
            if (preg_match('/^URLPH\d+X$/', $m[0])) return $m[0];
            return '[لینک]';
        }, $t);
        $t = preg_replace('/\n{3,}/u', "\n\n", $t);
        return trim($t);
    }

    private function splitS(string $t): void {
        $this->ss = []; $this->ps = []; $this->sp = [];
        $rp = preg_split('/\n\s*\n/u', $t);
        if (!is_array($rp)) $rp = [$t];
        if (count($rp) <= 2 && mb_strlen($t) > 1500) {
            $t = preg_replace('/(\n|^)\s*(فصل در یونان|سوفیست‌ها|هراکلیتوس|پارمنیدس|فیثاغورس|تالس|تعریف|نتیجه‌گیری|خلاصه|مقدمه|روش‌ها|نتایج|بحث|منابع|مراجع|تفسیر|تأمل|introduction|methods|results|discussion|conclusion|references|abstract|summary)\s*[:؛]?\s*/ui', "\n\n$2: ", $t);
            $rp = preg_split('/\n\s*\n/u', $t);
            if (!is_array($rp)) $rp = [$t];
        }
        $seen = []; $up = [];
        foreach ($rp as $p) {
            $n = preg_replace('/\s+/u', ' ', trim($p));
            if ($n === '') continue;
            $h = md5(mb_substr($n, 0, 200));
            if (isset($seen[$h])) continue;
            $seen[$h] = true;
            $up[] = $p;
        }
        $rp = $up;
        $ml = (int)($this->lang['min_sentence_length'] ?? 5);
        $xl = (int)($this->lang['max_sentence_length'] ?? 500);
        if ($this->mode === 'minimal' || $this->mode === 'micro' || $this->mode === 'tiny') $ml = 2;
        if ($this->lc === 'fa') $ml = max($ml, 12);
        $pat = '/(?<=[.!?؟。！？…؛])\s+(?=[\p{Lu}\p{Lt}\x{0600}-\x{06FF}0-9«"\x{1F300}-\x{1FAFF}])/u';
        $pi = 0; $tc = 0;
        foreach ($rp as $para) {
            if ($tc >= $this->MXS) break;
            $para = trim($para);
            if ($para === '') continue;
            $ps = [];
            $parts = preg_split($pat, $para);
            if (!is_array($parts)) $parts = [$para];
            foreach ($parts as $p) {
                if ($tc >= $this->MXS) break;
                if (!is_string($p)) continue;
                $p = trim($p);
                if (preg_match('/^(URLPH|MKPH)\d+X$/u', $p)) {
                    $i = count($this->ss);
                    $this->ss[] = $p;
                    $ps[] = $i;
                    $this->sp[$i] = $pi;
                    $tc++;
                    continue;
                }
                if (mb_strlen($p) < $ml) continue;
                if (!$this->isValidS($p)) continue;
                if (mb_strlen($p) > $xl) {
                    $sub = preg_split('/(?<=[،,;؛])\s+/u', $p);
                    if (is_array($sub) && count($sub) > 1) {
                        foreach ($sub as $sp) {
                            if ($tc >= $this->MXS) break;
                            $sp = trim($sp);
                            if (mb_strlen($sp) >= $ml && $this->isValidS($sp)) {
                                $i = count($this->ss);
                                $this->ss[] = $sp;
                                $ps[] = $i;
                                $this->sp[$i] = $pi;
                                $tc++;
                            }
                        }
                        continue;
                    }
                }
                $i = count($this->ss);
                $this->ss[] = $p;
                $ps[] = $i;
                $this->sp[$i] = $pi;
                $tc++;
            }
            if (!empty($ps)) {
                $this->ps[$pi] = $ps;
                $pi++;
                if ($pi >= $this->MXP) break;
            }
        }
        if (empty($this->ss)) {
            $this->ss = [mb_substr($t, 0, 4000)];
            $this->ps = [0 => [0]];
            $this->sp[0] = 0;
        }
    }

    private function isValidS(string $s): bool {
        $s = trim($s);
        if (preg_match('/^(URLPH|MKPH)\d+X$/u', $s)) return true;
        $l = mb_strlen($s);
        if ($l < 15) return false;
        preg_match_all('/[\p{L}\p{N}]{2,}/u', $s, $m);
        $wc = count($m[0]);
        if ($wc < 4) return false;
        $lc = preg_match_all('/\p{L}/u', $s);
        // Ignore spaces and punctuation in the character-density denominator.
        $visible = preg_replace('/[\s\p{P}\p{S}]+/u', '', $s);
        $visibleLength = mb_strlen((string)$visible);
        if ($lc / max($visibleLength, 1) < 0.55) return false;
        if (!preg_match('/\p{L}{3,}/u', $s)) return false;
        if (preg_match('/(.)\1{4,}/u', $s)) return false;
        return true;
    }

    private function classAll(): void {
        $this->stp2 = [];
        foreach ($this->ss as $i => $s) $this->stp2[$i] = $this->classS($s);
    }

    private function classS(string $s): array {
        $s = trim($s); $t = 'normal'; $b = 0.0;
        if (preg_match('/[\d۰-۹]+[\.,]?[\d۰-۹]*\s*(%|٪|درصد|percent)/ui', $s)
            || preg_match('/\b(13|14|19|20)\d{2}\b/u', $s)
            || preg_match('/(فقط|ارقام|آمار|میلیون|میلیارد|هزار|هکتار|حدود|million|billion|thousand|approximately|about|around)/ui', $s)) {
            $t = 'statistical'; $b = 2.5;
        }
        elseif (preg_match('/(عبارت است از|را .{1,30} می‌نامند|را .{1,30} گویند|تعریف|گفته می‌شود|محلول می‌نامند|is defined as|refers to|is called|is known as|means that|is described as|can be defined)/ui', $s)) {
            $t = 'definition'; $b = 2.0;
        }
        elseif (preg_match('/^(پس|در نتیجه|از این رو|به این ترتیب|به این دلیل|در پی این|نتیجه این که|پس می‌توان|therefore|thus|hence|consequently|as a result|in conclusion|to conclude)/ui', $s)) {
            $t = 'conclusion'; $b = 1.5;
        }
        elseif (preg_match('/(تقسیم می‌شوند|عبارتند از|انواع .{1,20} (عبارت|شامل)|دسته‌بندی می‌شوند|به دو دسته|به سه دسته|بر دو نوع|بر سه نوع|دو نوع|سه نوع|چهار نوع|are divided|are classified|consist of|types of|kinds of|categories of|two types|three types|four types)/ui', $s)) {
            $t = 'classification'; $b = 1.8;
        }
        elseif (preg_match('/(ممکن است|می‌توانند|may be|can be|might be)/ui', $s) && mb_strlen($s) > 30) {
            $t = 'explanatory'; $b = 1.0;
        }
        elseif (preg_match('/(همواره یکی از اولین‌ها|ما از تغییر|ما به‌عنوان|ما اینجا|شعار|کنار هم|ما می|ما با|we are|our mission|join us|sign up|subscribe|click here|buy now)/ui', $s)) {
            $t = 'slogan'; $b = -1.8;
        }
        elseif (preg_match('/^(چرا|کدام|چگونه|چه)\s.{5,}/u', $s) && preg_match('/\?|؟/u', $s)) {
            $t = 'conceptual_question'; $b = 0.3;
        }
        elseif (preg_match('/(می‌خواهید خود را|نیاز به یاد|را آغاز کنید|اجرا کنید|بررسی کنید|شبیه‌سازی|بشنوید|ذخیره کنید|روش آزمایش|باید انجام دهید|try it|run the|execute|simulate|save it|check it|do it|you should)/ui', $s)) {
            $t = 'exercise'; $b = -2.5;
        }
        elseif (preg_match('/^(چرا|چگونه)\s/u', $s) && preg_match('/\?|؟/u', $s)) {
            $t = 'question'; $b = -1.5;
        }
        elseif (!preg_match('/(است|هست|شود|شد|کرد|کند|دارد|دارند|باشد|بود|آمد|رفت|گفت|داد|دید|،|is|are|was|were|be|has|have|had|do|does|did|will|would|can|could|should|may|might|must|shall)/ui', $s) && mb_strlen($s) < 80) {
            $t = 'fragment'; $b = -3.0;
        }
        return ['type'=>$t, 'bonus'=>$b];
    }

    private function detMKS(): void {
        $this->mki = [];
        $ke = ['سقراط','افلاطون','ارسطو','هراکلیتوس','پارمنیدس','فیثاغورس','تالس','سوفیست','گورگیاس','پروتاگوراس','آناکسیماندر','آناکسیمنس','امپدوکل','آناکساگوراس','دموکریت','لئوکیپوس','Socrates','Plato','Aristotle','Heraclitus','Parmenides','Pythagoras','Thales','Sophist','Gorgias','Protagoras','Anaximander','Anaximenes','Empedocles','Anaxagoras','Democritus','Leucippus'];
        foreach ($this->ss as $i => $s) {
            foreach ($ke as $e) {
                if (mb_stripos($s, $e) !== false) {
                    if (preg_match('/(بود|است|می‌زیست|می‌گفت|می‌دانست|می‌پنداشت|بیان کرد|اظهار کرد|گفت|نوشت|تعریف کرد|اظهار داشت|بیان داشت|معتقد بود|مطرح کرد|آغاز کرد|was|were|said|wrote|believed|thought|taught|argued|claimed|stated|defined|introduced)/ui', $s)) $this->mki[] = $i;
                    break;
                }
            }
        }
        $this->mki = array_values(array_unique($this->mki));
    }

    private function detHeadings(): void {
        $this->head = []; $this->bnd = [];
        $n = count($this->ss);
        for ($i = 0; $i < $n; $i++) {
            $s = $this->ss[$i];
            $isHeading = (bool)preg_match('/^MKPH\d+X$/u', trim($s));
            if (!$isHeading) $isHeading = (bool)preg_match('/^(\*\*[^\*\n]{3,120}\*\*)\s*$/u', trim($s));
            if (!$isHeading) {
                $wc = count(preg_split('/\s+/u', trim($s)));
                if ($wc <= 8 && !preg_match('/[.؟!]/u', $s) && preg_match('/^[\p{L}\s\-\:]+$/u', $s)) $isHeading = true;
            }
            $this->head[$i] = $isHeading;
            if ($i === 0) $this->bnd[$i] = true;
            elseif ($isHeading) $this->bnd[$i] = true;
            else $this->bnd[$i] = false;
        }
    }

    private function detNonSumm(): void {
        $this->ivs = array_fill(0, count($this->ss), 0.0);
        foreach ($this->ss as $i => $s) {
            $sc = 0.0;
            $probe = mb_strlen($s) > 5000 ? mb_substr($s, 0, 5000) : $s;
            if (preg_match('/(\b\p{L}{3,}\b)(\s+\1){4,}/u', $probe)) $sc += 2.0;
            if (preg_last_error() !== PREG_NO_ERROR) $sc = min($sc, 1.0);
            $wc = count(preg_split('/\s+/u', trim($s)));
            if ($wc > 70 && substr_count($s, '،') > 10) $sc += 0.8;
            $this->ivs[$i] = min(3.0, $sc);
        }
        $n = count($this->ss);
        for ($i = 0; $i < $n - 2; $i++) {
            if ($this->ivs[$i] > 0) continue;
            $sa = md5(mb_substr(preg_replace('/\s+/u', ' ', $this->ss[$i]), 0, 60));
            $sb = md5(mb_substr(preg_replace('/\s+/u', ' ', $this->ss[$i + 1]), 0, 60));
            $scc = md5(mb_substr(preg_replace('/\s+/u', ' ', $this->ss[$i + 2]), 0, 60));
            if ($sa === $sb || $sb === $scc || $sa === $scc) {
                $this->ivs[$i] = max($this->ivs[$i], 1.5);
                $this->ivs[$i + 1] = max($this->ivs[$i + 1], 1.5);
                $this->ivs[$i + 2] = max($this->ivs[$i + 2], 1.5);
            }
        }
    }

    private function remNonSumm(array $sel): array {
        $out = [];
        foreach ($sel as $s) { if (($this->ivs[$s] ?? 0) >= 1.2) continue; $out[] = $s; }
        if (empty($out) && !empty($sel)) $out = [$sel[0]];
        return $out;
    }

    private function tok(): void {
        $this->tk = []; $this->st = [];
        $sw = LangData::mergedStopwords();
        $isFa = ($this->lc === 'fa');
        foreach ($this->ss as $i => $s) {
            if (preg_match('/^(URLPH|MKPH)\d+X$/u', trim($s))) { $this->tk[$i] = []; $this->st[$i] = []; continue; }
            $lo = mb_strtolower($s);
            preg_match_all('/[\p{L}\p{N}]+/u', $lo, $m);
            $w = $m[0] ?? [];
            $cl = [];
            foreach ($w as $x) {
                if (mb_strlen($x) < 2) continue;
                if (isset($sw[$x])) continue;
                if (preg_match('/^\d+$/u', $x)) continue;
                $cl[] = $x;
            }
            if ($isFa) {
                $aw = preg_split('/[\s\x{200C}]+/u', $lo);
                if (is_array($aw)) {
                    foreach ($aw as $x) {
                        $x = preg_replace('/[^\p{L}\p{N}]/u', '', $x);
                        if (mb_strlen($x) >= 2 && !isset($sw[$x]) && !preg_match('/^\d+$/u', $x)) $cl[] = $x;
                    }
                }
            }
            $cl = array_values(array_unique($cl));
            $this->tk[$i] = $cl;
            $ss = [];
            foreach ($cl as $x) $ss[] = $this->simpleStem($x);
            $this->st[$i] = $ss;
        }
    }

    private function simpleStem(string $w): string {
        $l = mb_strlen($w);
        if ($l < 4) return $w;
        if ($this->lc === 'fa') $sf = ['های','ها','تر','تری','ترین','اش','شان','مان','تان'];
        elseif ($this->lc === 'en') $sf = ['ation','ition','ness','ment','tion','sion','able','ible','ing','ies','ied','est','ful','less','ous','ive','ed','ly','es','er','s'];
        elseif ($this->lc === 'fr') $sf = ['ements','ement','ation','ition','euses','euse','elles','elle','eux','ment','tion','sion','ées','ée','es','s'];
        elseif ($this->lc === 'es') $sf = ['mientos','miento','ciones','ción','idades','idad','mente','iendo','ando','ador','ido','ada','ado','es','os','as','s'];
        else return $w;
        foreach ($sf as $x) {
            $sl = $this->lc === 'en' ? strlen($x) : mb_strlen($x);
            if ($l > $sl + 2) {
                $sub = $this->lc === 'en' ? substr($w, -$sl) : mb_substr($w, -$sl);
                if ($sub === $x) return $this->lc === 'en' ? substr($w, 0, $l - $sl) : mb_substr($w, 0, $l - $sl);
            }
        }
        return $w;
    }

    private function extKP(): void {
        $this->kp = []; $this->sks = [];
        $sw = LangData::mergedStopwords();
        $wf = []; $wd = []; $cd = [];
        foreach ($this->ss as $i => $s) {
            $lo = mb_strtolower($s);
            $tb = preg_split('/([\s\p{P}]+)/u', $lo, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            if (!is_array($tb)) $tb = [];
            $ph = []; $phl = [];
            foreach ($tb as $x) {
                $c = preg_replace('/[^\p{L}\p{N}]/u', '', $x);
                if ($c === '') { if (count($ph) > 0) { $phl[] = $ph; $ph = []; } continue; }
                if (isset($sw[$c]) || mb_strlen($c) < 2 || preg_match('/^\d+$/u', $c)) { if (count($ph) > 0) { $phl[] = $ph; $ph = []; } continue; }
                $ph[] = $this->simpleStem($c);
                if (count($ph) >= $this->RMP) { $phl[] = $ph; $ph = []; }
            }
            if (count($ph) > 0) $phl[] = $ph;
            foreach ($phl as $p) {
                $c = count($p);
                foreach ($p as $x) { $wf[$x] = ($wf[$x] ?? 0) + 1; $wd[$x] = ($wd[$x] ?? 0) + $c; }
            }
            $cd[$i] = $phl;
        }
        $ws = [];
        foreach ($wf as $x => $f) { $dg = $wd[$x] ?? 1; $ws[$x] = $dg / max($f, 1); }
        foreach ($cd as $i => $ps) foreach ($ps as $p) {
            $pt = implode(' ', $p);
            $psc = 0.0;
            foreach ($p as $x) $psc += $ws[$x] ?? 0;
            $psc *= (1 + 0.1 * (count($p) - 1));
            if (!isset($this->kp[$pt])) $this->kp[$pt] = 0.0;
            $this->kp[$pt] = max($this->kp[$pt], $psc);
        }
        arsort($this->kp);
        $this->kp = array_slice($this->kp, 0, 80, true);
        $mx = !empty($this->kp) ? max($this->kp) : 1.0;
        foreach ($cd as $i => $ps) {
            $sc = 0.0;
            foreach ($ps as $p) {
                $pt = implode(' ', $p);
                if (isset($this->kp[$pt])) $sc += $this->kp[$pt];
            }
            $this->sks[$i] = $mx > 0 ? $sc / $mx : 0.0;
        }
        for ($i = 0; $i < count($this->ss); $i++) if (!isset($this->sks[$i])) $this->sks[$i] = 0.0;
    }

    private function segTop(): void {
        $this->tp = []; $this->stp = [];
        $n = count($this->ss);
        if ($n === 0) return;
        if (count($this->ps) >= 2 && $this->mode !== 'huge') {
            $ti = 0;
            foreach ($this->ps as $pi => $ss) { $this->tp[$ti] = $ss; foreach ($ss as $s) $this->stp[$s] = $ti; $ti++; }
            return;
        }
        $ct = 0; $this->tp[0] = [0]; $this->stp[0] = 0;
        for ($i = 1; $i < $n; $i++) {
            if (!empty($this->bnd[$i])) { $ct++; $this->tp[$ct] = [$i]; $this->stp[$i] = $ct; continue; }
            $sp = $this->comb($i, $i - 1);
            $tc = $this->topC($ct);
            $sc = $tc !== null ? $this->comb($i, $tc) : 0.0;
            if ($sp < $this->TST && $sc < $this->TST) { $ct++; $this->tp[$ct] = [$i]; }
            else $this->tp[$ct][] = $i;
            $this->stp[$i] = $ct;
        }
        $mg = []; $ft = 0;
        foreach ($this->tp as $top) {
            if (count($top) < $this->MTS && !empty($mg)) {
                foreach ($top as $s) { $mg[count($mg) - 1][] = $s; $this->stp[$s] = $ft - 1; }
            } else {
                $mg[] = $top;
                foreach ($top as $s) $this->stp[$s] = $ft;
                $ft++;
            }
        }
        $this->tp = $mg;
    }

    private function topC(int $ti): ?int {
        if (!isset($this->tp[$ti]) || empty($this->tp[$ti])) return null;
        $b = null; $bs = -INF;
        foreach ($this->tp[$ti] as $i) {
            $s = 0.0;
            foreach ($this->tp[$ti] as $j) { if ($i === $j) continue; $s += $this->comb($i, $j); }
            if ($s > $bs) { $bs = $s; $b = $i; }
        }
        return $b;
    }

    private function comb(int $i, int $j): float {
        if (!isset($this->v[$i]) || !isset($this->v[$j])) return 0.0;
        $tf = $this->cos($this->v[$i], $this->v[$j]);
        $e = $this->emb($this->eb[$i], $this->eb[$j]);
        return 0.5 * $tf + 0.5 * $e;
    }

    private function scoreEnt(): void {
        $this->es = [];
        foreach ($this->ss as $i => $s) {
            $sc = 1.0;
            $rw = max(1, preg_match_all('/\S+/u', $s));
            $dm = preg_match_all('/\d+/u', $s);
            $dd = $dm / $rw;
            if ($dd > 0.30) $sc += 0.2; elseif ($dd > 0.15) $sc += 0.6; elseif ($dm >= 2) $sc += 0.9;
            if (preg_match('/\d+[\.,]?\d*\s*(%|٪|percent)/ui', $s)) $sc += 0.8;
            if (preg_match('/\b(13|14|19|20)\d{2}\b/u', $s)) $sc += 0.7;
            if (preg_match('/(میلیون|میلیارد|هزار|million|billion|thousand)/ui', $s)) $sc += 0.8;
            if (preg_match_all('/\b[A-Z][a-z]{2,}\b/', $s, $m)) $sc += min(0.8, 0.4 * count($m[0]));
            if ($rw < 6) $sc *= 0.85;
            $this->es[$i] = min(3.5, $sc);
        }
    }

    private function detIB(): void {
        $this->isI = []; $this->isB = [];
        $bc = 0;
        $n = count($this->ss);
        for ($i = 0; $i < $n; $i++) {
            $s = $this->ss[$i];
            $this->isI[$i] = $this->matchI($s);
            $this->isB[$i] = (bool)preg_match('/^\s*[•\-*▪◦‣⁃]\s+/u', $s);
            if ($this->isB[$i]) $bc++;
        }
        $this->lm = ($bc >= 3);
    }

    private function matchI(string $s): bool {
        $p = ['/^(نکات|موارد|مأخذ|عناوین|اصول|مراحل|دلایل|علل|انواع|اقسام|مثال‌ها|همچنین|نمونه‌ها)\s+(گفتنی|اساسی|مهم|زیر|قابل|زیرین)/u','/^(از این اثر عبارتند|از این اثر شامل|در این اثر شامل|عنوان از|شرح از|کتاب از|از این قرار|به این ترتیب|در این بخش)/u','/^(نکته|نکات)\s+(مهم|اساسی|زیر|قابل)/u','/^(تعریف|تعاریف|مفاهیم|مقدمه)/u','/^(key|main|important)\s+(points|takeaways|notes|ideas)/ui','/^(in summary|to summarize|following are|here are|the following|this section|this chapter)/ui'];
        $t = trim($s);
        foreach ($p as $x) if (preg_match($x, $t)) return true;
        return false;
    }

    private function detDocType(): void {
        $t = implode("\n", $this->ss);
        $tl = mb_strtolower($t);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $tl, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = max(1, is_array($words) ? count($words) : 1);

        $count = function (string $pattern) use ($tl): int {
            $n = preg_match_all($pattern, $tl);
            return $n === false ? 0 : $n;
        };

        $philosophy = $count('/(فلسفه|فلسفی|هستی.?شناسی|معرفت.?شناسی|اخلاق هنجاری|زیبایی.?شناسی|متافیزیک|ماهیت|چیستی|مغالطه|پیش.?فرض|گزاره|برهان|قیاس|تناقض|دیالکتیک|آزادی اراده|عدالت|حقیقت|آگاهی|وجود|جوهر|philosoph\\w*|metaphysic\\w*|epistemolog\\w*|ontology|ontological|ethic\\w*|aesthetic\\w*|premise|argument|syllogism|fallacy|dialectic|free will|justice|truth|consciousness)/ui');
        $argumentSignals = $count('/(مدعا|دعوی|تز|استدلال|برهان|مقدمه|فرض|اصل|اعتراض|نقد|پاسخ|ردّ|نتیجه|بنابراین|از این رو|thesis|claim|argu\\w*|proof|premise|assumption|principle|objection|counterargument|rebuttal|reply|conclusion|therefore)/ui');
        $definitionSignals = $count('/(تعریف|مفهوم|ماهیت|چیستی|معنا|تعریف می‌شود|definition|concept|nature|meaning|is defined as)/ui');

        $acad = $count('/(چکیده|مقدمه|روش‌ها|نتایج|بحث|نتیجه‌گیری|منبع|مراجع|abstract|introduction|methods|results|discussion|references|conclusion)/ui');
        $news = $count('/(به گزارش|خبرگزاری|تسنیم|ایرنا|ایسنا|خبرنگار|گزارش می‌دهد|منبع:|according to|reported by|news agency|press|source:)/ui');
        $nar = $count('/(روزی روزگاری|یکی بود|قصه|داستان|افسانه|once upon a time|story|tale|legend)/ui');
        $howto = $count('/(مرحله|گام|اول|دوم|سوم|چهارم|نحوه|آموزش|راهنما|step|stage|first|second|third|how to|tutorial|guide)/ui');

        $philosophyDensity = ($philosophy + 0.65 * $argumentSignals + 0.35 * $definitionSignals) / $wordCount;
        if (($philosophy >= 3 && $argumentSignals >= 2)
            || ($philosophy >= 5)
            || ($philosophyDensity >= 0.018 && $argumentSignals >= 1)) {
            $this->docType = 'philosophy';
        } elseif ($acad >= 4) $this->docType = 'academic';
        elseif ($news >= 2) $this->docType = 'news';
        elseif ($nar >= 2) $this->docType = 'narrative';
        elseif ($howto >= 3) $this->docType = 'howto';
        else $this->docType = 'general';
    }

    /**
     * Assign a structural role to each sentence. This is intentionally rule-based:
     * it improves selection discipline without pretending to prove factual truth.
     */
    private function argumentRole(string $sentence): string {
        $s = mb_strtolower(trim($sentence));
        if (preg_match('/^(در نتیجه|بنابراین|پس|از این رو|نتیجه اینکه|حاصل آنکه|in conclusion|therefore|thus|hence|consequently|to conclude)/ui', $s)
            || preg_match('/(نتیجه می.?گیریم|می‌توان نتیجه گرفت|نتیجه نهایی|we can conclude|it follows that)/ui', $s)) return 'conclusion';

        if (preg_match('/(اعتراض|اشکال|نقد|مخالفت|در مقابل|با این حال|اما|ولی|counterargument|objection|criticism|however|nevertheless|on the other hand|in contrast)/ui', $s)) return 'objection';

        if (preg_match('/(پاسخ به|در پاسخ|پاسخ می‌دهد|ردّ این اعتراض|این نقد را رد|reply|response to|rebuttal|answers the objection)/ui', $s)) return 'reply';

        if (preg_match('/(مقدمه|پیش.?فرض|فرض کنیم|فرض بر این است|اصل موضوع|بر اساس این اصل|premise|assumption|let us assume|axiom|principle)/ui', $s)) return 'premise';

        if (preg_match('/(مدعا|دعوی|تز|ادعا می‌کند|استدلال می‌کند|برهان اصلی|thesis|the claim|argues that|argument is)/ui', $s)) return 'claim';

        if (preg_match('/(تعریف|مفهوم|ماهیت|چیستی|به معنای|عبارت است از|definition|concept|is defined as|refers to|means that)/ui', $s)) return 'definition';

        if (preg_match('/(زیرا|چون|به دلیل|علت|سبب|منجر به|در نتیجه.?ی|because|since|due to|cause|leads to|results in)/ui', $s)) return 'cause_effect';

        if (preg_match('/([0-9۰-۹٠-٩]+\\s*(%|٪|درصد|نفر|سال|میلیون|میلیارد)|آمار|بر اساس داده|مطالعه نشان|گزارش نشان|according to data|\\d+\\s*%|statistics|study found)/ui', $s)) return 'evidence';

        if (preg_match('/(برای مثال|به عنوان نمونه|نمونه.?ی|مثلاً|for example|for instance|such as|e\\.g\\.)/ui', $s)) return 'example';

        if (preg_match('/(از سوی دیگر|در مقابل|در مقایسه|برخلاف|در حالی که|whereas|compared with|unlike|in contrast)/ui', $s)) return 'contrast';
        return 'normal';
    }

    /**
     * Recover missing structural roles after redundancy filtering.
     * Philosophy receives a stronger argument-chain constraint; general texts
     * receive balanced factual and explanatory anchors.
     */
    private function structureRepair(array $sel, int $n, float $r): array {
        if ($n < 4 || empty($this->ss)) return array_values(array_unique($sel));
        $sel = array_values(array_unique(array_filter($sel, function ($i) use ($n) {
            return is_int($i) && $i >= 0 && $i < $n;
        })));
        $philosophy = ($this->docType === 'philosophy');

        $rolePriority = $philosophy
            ? ['claim', 'premise', 'objection', 'reply', 'conclusion', 'definition', 'evidence']
            : ['definition', 'evidence', 'cause_effect', 'contrast', 'example', 'conclusion'];

        $present = [];
        $available = [];
        foreach ($this->ss as $i => $sentence) {
            $role = $this->argumentRole($sentence);
            if ($role !== 'normal') $available[$role][] = $i;
            if (in_array($i, $sel, true) && $role !== 'normal') $present[$role] = true;
        }

        $baseCap = $philosophy ? 0.76 : 0.70;
        $ratioCap = min($baseCap, max(0.42, $r + ($philosophy ? 0.24 : 0.20)));
        $cap = min($n, max(count($sel), (int)ceil($n * $ratioCap)));
        $addLimit = $philosophy ? 5 : 4;
        $added = 0;

        foreach ($rolePriority as $role) {
            if ($added >= $addLimit || count($sel) >= $cap) break;
            if (!empty($present[$role]) || empty($available[$role])) continue;

            $best = null;
            $bestScore = -INF;
            foreach ($available[$role] as $idx) {
                if (in_array($idx, $sel, true)) continue;
                $score = (float)($this->sc[$idx] ?? 0.0);
                if (!empty($this->head[$idx])) $score += 0.45;
                if (in_array($idx, $this->mki, true)) $score += 0.35;
                if ($role === 'conclusion' && $idx >= (int)floor($n * 0.55)) $score += 0.30;
                if (($role === 'claim' || $role === 'premise') && $idx <= (int)ceil($n * 0.40)) $score += 0.22;
                foreach ($sel as $chosen) {
                    if (abs($idx - $chosen) === 1) { $score += 0.10; break; }
                }
                if ($score > $bestScore) { $bestScore = $score; $best = $idx; }
            }
            if ($best !== null) {
                $sel[] = $best;
                $present[$role] = true;
                $added++;
            }
        }

        sort($sel);
        return array_values(array_unique($sel));
    }

    private function extractEntities(string $s): array {
        if (isset($this->entCache[$s])) return $this->entCache[$s];
        $e = [];
        if (preg_match_all('/\d+[\.,]?\d*\s*(%|٪|درصد|میلیون|میلیارد|هزار|نفر|سال|متر|کیلو|گرم|دلار|تومان|یورو|million|billion|thousand|percent|people|years|meters|kilograms|grams|dollars|euros)/ui', $s, $m)) foreach ($m[0] as $x) $e[] = 'NUM:' . mb_strtolower($x);
        if (preg_match_all('/\b(13|14|19|20)\d{2}\b/u', $s, $m)) foreach ($m[0] as $x) $e[] = 'YR:' . $x;
        if (preg_match_all('/\b[A-Z][a-zA-Z]{2,}(?:\s+[A-Z][a-zA-Z]{2,}){0,2}\b/u', $s, $m)) foreach ($m[0] as $x) $e[] = 'LAT:' . mb_strtolower($x);
        if (preg_match_all('/(?:^|[\s«"])([\p{L}]{3,}(?:\s+[\p{L}]{3,}){0,2})\s+(?:گفت|کرد|است|بود|شد|دارد|داد|آمد|رفت|دید|said|stated|claimed|argued|believed|thought|wrote|defined|introduced)/ui', $s, $m)) foreach ($m[1] as $x) $e[] = 'PERS:' . mb_strtolower(trim($x));
        if (preg_match_all('/(?:کتاب|نویسنده|نوشته|انتشارات|اثر|سازمان|شرکت|دانشگاه|خبرگزاری|وزارت|اداره|book|author|writer|publisher|organization|company|university|news agency|ministry|department)\s+[«"]?([\p{L}\s]{2,40})[»"]?/ui', $s, $m)) foreach ($m[1] as $x) $e[] = 'ORG:' . mb_strtolower(trim($x));
        if (preg_match_all('/[«"]([^»"]{5,200})[»"]/u', $s, $m)) $e[] = 'QUOTE:' . count($m[0]);
        $out = array_values(array_unique($e));
        if (count($this->entCache) >= self::CACHE_ENT_MAX) {
            $this->entCache = array_slice($this->entCache, -((int)(self::CACHE_ENT_MAX / 2)), null, true);
        }
        return $this->entCache[$s] = $out;
    }

    private function compCen(): array {
        $n = count($this->v);
        if ($n === 0) return [];
        if ($n === 1) return [0 => 1.0];
        $g = [];
        for ($i = 0; $i < $n; $i++) {
            $g[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) { $g[$i][$j] = 0.0; continue; }
                $s = $this->comb($i, $j);
                if (abs($i - $j) <= 2) $s *= 1.2;
                if (abs($i - $j) === 1) $s *= 1.1;
                $g[$i][$j] = $s;
            }
        }
        $sr = [];
        for ($i = 0; $i < $n; $i++) $sr[$i] = array_sum($g[$i]) ?: 1.0;
        $rk = array_fill(0, $n, 1.0 / $n);
        $d = $this->TD;
        for ($it = 0; $it < $this->TI; $it++) {
            $nr = array_fill(0, $n, (1 - $d) / $n);
            $df = 0.0;
            for ($i = 0; $i < $n; $i++) {
                $si = 0.0;
                for ($j = 0; $j < $n; $j++) {
                    if ($i === $j) continue;
                    $w = $g[$j][$i] / $sr[$j];
                    $si += $w * $rk[$j];
                }
                $nr[$i] += $d * $si;
                $df += abs($nr[$i] - $rk[$i]);
            }
            $rk = $nr;
            if ($df < 1e-4) break;
        }
        $mx = max($rk) ?: 1.0;
        $mn = min($rk);
        $rg = max($mx - $mn, 1e-9);
        foreach ($rk as $i => $r) $rk[$i] = ($r - $mn) / $rg;
        return $rk;
    }

    private function cos(array $a, array $b): float {
        if (empty($a) || empty($b)) return 0.0;
        $s = 0.0;
        if (count($a) > count($b)) { $t = $a; $a = $b; $b = $t; }
        foreach ($a as $k => $v) if (isset($b[$k])) $s += $v * $b[$k];
        if ($s < 0) $s = 0;
        if ($s > 1) $s = 1;
        return $s;
    }

    private function emb(array $a, array $b): float {
        if (empty($a) || empty($b)) return 0.0;
        $s = 0.0;
        $l = min(count($a), count($b));
        for ($i = 0; $i < $l; $i++) $s += $a[$i] * $b[$i];
        if ($s < 0) $s = 0;
        if ($s > 1) $s = 1;
        return $s;
    }

    private function selInit(int $k): array {
        $n = count($this->ss);
        if ($n <= $k) return range(0, $n - 1);
        $allEnt = [];
        for ($i = 0; $i < $n; $i++) {
            foreach ($this->extractEntities($this->ss[$i]) as $e) $allEnt[$e][] = $i;
        }
        $locked = [];
        foreach ($allEnt as $e => $sents) {
            if (count($sents) === 1 && (strpos($e, 'PERS:') === 0 || strpos($e, 'ORG:') === 0 || strpos($e, 'YR:') === 0)) {
                $locked[$sents[0]] = true;
            }
        }
        if ($n > 2) { $locked[0] = true; $locked[$n - 1] = true; }
        foreach ($this->mki as $m) $locked[$m] = true;
        foreach ($this->head as $i => $h) if ($h) $locked[$i] = true;
        foreach ($this->isI as $i => $v) if ($v) $locked[$i] = true;
        $locked = array_keys($locked);
        $maxLock = max($k, (int)ceil($n * 0.50));
        if (count($locked) > $maxLock) {
            $sc = [];
            foreach ($locked as $s) $sc[$s] = $this->sc[$s] ?? 0;
            arsort($sc);
            $locked = array_slice(array_keys($sc), 0, $maxLock);
        }
        $sel = $locked;
        if (count($sel) < $k) $sel = $this->fillMMR($sel, $k);
        if (count($sel) < $k) {
            $srt = $this->sc;
            arsort($srt);
            foreach (array_keys($srt) as $c) { if (count($sel) >= $k) break; if (in_array($c, $sel, true)) continue; $sel[] = $c; }
        }
        return array_values(array_unique(array_slice($sel, 0, $k)));
    }

    private function detQA(array $sel): array {
        $e = $sel;
        foreach ($sel as $s) {
            $t = $this->ss[$s] ?? '';
            if (preg_match('/^(پاسخ|جواب|answer)\s*[:؛]/ui', $t)) { for ($j = $s - 1; $j >= max(0, $s - 3); $j--) if (preg_match('/\?|؟/u', $this->ss[$j] ?? '')) { if (!in_array($j, $e, true)) $e[] = $j; break; } }
            if (preg_match('/^(سوال|پرسش|question)\s*[:؛]/ui', $t)) { for ($j = $s + 1; $j <= min(count($this->ss) - 1, $s + 3); $j++) if (preg_match('/^(پاسخ|جواب|answer)\s*[:؛]/ui', $this->ss[$j] ?? '')) { if (!in_array($j, $e, true)) $e[] = $j; break; } }
        }
        return array_values(array_unique($e));
    }

    private function balBul(array $sel, int $k): array {
        if (!$this->lm) return $sel;
        $hi = false; $bc = 0;
        foreach ($sel as $s) { if (!empty($this->isI[$s])) $hi = true; if (!empty($this->isB[$s])) $bc++; }
        if ($hi && $bc < 2 && count($sel) < $k) {
            $nd = min(2 - $bc, $k - count($sel));
            $cd = [];
            foreach ($this->ss as $i => $s) if (!empty($this->isB[$i]) && !in_array($i, $sel, true)) $cd[$i] = $this->sc[$i] ?? 0;
            arsort($cd);
            foreach (array_slice(array_keys($cd), 0, $nd) as $i) $sel[] = $i;
        }
        return array_values(array_unique($sel));
    }

    private function fillTQ(array $sel, int $k): array {
        if (count($sel) >= $k) return $sel;
        if (empty($this->tp)) return $this->fillMMR($sel, $k);
        $rm = [];
        foreach (array_keys($this->sc) as $s) if (!in_array($s, $sel, true)) $rm[] = $s;
        $tc = [];
        foreach ($this->tp as $i => $_) $tc[$i] = 0;
        foreach ($sel as $s) { $t = $this->stp[$s] ?? 0; $tc[$t] = ($tc[$t] ?? 0) + 1; }
        while (count($sel) < $k && !empty($rm)) {
            $b = null; $bs = -INF; $lm = $this->ML;
            foreach ($rm as $c) {
                $r = $this->sc[$c];
                $ms = 0.0;
                foreach ($sel as $s) { $si = $this->comb($c, $s); if ($si > $ms) $ms = $si; }
                $mm = $lm * $r - (1 - $lm) * $ms;
                $ti = $this->stp[$c] ?? 0;
                $tb = 1.0 - min(1.0, $tc[$ti] / max(1, $k * $this->TQW));
                $mm += 0.20 * $tb;
                $mm += 0.10 * ($this->es[$c] - 1.0);
                $tin = $this->stp2[$c] ?? ['bonus'=>0.0];
                $mm += $tin['bonus'] * 0.15;
                $mm -= $this->ivs[$c] * 0.5;
                if ($this->head[$c]) $mm += 0.15;
                if (!empty($this->hlp[$c])) { $ro = false; for ($ri = $c - 1; $ri >= max(0, $c - 4); $ri--) if (in_array($ri, $sel, true)) { $ro = true; break; } if (!$ro) $mm *= 0.6; }
                if ($mm > $bs) { $bs = $mm; $b = $c; }
            }
            if ($b === null) break;
            $sel[] = $b;
            $ti = $this->stp[$b] ?? 0;
            $tc[$ti] = ($tc[$ti] ?? 0) + 1;
            $rm = array_values(array_diff($rm, [$b]));
        }
        return $sel;
    }

    private function fillMMR(array $sel, int $k): array {
        if (count($sel) >= $k) return $sel;
        $srt = $this->sc;
        arsort($srt);
        $co = array_keys($srt);
        $rm = [];
        foreach ($co as $c) if (!in_array($c, $sel, true)) $rm[] = $c;
        while (count($sel) < $k && !empty($rm)) {
            $b = null; $bs = -INF;
            foreach ($rm as $c) {
                $r = $this->sc[$c];
                $ms = 0.0;
                foreach ($sel as $s) { $si = $this->comb($c, $s); if ($si > $ms) $ms = $si; }
                $mm = $this->ML * $r - (1 - $this->ML) * $ms;
                if ($mm > $bs) { $bs = $mm; $b = $c; }
            }
            if ($b === null) break;
            $sel[] = $b;
            $rm = array_values(array_diff($rm, [$b]));
        }
        return $sel;
    }

    private function resCG(array $sel): array {
        $ss = array_flip($sel);
        $ta = []; $tr = [];
        foreach ($sel as $s) {
            if (empty($this->hlp[$s])) continue;
            $rf = false;
            for ($ri = $s - 1; $ri >= max(0, $s - 4); $ri--) if (isset($ss[$ri])) { $rf = true; break; }
            if (!$rf && isset($this->ct[$s]) && $this->ct[$s] !== null) {
                $r = $this->ct[$s];
                if (!isset($ss[$r])) $ta[] = $r;
            } elseif (!$rf) $tr[] = $s;
        }
        $sel = array_merge($sel, $ta);
        if (!empty($tr)) $sel = array_values(array_diff($sel, $tr));
        return array_values(array_unique($sel));
    }

    private function isRed(int $a, int $b): bool {
        if (!isset($this->ss[$a], $this->ss[$b])) return false;
        $sa = trim($this->ss[$a]); $sb = trim($this->ss[$b]);
        if ($sa === '' || $sb === '') return false;

        preg_match_all('/[0-9۰-۹٠-٩]+(?:[.,٫][0-9۰-۹٠-٩]+)?/u', $sa, $na);
        preg_match_all('/[0-9۰-۹٠-٩]+(?:[.,٫][0-9۰-۹٠-٩]+)?/u', $sb, $nb);
        $numsA = array_values(array_unique($na[0] ?? []));
        $numsB = array_values(array_unique($nb[0] ?? []));
        if ($numsA && $numsB && $numsA !== $numsB) return false;

        $ea = $this->extractEntities($sa); $eb = $this->extractEntities($sb);
        $namedA = array_values(array_filter($ea, function ($x) {
            return strpos($x, 'PERS:') === 0 || strpos($x, 'ORG:') === 0 || strpos($x, 'LAT:') === 0 || strpos($x, 'YR:') === 0;
        }));
        $namedB = array_values(array_filter($eb, function ($x) {
            return strpos($x, 'PERS:') === 0 || strpos($x, 'ORG:') === 0 || strpos($x, 'LAT:') === 0 || strpos($x, 'YR:') === 0;
        }));
        if ($namedA && $namedB && count(array_intersect($namedA, $namedB)) === 0) return false;

        $neg = '/(?:\bnot\b|\bno\b|\bnever\b|\bwithout\b|\bneither\b|نیست|نبود|نمی\p{L}+|هرگز|بدون|عدم)/ui';
        if ((bool)preg_match($neg, $sa) !== (bool)preg_match($neg, $sb)) return false;

        if ($this->docType === 'philosophy') {
            $roleA = $this->argumentRole($sa);
            $roleB = $this->argumentRole($sb);
            if ($roleA !== 'normal' && $roleB !== 'normal' && $roleA !== $roleB) return false;
        }

        $typeA = $this->stp2[$a]['type'] ?? 'normal';
        $typeB = $this->stp2[$b]['type'] ?? 'normal';
        $protectedTypes = ['statistical', 'definition', 'conclusion', 'classification'];
        if ((in_array($typeA, $protectedTypes, true) || in_array($typeB, $protectedTypes, true)) && $typeA !== $typeB) return false;

        $ta = array_values(array_unique($this->st[$a] ?? []));
        $tb = array_values(array_unique($this->st[$b] ?? []));
        if (!$ta || !$tb) return false;
        $setA = array_fill_keys($ta, true); $setB = array_fill_keys($tb, true);
        $intersection = count(array_intersect_key($setA, $setB));
        $union = count($setA) + count($setB) - $intersection;
        $jaccard = $union > 0 ? $intersection / $union : 0.0;
        $containment = $intersection / max(1, min(count($setA), count($setB)));
        $similarity = $this->comb($a, $b);
        $adjacent = abs($a - $b) <= 1;

        // Similarity alone is insufficient: lexical evidence must also be strong.
        if ($adjacent) return $jaccard >= 0.78 && ($similarity >= 0.70 || $containment >= 0.90);
        return $jaccard >= 0.84 && ($similarity >= 0.76 || $containment >= 0.94);
    }

    private function extEntTok(int $i): array {
        $s = $this->ss[$i] ?? '';
        $o = [];
        preg_match_all('/\d+[\.,]?\d*/u', $s, $m);
        foreach ($m[0] as $n) $o[] = 'NUM:' . $n;
        preg_match_all('/\b[A-Z][a-z]{2,}\b/u', $s, $m2);
        foreach ($m2[0] as $n) $o[] = 'ENT:' . mb_strtolower($n);
        return $o;
    }

    private function balTop(array $sel, int $k): array {
        if (count($this->tp) < 2) return $sel;
        $tc = [];
        foreach ($this->tp as $i => $_) $tc[$i] = 0;
        foreach ($sel as $s) { $t = $this->stp[$s] ?? 0; $tc[$t] = ($tc[$t] ?? 0) + 1; }
        $et = [];
        foreach ($tc as $i => $c) if ($c === 0) $et[] = $i;
        foreach ($et as $ti) {
            if (count($sel) >= $k) break;
            $b = null; $bs = -INF;
            foreach ($this->tp[$ti] as $s) {
                if (in_array($s, $sel, true)) continue;
                if (!isset($this->sc[$s])) continue;
                if ($this->sc[$s] > $bs) { $bs = $this->sc[$s]; $b = $s; }
            }
            if ($b !== null) $sel[] = $b;
        }
        return array_values(array_unique($sel));
    }

    private function remOrph(array $sel): array {
        if (count($sel) <= 1 || !$this->lm) return $sel;
        $hi = false;
        foreach ($sel as $s) if (!empty($this->isI[$s])) { $hi = true; break; }
        $bc = 0;
        foreach ($sel as $s) if (!empty($this->isB[$s])) $bc++;
        if ($bc === 1 && !$hi) {
            $kp = [];
            foreach ($sel as $s) { if (!empty($this->isB[$s])) continue; $kp[] = $s; }
            if (!empty($kp)) return $kp;
        }
        return $sel;
    }

    private function enfR(array $sel, int $n, float $r): array {
        $tk = max(1, (int)round($n * $r));
        if ($this->mode === 'minimal' || $this->mode === 'micro' || $this->mode === 'tiny') $tk = max(1, $tk);
        elseif ($this->mode === 'short') $tk = max(2, $tk);
        $tk = min($tk, (int)ceil($n * 0.60));
        if ($n >= 3) $tk = max(2, $tk);
        $mk = $this->mki;
        $mis = array_intersect($mk, $sel);
        $mm = min(count($mis), count($mk), 4);
        $tk = max($tk, $mm);
        if (count($sel) <= $tk) return $sel;
        $globalEnt = [];
        for ($i = 0; $i < $n; $i++) {
            foreach ($this->extractEntities($this->ss[$i]) as $e) $globalEnt[$e] = ($globalEnt[$e] ?? 0) + 1;
        }
        $protected = [];
        foreach ($sel as $s) {
            foreach ($this->extractEntities($this->ss[$s]) as $e) {
                if (($globalEnt[$e] ?? 0) === 1 && (strpos($e, 'PERS:') === 0 || strpos($e, 'ORG:') === 0 || strpos($e, 'YR:') === 0)) {
                    $protected[$s] = true;
                    break;
                }
            }
        }
        if (count($protected) > $tk) {
            $psc = [];
            foreach (array_keys($protected) as $s) $psc[$s] = $this->sc[$s] ?? 0;
            arsort($psc);
            $protected = array_flip(array_slice(array_keys($psc), 0, $tk));
        }
        $sc = [];
        foreach ($sel as $s) {
            $sc[$s] = $this->sc[$s] ?? 0;
            if (!empty($this->isI[$s])) $sc[$s] += 0.5;
            if (!empty($this->isB[$s]) && $this->lm) $sc[$s] += 0.15;
            $ti = $this->stp2[$s] ?? ['bonus'=>0.0];
            $sc[$s] += $ti['bonus'] * 0.4;
            if (in_array($s, $mk, true)) $sc[$s] += 3.0;
            $sc[$s] -= $this->ivs[$s] * 2.0;
            if (isset($protected[$s])) $sc[$s] += 10.0;
        }
        arsort($sc);
        $top = array_slice(array_keys($sc), 0, $tk);
        if ($n > 2) {
            if (!in_array(0, $top, true)) $top[count($top) - 1] = 0;
            if (!in_array($n - 1, $top, true)) $top[count($top) - 1] = $n - 1;
        }
        return array_values(array_unique($top));
    }

    private function ensMin(array $sel, int $n, int $tl): array {
        if (empty($sel) && $n > 0) $sel[] = 0;
        if (($this->mode === 'minimal' || $this->mode === 'micro' || $this->mode === 'tiny') && empty($sel) && $n >= 1) $sel[] = 0;
        if (count($sel) < 2 && $n >= 2 && $tl > 200) {
            $srt = $this->sc;
            arsort($srt);
            foreach (array_keys($srt) as $c) if (!in_array($c, $sel, true)) { $sel[] = $c; break; }
        }
        return array_values(array_unique($sel));
    }

    private function mrgFrag(array $sel): array {
        if (count($sel) <= 1) return $sel;
        $mg = []; $sk = [];
        foreach ($sel as $i => $s) {
            if (in_array($s, $sk, true)) continue;
            $cur = $s;
            for ($j = $i + 1; $j < count($sel); $j++) {
                $nx = $sel[$j];
                if (in_array($nx, $sk, true)) continue;
                if (abs($nx - $cur) === 1) {
                    $ta = $this->stp2[$cur]['type'] ?? 'normal';
                    $tb = $this->stp2[$nx]['type'] ?? 'normal';
                    if (($ta === 'fragment' || $tb === 'fragment') && $this->comb($cur, $nx) > 0.20) {
                        $sk[] = $nx;
                        $this->ss[$cur] = rtrim($this->ss[$cur], '.') . ' ' . ltrim($this->ss[$nx]);
                        continue;
                    }
                }
                break;
            }
            $mg[] = $cur;
        }
        return array_values(array_unique($mg));
    }

    private function ordRead(array $sel): array {
        if (count($sel) <= 2) return $sel;
        $concl = []; $frag = []; $rest = [];
        foreach ($sel as $s) {
            $t = $this->stp2[$s]['type'] ?? 'normal';
            if ($t === 'conclusion') $concl[] = $s;
            elseif ($t === 'exercise' || $t === 'fragment') $frag[] = $s;
            else $rest[] = $s;
        }
        sort($rest); sort($concl); sort($frag);
        return array_merge($rest, $concl, $frag);
    }

    private function joinSec(array $idx): string {
        if (empty($idx)) return '';
        sort($idx);
        $o = [];
        foreach ($idx as $i) {
            if (!isset($this->ss[$i])) continue;
            $t = trim($this->ss[$i]);
            if ($t === '') continue;
            $o[] = $this->ens($t);
        }
        $out = implode(' ', $o);
        $out = $this->restoreMarkup($out);
        return $this->resUrls($out);
    }

    private function compSum(array $idx): string {
        if (empty($idx)) return '';
        $sec = [];
        foreach ($idx as $i) {
            if (!isset($this->ss[$i])) continue;
            $p = $this->sp[$i] ?? 0;
            if (!isset($sec[$p])) $sec[$p] = [];
            $sec[$p][] = $i;
        }
        ksort($sec);
        $pr = [];
        foreach ($sec as $pi => $is) {
            sort($is);
            $sn = [];
            foreach ($is as $i) {
                $t = trim($this->ss[$i]);
                if ($t === '') continue;
                $t = $this->balPar($t);
                $t = $this->cleanEnd($t);
                if ($t === '') continue;
                $sn[] = $t;
            }
            if (empty($sn)) continue;
            $pr[] = $this->joinSP($sn);
        }
        $o = count($pr) > 1 ? implode("\n\n", $pr) : implode(' ', $pr);
        $o = $this->remRepW($o);
        if (count($pr) === 1 && count($idx) >= 6) $o = $this->brkLong($o);
        $o = $this->restoreMarkup($o);
        $o = $this->resUrls($o);
        if (!empty($this->up)) {
            $hasUrl = false;
            foreach ($this->up as $u) if (mb_strpos($o, $u) !== false) { $hasUrl = true; break; }
            if (!$hasUrl) {
                $firstUrl = reset($this->up);
                $o .= ' ' . $firstUrl;
            }
        }
        return $o;
    }

    private function brkLong(string $t): string {
        $ss = preg_split('/(?<=[.؟!])\s+/u', $t);
        if (!is_array($ss) || count($ss) < 6) return $t;
        $ch = []; $cs = 3; $tt = count($ss);
        for ($i = 0; $i < $tt; $i += $cs) $ch[] = implode(' ', array_slice($ss, $i, $cs));
        return implode("\n\n", $ch);
    }

    private function remRepW(string $t): string {
        $tk = preg_split('/(\s+)/u', $t, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($tk)) return $t;
        $o = []; $ln = ''; $lw = false;
        foreach ($tk as $x) {
            if ($x === '') continue;
            if (preg_match('/^\s+$/u', $x)) { if (!$lw) $o[] = ' '; $lw = true; continue; }
            $n = mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $x));
            if ($n !== '' && $n === $ln) continue;
            $o[] = $x;
            $ln = $n;
            $lw = false;
        }
        return trim(implode('', $o));
    }

    private function joinSP(array $sn): string {
        $c = count($sn);
        if ($c === 1) return $this->ens($sn[0]);
        $o = '';
        for ($i = 0; $i < $c; $i++) {
            $t = $sn[$i];
            $il = ($i === $c - 1);
            if ($il) $o .= $this->ens($t);
            else {
                $nx = $sn[$i + 1];
                $ec = (bool)preg_match('/[:؛]\s*$/u', $t);
                if ($ec) $o .= $t . ' ';
                else { $sep = $this->smartSep($t, $nx); $o .= $t . $sep; }
            }
        }
        return trim(preg_replace('/[ \t]+/u', ' ', $o));
    }

    private function smartSep(string $a, string $b): string {
        $b = trim($b);
        $aTrim = rtrim($a);
        $aEnd = mb_substr($aTrim, -1);
        if (preg_match('/[؟?!…]$/u', $aEnd)) return ' ';
        if (preg_match('/[:؛]$/u', $aEnd)) return ' ';
        $conjDot = ['دور ','ولی ','اما ','به عنوان مثال ','برای اینکه ','در نتیجه ','however ','but ','yet ','for example ','therefore ','thus ','hence ','in conclusion ','in summary '];
        $conjComma = ['و ','یا ','همچنین ','زیرا ','چون ','آنابراین ','در نتیجه ','به عنوان مثال ','از این رو ','به این ترتیب ','نهایتاً ','در پی این ','به علاوه ','and ','or ','also ','because ','since ','so ','moreover ','furthermore ','in addition ','for instance '];
        foreach ($conjDot as $cj) if (mb_stripos($b, $cj) === 0) return '. ';
        foreach ($conjComma as $cj) if (mb_stripos($b, $cj) === 0) return '، ';
        $ai = array_search($a, $this->ss, true);
        $bi = array_search($b, $this->ss, true);
        if ($ai !== false && $bi !== false) {
            $sim = $this->comb($ai, $bi);
            $ta = $this->stp2[$ai]['type'] ?? 'normal';
            $tb = $this->stp2[$bi]['type'] ?? 'normal';
            if ($ta === 'definition' && $tb === 'definition') return '. ';
            if ($ta === 'conclusion' || $tb === 'conclusion') return '. ';
            if ($ta === 'classification' && $tb === 'classification') return '؛ ';
            if ($sim > 0.42) return '؛ ';
            if ($sim > 0.22) return '، ';
        }
        return '. ';
    }

    private function balPar(string $s): string {
        $o = substr_count($s, '('); $c = substr_count($s, ')');
        if ($o > $c) $s .= str_repeat(')', $o - $c);
        elseif ($o < $c) while ($o < $c && preg_match('/[\)]\s*$/u', $s)) { $s = preg_replace('/[\)]\s*$/u', '', $s); $c--; }
        $o = substr_count($s, '«'); $c = substr_count($s, '»');
        if ($o > $c) $s .= str_repeat('»', $o - $c);
        elseif ($o < $c) while ($o < $c && preg_match('/[»]\s*$/u', $s)) { $s = preg_replace('/[»]\s*$/u', '', $s); $c--; }
        $o = substr_count($s, '['); $c = substr_count($s, ']');
        if ($o > $c) $s .= str_repeat(']', $o - $c);
        elseif ($o < $c) while ($o < $c && preg_match('/[\]]\s*$/u', $s)) { $s = preg_replace('/[\]]\s*$/u', '', $s); $c--; }
        if (preg_match('/«[^»]*$/u', $s)) $s .= '»';
        return $s;
    }

    private function cleanEnd(string $t): string {
        $t = rtrim($t);
        $t = $this->balPar($t);
        $t = preg_replace('/[.؟!。！？؛،,]+$/u', '', $t);
        $t = $this->balPar($t);
        return rtrim($t);
    }

    private function ens(string $t): string {
        $t = rtrim($t);
        $t = $this->balPar($t);
        if (!preg_match('/[.؟!。！？…]$/u', $t)) return $t . '.';
        return $t;
    }

    private function bRes(string $orig, string $sm, float $r, int $tt, int $sel, string $m = 'mid'): array {
        $ol = mb_strlen($orig);
        $sl = mb_strlen($sm);
        return ['success'=>true,'summary'=>$sm,'stats'=>['original_length'=>$ol,'summary_length'=>$sl,'compression_ratio'=>$ol>0?round((1-$sl/$ol)*100,1):0,'target_compression'=>round($r*100,1),'total_sentences'=>$tt,'sentences_extracted'=>$sel,'processing_time'=>round(microtime(true)-$this->t0,3),'language'=>$this->lc,'language_name'=>$this->lang['name']??$this->lc,'paragraphs'=>count($this->ps),'topics'=>count($this->tp),'mode'=>$m,'restricted_ratio'=>$this->rr2]];
    }
}

LangData::setBasePath(__DIR__ . '/lang');

/* ═══════════ Wrapper ═══════════ */
if (!function_exists('weight_analyze_text')) {
    function weight_analyze_text(string $text): ?array {
        if (!class_exists('WeightEngine', false)) return null;
        if (mb_strlen($text) < 75) return null;
        try {
            $wLang = weight_detect_lang($text);
            $engine = new WeightEngine($wLang, WEIGHT_LANG_PATH);
            $sentences = preg_split('/(?<=[.!?؟。！？…])\s+/u', $text);
            if (!is_array($sentences)) $sentences = [$text];
            $sentences = array_values(array_filter($sentences, function ($s) {
                return trim((string)$s) !== '' && mb_strlen(trim((string)$s)) >= 10;
            }));
            if (empty($sentences)) return null;
            $result = $engine->score($sentences);
            return [
                'dominant_topic'  => $result['dominant_topic'] ?? null,
                'dominant_topics' => $result['dominant_topics'] ?? [],
                'topic_scores'    => array_slice($result['topic_scores'] ?? [], 0, 10, true),
                'doc_type'        => $result['doc_type'] ?? 'general',
                'weights'         => $result['weights'] ?? [],
                'sentence_count'  => count($sentences),
                'weight_lang'     => $wLang,
            ];
        } catch (Throwable $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log('[weight_analyze_text] ' . $e->getMessage());
            }
            return null;
        }
    }
}

if (!function_exists('summarizer_services_status')) {
    function summarizer_services_status(): array {
        return [
            'semantic_enabled'      => SEMANTIC_ENABLED,
            'semantic_available'    => SemanticBridge::isAvailable(),
            'semantic_endpoint'     => SEMANTIC_ENDPOINT,
            'abstractive_enabled'   => ABSTRACTIVE_ENABLED,
            'abstractive_available' => AbstractiveBridge::isAvailable(),
            'abstractive_endpoint'  => ABSTRACTIVE_ENDPOINT,
            'weight_loaded'         => class_exists('WeightEngine', false),
            'version'               => SUMMARIZER_VERSION,
            'bm25_enabled'          => SUMMARIZER_ENABLE_BM25,
            'max_passes'            => SUMMARIZER_MAX_PASSES,
        ];
    }
}
