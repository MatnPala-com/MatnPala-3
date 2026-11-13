<?php
declare(strict_types=1);

/**
 * Weight.php — نسخه ۴.۲ (کالیبره برای متن سخت و ساده)
 * سازگار با vip.php و index.php فعلی.
 *
 * تغییرات نسخه ۴.۲:
 *  - normalizeStrict: تشخیص رژیم توزیع (باریک vs پهن) — رفع تقویت نویز در متن ساده
 *  - selectDominant: آستانه تطبیقی + fallback به بهترین موضوع
 *  - buildSignals: fallback موضوعی وقتی dominant خالی است
 *  - score: کش کلید بهبودیافته
 */

if (defined('MATNPALA_WEIGHT_CORE_LOADED')) return;
define('MATNPALA_WEIGHT_CORE_LOADED', true);

if (!defined('WEIGHT_MIN_TOPIC_SCORE')) define('WEIGHT_MIN_TOPIC_SCORE', 0.35);
if (!defined('WEIGHT_MAX_TOPICS'))      define('WEIGHT_MAX_TOPICS', 5);
if (!defined('WEIGHT_CACHE_SIZE'))      define('WEIGHT_CACHE_SIZE', 256);
if (!defined('WEIGHT_CACHE_TTL'))       define('WEIGHT_CACHE_TTL', 300);
if (!defined('WEIGHT_LANG_PATH'))       define('WEIGHT_LANG_PATH', __DIR__ . '/lang/Weight');

/* ═══════════════════════════════════════════════════════════
 *  1) WeightTopic
 * ═══════════════════════════════════════════════════════════ */

final class WeightTopic
{
    public string $id;
    public string $name;
    public string $description;
    public array  $terms;
    public array  $keyphrases;
    public array  $synonyms;
    public array  $negative;
    public array  $docTypes;
    public float  $avgLen;
    public float  $bm25K1;
    public float  $bm25B;
    public float  $priorWeight;
    public array  $meta;

    public function __construct(string $id, array $raw)
    {
        $this->id          = $id;
        $this->name        = (string)($raw['name']        ?? $id);
        $this->description = (string)($raw['description'] ?? '');
        $this->terms       = $this->normalizeTerms($raw['terms'] ?? []);
        $this->keyphrases  = $this->normalizeList($raw['keyphrases'] ?? []);
        $this->synonyms    = $this->normalizeList($raw['synonyms']   ?? []);
        $this->negative    = $this->normalizeList($raw['negative']   ?? []);
        $this->docTypes    = $this->normalizeList($raw['doc_types']  ?? []);
        $this->avgLen      = (float)($raw['avg_len']     ?? 18.0);
        $this->bm25K1      = (float)($raw['bm25_k1']     ?? 1.5);
        $this->bm25B       = (float)($raw['bm25_b']      ?? 0.75);
        $this->priorWeight = (float)($raw['prior_weight'] ?? 1.0);
        $this->meta        = (array)($raw['meta'] ?? []);

        if (empty($this->terms) && empty($this->keyphrases)) {
            throw new InvalidArgumentException("Topic '{$id}' has no terms or keyphrases");
        }
    }

    private function normalizeTerms($raw): array
    {
        if (!is_array($raw)) return [];
        $out = [];
        foreach ($raw as $k => $v) {
            $k = mb_strtolower(trim((string)$k));
            if ($k === '') continue;
            $w = is_numeric($v) ? (float)$v : 1.0;
            $out[$k] = max(0.0, min(1.0, $w));
        }
        return $out;
    }

    private function normalizeList($raw): array
    {
        if (!is_array($raw)) return [];
        $out = [];
        foreach ($raw as $v) {
            $v = mb_strtolower(trim((string)$v));
            if ($v !== '') $out[] = $v;
        }
        return array_values(array_unique($out));
    }

    public function termCount(): int
    {
        return count($this->terms) + count($this->keyphrases);
    }
}

/* ═══════════════════════════════════════════════════════════
 *  2) TopicLoader
 * ═══════════════════════════════════════════════════════════ */

final class TopicLoader
{
    private static array $cache = [];

    public static function load(string $lang, string $basePath): array
    {
        if (isset(self::$cache[$lang])) return self::$cache[$lang];

        $path = rtrim($basePath, '/') . '/' . $lang . '.json';
        if (!is_file($path)) $path = rtrim($basePath, '/') . '/fa.json';
        if (!is_file($path)) throw new RuntimeException("Weight lang file not found: {$path}");

        $raw = @file_get_contents($path);
        if ($raw === false) throw new RuntimeException("Cannot read weight file: {$path}");

        $data = json_decode($raw, true);
        if (!is_array($data)) throw new RuntimeException("Invalid JSON in weight file: {$path}");

        $topicsRaw = $data['topics'] ?? [];
        if (!is_array($topicsRaw) || empty($topicsRaw)) throw new RuntimeException("No topics found: {$path}");

        $topics = [];
        foreach ($topicsRaw as $tid => $tRaw) {
            if (!is_array($tRaw)) continue;
            try { $topics[(string)$tid] = new WeightTopic((string)$tid, $tRaw); }
            catch (Throwable $e) { continue; }
        }
        if (empty($topics)) throw new RuntimeException("All topics invalid: {$path}");

        $builtinStopwords = preg_split('/\\s+/u', trim(<<<'WORDS'
از ازاین ازآن به با برای در را که و یا اما اگر اگرچه هرچند چون زیرا پس بنابراین
این آن اینها آنها وی ایشان ما شما من تو او آنان خود خودش خودشان هم نیز فقط تنها
یک چند بعضی بسیاری بیشتر کمتر همه تمام هیچ هر هرچه هرکس هرکدام هرچند چنین چنان
است هستند بود بودند باشد باشند شده شده‌است شده‌اند شده‌بود می‌شود می‌شوند
می‌کند می‌کنند کرد کردند کرده کرده‌است کرده‌اند کردن کردنش کردنشان
خواهد خواهند خواهم خواهی خواهیم خواهید می‌خواهند می‌خواهیم می‌خواهد
ازاین‌رو ازاین‌جهت همچنین علاوه‌بر علاوه بر مانند مثل نظیر شامل خصوص مورد
درباره دربارهٔ راجع راجع‌به نسبت نسبت‌به جهت سبب علت طریق شکل صورت نحو
تا مگر بلکه بلکه‌اینکه بلکه اینکه ضمن هنگام زمان وقت موقع جای جا
بسیار خیلی کاملاً کاملا تقریباً تقریبا حدود حدوداً حدودا تقریباً
منظور مقصود یعنی مثلا مثلاً مانندِ همان همین چنین‌که چنان‌که
نیز هم‌چنین همچون البته البته‌که آری بله خیر نه
WORDS
        )) ?: [];
        $stopwords = [];
        foreach (array_merge($builtinStopwords, (array)($data['stopwords'] ?? [])) as $w) {
            $w = mb_strtolower(trim((string)$w));
            if ($w !== '') $stopwords[$w] = true;
        }

        $df = $data['global_df'] ?? [];
        if (!is_array($df) || empty($df)) $df = self::buildDf($topics);
        $N = (int)($data['global_doc_count'] ?? count($topics));
        if ($N <= 0) $N = count($topics);

        return self::$cache[$lang] = [
            'topics'    => $topics,
            'stopwords' => $stopwords,
            'df'        => $df,
            'N'         => $N,
            'raw'       => $data,
        ];
    }

    private static function buildDf(array $topics): array
    {
        $df = [];
        foreach ($topics as $t) {
            $seen = [];
            foreach (array_keys($t->terms) as $term) {
                $seen[$term] = true;
                foreach (preg_split('/\s+/u', $term) as $tok) if ($tok !== '') $seen[$tok] = true;
            }
            foreach ($t->keyphrases as $kp) {
                foreach (preg_split('/\s+/u', $kp) as $tok) if ($tok !== '') $seen[$tok] = true;
            }
            foreach (array_keys($seen) as $term) $df[$term] = ($df[$term] ?? 0) + 1;
        }
        return $df;
    }

    public static function clearCache(): void { self::$cache = []; }
}

/* ═══════════════════════════════════════════════════════════
 *  3) Tokenizer
 * ═══════════════════════════════════════════════════════════ */

final class Tokenizer
{
    private array $stopwords;
    private string $lang;

    private const SUFFIXES_FA = ['های','ها','ترین','تری','تر','اش','شان','مان','تان','ام','ات'];
    private const SUFFIXES_EN = ['ations','ation','ness','ment','tion','sion','able','ible','ing','ies','ied','est','ful','less','ous','ive','ed','ly','es','er','s'];
    private const SUFFIXES_FR = ['ements','ement','ation','ition','euses','euse','elles','elle','eux','ment','tion','sion','ées','ée','es','s'];
    private const SUFFIXES_ES = ['mientos','miento','ciones','ción','idades','idad','mente','iendo','ando','ador','ido','ada','ado','es','os','as','s'];

    public function __construct(array $stopwords, string $lang = 'fa')
    {
        $this->stopwords = $stopwords;
        $this->lang = $lang;
    }

    public function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $text = strtr($text, [
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه',
            'ۀ' => 'ه', 'ؤ' => 'و', 'إ' => 'ا', 'أ' => 'ا', 'ٱ' => 'ا'
        ]);
        $text = str_replace(["\xE2\x80\x8C", "\xE2\x80\x8F", "\xE2\x80\x8E", "\xEF\xBB\xBF"], ' ', $text);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        if ($text === '') return [[], []];

        $raw = explode(' ', $text);
        $tokens = [];
        $stems  = [];
        foreach ($raw as $t) {
            if ($t === '' || mb_strlen($t) < 2) continue;
            if (isset($this->stopwords[$t])) continue;
            $tokens[] = $t;
            $stems[]  = $this->stem($t);
        }
        return [$tokens, $stems];
    }

    public function stem(string $w): string
    {
        $l = mb_strlen($w);
        if ($l < 6) return $w;
        foreach ($this->suffixes() as $sf) {
            $sl = mb_strlen($sf);
            if ($l > $sl + 2 && mb_substr($w, -$sl) === $sf) {
                return mb_substr($w, 0, $l - $sl);
            }
        }
        return $w;
    }

    private function suffixes(): array
    {
        switch ($this->lang) {
            case 'fa': return self::SUFFIXES_FA;
            case 'en': return self::SUFFIXES_EN;
            case 'fr': return self::SUFFIXES_FR;
            case 'es': return self::SUFFIXES_ES;
            default:   return self::SUFFIXES_FA;
        }
    }
}

/* ═══════════════════════════════════════════════════════════
 *  4) Bm25Scorer
 * ═══════════════════════════════════════════════════════════ */

final class Bm25Scorer
{
    private array $df;
    private int $N;

    public function __construct(array $df, int $N)
    {
        $this->df = $df;
        $this->N  = max(1, $N);
    }

    public function idf(string $term): float
    {
        $df = (int)($this->df[$term] ?? 0);
        if ($df > $this->N) $df = $this->N;
        return log(($this->N + 1) / ($df + 0.5)) + 1.0;
    }

    public function score(array $sentenceTokens, array $topicTerms, float $k1 = 1.5, float $b = 0.75, float $avgLen = 18.0): float
    {
        if (empty($sentenceTokens) || empty($topicTerms)) return 0.0;

        $len = count($sentenceTokens);
        $tf = array_count_values($sentenceTokens);
        $raw = 0.0;
        $max = 0.0;
        $avg = max($avgLen, 1.0);

        foreach ($topicTerms as $term => $tw) {
            $idf = $this->idf((string)$term);
            $f = (int)($tf[$term] ?? 0);
            $tw = (float)$tw;
            $max += $tw * $idf * ($k1 + 1);
            if ($f === 0) continue;
            $denom = $f + $k1 * (1 - $b + $b * ($len / $avg));
            $raw += $tw * $idf * ($f * ($k1 + 1)) / max($denom, 1e-9);
        }

        if ($max <= 0) return 0.0;
        return min(1.0, $raw / $max);
    }
}

/* ═══════════════════════════════════════════════════════════
 *  5) CosineMatcher
 * ═══════════════════════════════════════════════════════════ */

final class CosineMatcher
{
    public function similarity(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0.0;
        if (count($a) > count($b)) { $t = $a; $a = $b; $b = $t; }
        $dot = 0.0;
        foreach ($a as $k => $v) if (isset($b[$k])) $dot += $v * $b[$k];
        $na = 0.0; foreach ($a as $v) $na += $v * $v;
        $nb = 0.0; foreach ($b as $v) $nb += $v * $v;
        $na = sqrt($na); $nb = sqrt($nb);
        if ($na <= 0 || $nb <= 0) return 0.0;
        $s = $dot / ($na * $nb);
        return max(0.0, min(1.0, $s));
    }

    public function jaccard(array $a, array $b): float
    {
        if (empty($a) || empty($b)) return 0.0;
        $sa = array_flip($a);
        $sb = array_flip($b);
        $inter = count(array_intersect_key($sa, $sb));
        $union = count($sa) + count($sb) - $inter;
        return $union > 0 ? $inter / $union : 0.0;
    }
}

/* ═══════════════════════════════════════════════════════════
 *  6) KeyphraseMatcher
 * ═══════════════════════════════════════════════════════════ */

final class KeyphraseMatcher
{
    private array $index = [];

    public function __construct(array $topics)
    {
        foreach ($topics as $tid => $topic) {
            $this->index[$tid] = [];
            foreach ($topic->keyphrases as $kp) {
                $this->index[$tid][] = ['phrase' => $kp, 'weight' => 1.0];
            }
            usort($this->index[$tid], function ($a, $b) {
                return mb_strlen($b['phrase']) <=> mb_strlen($a['phrase']);
            });
        }
    }

    public function coverage(string $sentence, string $topicId): float
    {
        if (!isset($this->index[$topicId]) || empty($this->index[$topicId])) return 0.0;
        $sLower = mb_strtolower($sentence);
        $hits = 0;
        $total = count($this->index[$topicId]);
        foreach ($this->index[$topicId] as $kp) {
            if (mb_strpos($sLower, $kp['phrase']) !== false) $hits++;
        }
        return $total > 0 ? $hits / $total : 0.0;
    }

    public function hitScore(string $sentence, string $topicId, int $cap = 3): float
    {
        if (!isset($this->index[$topicId])) return 0.0;
        $sLower = mb_strtolower($sentence);
        $hits = 0;
        foreach ($this->index[$topicId] as $kp) {
            if (mb_strpos($sLower, $kp['phrase']) !== false) $hits++;
        }
        return min(1.0, $hits / max(1, $cap));
    }
}

/* ═══════════════════════════════════════════════════════════
 *  7) PositionPrior
 * ═══════════════════════════════════════════════════════════ */

final class PositionPrior
{
    private float $headSigma;
    private float $tailSigma;
    private float $midBoost;

    public function __construct(float $headSigma = 0.18, float $tailSigma = 0.12, float $midBoost = 0.35)
    {
        $this->headSigma = $headSigma;
        $this->tailSigma = $tailSigma;
        $this->midBoost  = $midBoost;
    }

    public function score(int $index, int $total): float
    {
        if ($total <= 1) return 1.0;
        $p = $index / ($total - 1);
        $head = exp(-pow($p - 0.0, 2) / (2 * $this->headSigma ** 2));
        $tail = exp(-pow($p - 1.0, 2) / (2 * $this->tailSigma ** 2));
        $mid  = $this->midBoost * exp(-pow($p - 0.5, 2) / (2 * 0.35 ** 2));
        return max($head, $tail) + $mid;
    }
}

/* ═══════════════════════════════════════════════════════════
 *  8) CueDetector
 * ═══════════════════════════════════════════════════════════ */

final class CueDetector
{
    private array $patterns;
    private array $weights;

    public function __construct(string $lang = 'fa')
    {
        $this->patterns = $this->buildPatterns($lang);
        $this->weights  = [
            'definition'   => 0.30,
            'causal'       => 0.25,
            'example'      => 0.15,
            'emphasis'     => 0.20,
            'sequence'     => 0.15,
            'numeric'      => 0.25,
            'year'         => 0.20,
            'conclusion'   => 0.28,
            'contrast'     => 0.18,
            'enumeration'  => 0.20,
        ];
    }

    private function buildPatterns(string $lang): array
    {
        if ($lang === 'fa') {
            return [
                'definition'  => '/(تعریف|تعریف می‌شود|تعریف شده است|عبارت است از|به معنای|به معنی|منظور از|مقصود از|یعنی|در اصطلاح|نامیده می‌شود|شناخته می‌شود)/u',
                'causal'      => '/(زیرا|چون|از آنجا که|ازآنجاکه|به دلیل|به علت|به سبب|در اثر|ناشی از|سبب می‌شود|منجر به|موجب می‌شود|بنابراین|در نتیجه|از این رو|ازاین‌رو|در پی آن)/u',
                'example'     => '/(مثال|نمونه|برای مثال|برای نمونه|به عنوان مثال|به‌عنوان نمونه|از جمله|از قبیل|مانند|نظیر|همچون|به طور نمونه)/u',
                'emphasis'    => '/(مهم|اساسی|کلیدی|اصلی|حیاتی|بنیادی|ضروری|حائز اهمیت|قابل توجه|چشمگیر|بسیار مهم|نکته مهم|نکته اساسی)/u',
                'sequence'    => '/(اول|دوم|سوم|چهارم|پنجم|نخست|ابتدا|در ابتدا|در وهله نخست|سپس|پس از آن|در ادامه|در مرحله بعد|در نهایت|سرانجام|پیش از آن|بعداً|متعاقباً)/u',
                'numeric'     => '/[۰-۹٠-٩0-9]+(?:[٫.,٬][۰-۹٠-٩0-9]+)?\s*(%|٪|درصد|میلیون|میلیارد|هزار|تریلیون|نفر|متر|کیلومتر|کیلوگرم|گرم|تن|ریال|تومان|دلار|یورو|ساعت|روز|ماه|سال)/u',
                'year'        => '/(?:۱۳|۱۴|19|20)[۰-۹0-9]{2}/u',
                'conclusion'  => '/(نتیجه‌گیری|نتیجه گیری|خلاصه|جمع‌بندی|جمع بندی|در پایان|به طور کلی|حاصل آنکه|می‌توان نتیجه گرفت|برآیند|در یک کلام)/u',
                'contrast'    => '/(اما|ولی|با این حال|بااین‌حال|در مقابل|از سوی دیگر|در سوی مقابل|برخلاف|هرچند|اگرچه|با وجود این|در عوض|در نقطه مقابل)/u',
                'enumeration' => '/(انواع|اقسام|دسته‌بندی|دسته بندی|طبقه|گروه|شامل|عبارتند از|عبارت‌اند از|به دو دسته|به چند گروه|نخستین دسته|دسته نخست)/u',
            ];
        }
        return [
            'definition'  => '/(is defined as|refers to|means|definition|known as)/ui',
            'causal'      => '/(because|therefore|thus|hence|as a result|consequently|due to)/ui',
            'example'     => '/(for example|for instance|such as|e\.g\.|like)/ui',
            'emphasis'    => '/(important|essential|key|main|critical|vital|significant)/ui',
            'sequence'    => '/(first|second|third|then|next|finally|afterward)/ui',
            'numeric'     => '/\d+[\.,]?\d*\s*(%|percent|million|billion|thousand)/ui',
            'year'        => '/\b(19|20)\d{2}\b/u',
            'conclusion'  => '/(conclusion|summary|in short|overall|in summary)/ui',
            'contrast'    => '/(however|but|although|nevertheless|on the other hand)/ui',
            'enumeration' => '/(types|categories|groups|include|consist of|following)/ui',
        ];
    }

    public function score(string $sentence): float
    {
        $total = 0.0;
        foreach ($this->patterns as $key => $pat) {
            if (preg_match($pat, $sentence)) $total += $this->weights[$key] ?? 0.0;
        }
        return min(1.0, $total);
    }

    public function breakdown(string $sentence): array
    {
        $out = [];
        foreach ($this->patterns as $key => $pat) {
            $out[$key] = preg_match($pat, $sentence) ? ($this->weights[$key] ?? 0.0) : 0.0;
        }
        return $out;
    }
}

/* ═══════════════════════════════════════════════════════════
 *  9) DocTypeClassifier
 * ═══════════════════════════════════════════════════════════ */

final class DocTypeClassifier
{
    public const ACADEMIC  = 'academic';
    public const NEWS      = 'news';
    public const HOWTO     = 'howto';
    public const NARRATIVE = 'narrative';
    public const GENERAL   = 'general';

    public function detect(string $fullText, array $sentences = []): array
    {
        $lower = mb_strtolower($fullText);

        $scores = [
            self::ACADEMIC  => 0.0,
            self::NEWS      => 0.0,
            self::HOWTO     => 0.0,
            self::NARRATIVE => 0.0,
        ];

        $scores[self::ACADEMIC] += 0.20 * preg_match_all('/(چکیده|مقدمه|پیشینه پژوهش|مرور ادبیات|روش‌ها|روش تحقیق|مواد و روش‌ها|نتایج|بحث|نتیجه‌گیری|منابع|مراجع|کتابنامه)/u', $lower);
        $scores[self::ACADEMIC] += 0.15 * preg_match_all('/(یافته|فرضیه|متغیر|نمونه آماری|جامعه آماری|تحلیل آماری|آزمون|معناداری|ضریب همبستگی|رگرسیون|پرسشنامه|روایی|پایایی|چارچوب نظری)/u', $lower);
        $scores[self::ACADEMIC] += 0.10 * preg_match_all('/(پژوهش حاضر|مطالعه حاضر|روش‌شناسی|روش شناسی|داده‌های تجربی|ادبیات نظری|مدل مفهومی|آزمودنی)/u', $lower);
        $scores[self::NEWS] += 0.30 * preg_match_all('/(به گزارش|خبرگزاری|ایرنا|ایسنا|تسنیم|مهر|فارس|ایلنا|خبرنگار|منبع:|گزارشگر)/u', $lower);
        $scores[self::NEWS] += 0.15 * preg_match_all('/(دیروز|امروز|فردا|ساعتی پیش|لحظاتی پیش|در پی این حادثه|در نشست خبری|در بیانیه‌ای|اعلام کرد|اظهار داشت|به نقل از)/u', $lower);
        $scores[self::NEWS] += 0.10 * preg_match_all('/(وزیر|رئیس‌جمهور|استاندار|سخنگو|مجلس|دولت|شورای شهر|خبر فوری|آخرین خبر)/u', $lower);
        $scores[self::HOWTO] += 0.25 * preg_match_all('/(مرحله|گام|نحوه|آموزش|راهنما|روش کار|طریقه|دستورالعمل|راهنمای گام به گام|مراحل انجام)/u', $lower);
        $scores[self::HOWTO] += 0.15 * preg_match_all('/(ابتدا|سپس|در ادامه|در پایان|نکته:|برای شروع|کافی است|روی گزینه|انتخاب کنید|وارد کنید|ذخیره کنید)/u', $lower);
        $scores[self::HOWTO] += 0.10 * preg_match_all('/(پیش‌نیاز|پیش نیاز|ابزار مورد نیاز|مواد لازم|نصب کنید|فعال‌سازی|تنظیمات|عیب‌یابی)/u', $lower);
        $scores[self::NARRATIVE] += 0.30 * preg_match_all('/(روزی|یکی بود یکی نبود|داستان|افسانه|قصه|حکایت|رمان|روایت|شخصیت اصلی)/u', $lower);
        $scores[self::NARRATIVE] += 0.10 * preg_match_all('/(گفت|رفت|آمد|دید|خندید|گریست|پرسید|پاسخ داد|ناگهان|اندکی بعد|صبح روز بعد)/u', $lower);

        $lenFactor = max(1.0, mb_strlen($fullText) / 1000);
        foreach ($scores as $k => $v) $scores[$k] = $v / $lenFactor;

        arsort($scores);
        $top = array_key_first($scores);
        $topScore = $scores[$top];

        if ($topScore < 0.30) return ['type' => self::GENERAL, 'scores' => $scores, 'confidence' => 0.0];
        return ['type' => $top, 'scores' => $scores, 'confidence' => min(1.0, $topScore)];
    }
}

/* ═══════════════════════════════════════════════════════════
 *  10) NoiseFilter
 * ═══════════════════════════════════════════════════════════ */

final class NoiseFilter
{
    public function score(string $sentence, int $len): float
    {
        $noise = 0.0;
        $probe = $len > 5000 ? mb_substr($sentence, 0, 5000) : $sentence;
        if (preg_match('/(.)\1{4,}/u', $probe)) $noise += 0.30;
        if (preg_last_error() !== PREG_NO_ERROR) $noise = min($noise, 0.30);

        if (preg_match('/^[\s\-_=~*#•·─━═]+$/u', $probe)) $noise += 0.50;
        if ($len < 4) $noise += 0.30;
        if (preg_match('/^(URLPH|MKPH)\d+X$/u', trim($sentence))) $noise += 0.40;
        if (preg_match('/https?:\/\/\S{100,}/u', $sentence)) $noise += 0.20;
        if (preg_match('/^\s*[\x{1F300}-\x{1FAFF}]+\s*$/u', $sentence)) $noise += 0.40;
        $punct = preg_match_all('/[^\p{L}\p{N}\s]/u', $sentence);
        if ($punct / max(1, mb_strlen($sentence)) > 0.35) $noise += 0.25;
        return min(1.0, $noise);
    }

    public function repeatRatio(array $sentences): float
    {
        if (count($sentences) < 2) return 0.0;
        $seen = []; $dup = 0;
        foreach ($sentences as $s) {
            $n = preg_replace('/\s+/u', ' ', mb_substr(trim($s), 0, 100));
            $h = md5($n);
            if (isset($seen[$h])) $dup++; else $seen[$h] = true;
        }
        return $dup / max(1, count($sentences));
    }
}

/* ═══════════════════════════════════════════════════════════
 *  11) SignalAggregator
 * ═══════════════════════════════════════════════════════════ */

final class SignalAggregator
{
    private array $baseWeights = [
        'topic_sim'      => 0.22,
        'topic_bm25'     => 0.18,
        'term_coverage'  => 0.12,
        'keyphrase_hit'  => 0.10,
        'position'       => 0.08,
        'length'         => 0.05,
        'info_density'   => 0.06,
        'cue'            => 0.07,
        'doctype'        => 0.04,
        'novelty'        => 0.05,
        'coherence'      => 0.03,
    ];

    private array $docTypeAdjust = [
        DocTypeClassifier::ACADEMIC => [
            'topic_bm25' => +0.04, 'term_coverage' => +0.03, 'cue' => -0.02,
        ],
        DocTypeClassifier::NEWS => [
            'position' => +0.05, 'novelty' => +0.03, 'topic_sim' => -0.02,
        ],
        DocTypeClassifier::HOWTO => [
            'term_coverage' => +0.05, 'cue' => +0.03, 'position' => -0.02,
        ],
        DocTypeClassifier::NARRATIVE => [
            'novelty' => +0.05, 'position' => +0.03, 'topic_bm25' => -0.03,
        ],
    ];

    public function aggregate(array $signals, string $docType, float $noisePenalty = 0.55): float
    {
        $W = $this->baseWeights;
        if (isset($this->docTypeAdjust[$docType])) {
            foreach ($this->docTypeAdjust[$docType] as $k => $delta) {
                if (isset($W[$k])) $W[$k] = max(0.0, $W[$k] + $delta);
            }
        }
        $sum = array_sum($W);
        if ($sum <= 0) return 0.0;
        foreach ($W as $k => $v) $W[$k] = $v / $sum;

        $score = 0.0;
        foreach ($W as $k => $w) $score += $w * ($signals[$k] ?? 0.0);
        $score -= $noisePenalty * ($signals['noise'] ?? 0.0);
        return max(0.0, min(1.0, $score));
    }

    public function weights(string $docType = DocTypeClassifier::GENERAL): array
    {
        $W = $this->baseWeights;
        if (isset($this->docTypeAdjust[$docType])) {
            foreach ($this->docTypeAdjust[$docType] as $k => $d) {
                if (isset($W[$k])) $W[$k] = max(0.0, $W[$k] + $d);
            }
        }
        $sum = array_sum($W);
        if ($sum > 0) foreach ($W as $k => $v) $W[$k] = $v / $sum;
        return $W;
    }
}

/* ═══════════════════════════════════════════════════════════
 *  12) WeightEngine
 * ═══════════════════════════════════════════════════════════ */

final class WeightEngine
{
    private string $lang;
    private array  $topics       = [];
    private array  $stopwords    = [];
    private array  $df           = [];
    private int    $N            = 1;

    private Tokenizer          $tokenizer;
    private Bm25Scorer         $bm25;
    private CosineMatcher      $cosine;
    private KeyphraseMatcher   $kpMatcher;
    private PositionPrior      $position;
    private CueDetector        $cue;
    private DocTypeClassifier  $docType;
    private NoiseFilter        $noise;
    private SignalAggregator   $aggregator;

    private float $t0;

    private static array $cache = [];
    private static int   $cacheTick = 0;

    public function __construct(string $lang = 'fa', string $basePath = '')
    {
        $this->t0   = microtime(true);
        $this->lang = $lang;

        if ($basePath === '') {
            $basePath = defined('WEIGHT_LANG_PATH') ? WEIGHT_LANG_PATH : __DIR__ . '/lang/Weight';
        }

        $loaded = TopicLoader::load($lang, $basePath);
        $this->topics    = $loaded['topics'];
        $this->stopwords = $loaded['stopwords'];
        $this->df        = $loaded['df'];
        $this->N         = $loaded['N'];

        $this->tokenizer  = new Tokenizer($this->stopwords, $lang);
        $this->bm25       = new Bm25Scorer($this->df, $this->N);
        $this->cosine     = new CosineMatcher();
        $this->kpMatcher  = new KeyphraseMatcher($this->topics);
        $this->position   = new PositionPrior();
        $this->cue        = new CueDetector($lang);
        $this->docType    = new DocTypeClassifier();
        $this->noise      = new NoiseFilter();
        $this->aggregator = new SignalAggregator();
    }

    public function score(array $sentences, array $options = []): array
    {
        $n = count($sentences);
        if ($n === 0) return $this->emptyResult('empty_input');

        $sample = '';
        $step = max(1, (int)($n / 8));
        for ($i = 0; $i < $n; $i += $step) {
            $s = (string)$sentences[$i];
            $sample .= (mb_strlen($s) > 80 ? mb_substr($s, 0, 80) : $s) . "\x1F";
        }
        $cacheKey = crc32($sample) . ':' . $n . ':' . $this->lang . ':' . ($options['doc_type'] ?? '');

        $now = time();
        if (isset(self::$cache[$cacheKey])) {
            [$ts, $val] = self::$cache[$cacheKey];
            if ($now - $ts < WEIGHT_CACHE_TTL) return $val;
            unset(self::$cache[$cacheKey]);
        }

        $tokens = [];
        $lens   = [];
        for ($i = 0; $i < $n; $i++) {
            [$tk, $_] = $this->tokenizer->tokenize((string)$sentences[$i]);
            $tokens[$i] = $tk;
            $lens[$i]   = max(1, count($tk));
        }

        $fullText = implode(' ', $sentences);
        $dt = $this->docType->detect($fullText, $sentences);
        $docType = !empty($options['doc_type']) ? (string)$options['doc_type'] : $dt['type'];

        $topicScores = [];
        foreach ($this->topics as $tid => $topic) {
            $topicScores[$tid] = $this->scoreTopic($topic, $tokens, $lens, $n);
        }
        arsort($topicScores);

        $dominant = $this->selectDominant($topicScores, $n);

        $sumD = array_sum($dominant);
        if ($sumD > 0) {
            foreach ($dominant as $tid => $sc) $dominant[$tid] = $sc / $sumD;
        }

        $repeatRatio = $this->noise->repeatRatio($sentences);
        $signals = [];
        $prevTokens = [];
        for ($i = 0; $i < $n; $i++) {
            $signals[$i] = $this->buildSignals(
                (string)$sentences[$i], $tokens[$i], $lens[$i],
                $dominant, $i, $n, $docType, $prevTokens, $repeatRatio
            );
            $prevTokens[] = $tokens[$i];
        }

        $rawWeights = [];
        foreach ($signals as $i => $sig) {
            $rawWeights[$i] = $this->aggregator->aggregate($sig, $docType);
        }

        $weights = $this->normalizeStrict($rawWeights, $n);

        $out = [
            'success'         => true,
            'weights'         => $weights,
            'raw_weights'     => $rawWeights,
            'dominant_topic'  => array_key_first($dominant),
            'dominant_topics' => $dominant,
            'topic_scores'    => $topicScores,
            'signals'         => $signals,
            'doc_type'        => $docType,
            'doc_type_scores' => $dt['scores'] ?? [],
            'diagnostics'     => [
                'sentence_count' => $n,
                'topic_count'    => count($this->topics),
                'dominant_count' => count($dominant),
                'repeat_ratio'   => round($repeatRatio, 3),
                'time'           => round(microtime(true) - $this->t0, 4),
            ],
        ];

        self::$cache[$cacheKey] = [$now, $out];
        if (++self::$cacheTick % 32 === 0 && count(self::$cache) > WEIGHT_CACHE_SIZE) {
            foreach (self::$cache as $k => $v) {
                if ($now - $v[0] >= WEIGHT_CACHE_TTL) unset(self::$cache[$k]);
            }
            if (count(self::$cache) > WEIGHT_CACHE_SIZE) {
                self::$cache = array_slice(self::$cache, -WEIGHT_CACHE_SIZE, null, true);
            }
        }
        return $out;
    }

    public function scoreText(string $text, array $options = []): array
    {
        return $this->score($this->splitSentences($text), $options);
    }

    public function scoreSentence(string $sentence): array
    {
        $r = $this->score([$sentence]);
        return [
            'weight'  => $r['weights'][0] ?? 0.0,
            'signals' => $r['signals'][0] ?? [],
            'topics'  => $r['dominant_topics'] ?? [],
        ];
    }

    public function detectTopics(string $text, int $top = 5): array
    {
        $r = $this->scoreText($text);
        $scores = $r['topic_scores'];
        arsort($scores);
        $out = []; $i = 0;
        foreach ($scores as $tid => $sc) {
            if ($i++ >= $top) break;
            $out[] = ['id' => $tid, 'name' => $this->topics[$tid]->name ?? $tid, 'score' => round($sc, 4)];
        }
        return $out;
    }

    public function listTopics(): array
    {
        $out = [];
        foreach ($this->topics as $tid => $t) {
            $out[$tid] = [
                'name'       => $t->name,
                'terms'      => count($t->terms),
                'keyphrases' => count($t->keyphrases),
                'synonyms'   => count($t->synonyms),
                'negative'   => count($t->negative),
            ];
        }
        return $out;
    }

    public function weightsMatrix(): array
    {
        return $this->aggregator->weights();
    }

    private function emptyResult(string $reason): array
    {
        return [
            'success' => true, 'weights' => [], 'raw_weights' => [],
            'dominant_topic' => null, 'dominant_topics' => [],
            'topic_scores' => [], 'signals' => [],
            'doc_type' => DocTypeClassifier::GENERAL,
            'diagnostics' => ['reason' => $reason],
        ];
    }

    /**
     * انتخاب موضوعات غالب با آستانه تطبیقی.
     * برای متن‌های کوچک یا امتیازهای پایین، سخت‌گیری را کاهش می‌دهد
     * و در نهایت fallback به بهترین موضوع می‌کند تا dominant هرگز خالی نماند.
     */
    private function selectDominant(array $topicScores, int $n = 0): array
    {
        if (empty($topicScores)) return [];

        $sorted = $topicScores;
        arsort($sorted);

        $topScore = reset($sorted);
        if ($topScore <= 0) return [];

        // آستانه تطبیقی بر اساس طول متن و امتیاز بالا
        if ($n <= 5 || $topScore < 0.05) {
            $minAbsolute = 0.001;
        } elseif ($n <= 15 || $topScore < 0.15) {
            $minAbsolute = 0.008;
        } else {
            $minAbsolute = 0.015;
        }

        $minRelative = $topScore * 0.40;

        $dominant = [];
        foreach ($sorted as $tid => $sc) {
            if ($sc < $minRelative || $sc < $minAbsolute) break;
            $dominant[$tid] = $sc;
            if (count($dominant) >= 3) break;
        }

        // ★ fallback: dominant هرگز خالی نماند وقتی موضوعی امتیاز مثبت دارد
        if (empty($dominant)) {
            $dominant[array_key_first($sorted)] = $topScore;
        }

        return $dominant;
    }

    private function scoreTopic(WeightTopic $topic, array $tokens, array $lens, int $n): float
    {
        $bm25Total = 0.0;
        $cosTotal  = 0.0;
        $covTotal  = 0.0;
        $kpTotal   = 0.0;
        $synTotal  = 0.0;
        $negTotal  = 0.0;
        $matchedSentences = 0;

        $topicVec = $topic->terms;
        $synCount = count($topic->synonyms);
        $negCount = count($topic->negative);

        for ($i = 0; $i < $n; $i++) {
            if (empty($tokens[$i])) continue;

            $bm25 = $this->bm25->score($tokens[$i], $topic->terms, $topic->bm25K1, $topic->bm25B, $topic->avgLen);

            $tf = array_count_values($tokens[$i]);
            $cos = $this->cosine->similarity($tf, $topicVec);

            $sentLower = mb_strtolower(implode(' ', $tokens[$i]));
            $cov = $this->kpMatcher->coverage($sentLower, $topic->id);
            $kp  = $this->kpMatcher->hitScore($sentLower, $topic->id, 3);

            $synHit = 0;
            if ($synCount > 0) {
                $tokFlip = array_flip($tokens[$i]);
                foreach ($topic->synonyms as $syn) {
                    if (isset($tokFlip[$syn])) $synHit++;
                }
            }
            $syn = $synCount > 0 ? $synHit / $synCount : 0.0;

            $negHit = 0;
            if ($negCount > 0) {
                $tokFlip = $tokFlip ?? array_flip($tokens[$i]);
                foreach ($topic->negative as $neg) {
                    if (isset($tokFlip[$neg])) $negHit++;
                }
            }
            $neg = $negCount > 0 ? $negHit / $negCount : 0.0;

            $hasMatch = ($bm25 > 0.05 || $cos > 0.10 || $cov > 0 || $kp > 0 || $syn > 0);
            if (!$hasMatch) continue;

            $bm25Total += $bm25;
            $cosTotal  += $cos;
            $covTotal  += $cov;
            $kpTotal   += $kp;
            $synTotal  += $syn;
            $negTotal  += $neg;
            $matchedSentences++;
        }

        if ($matchedSentences === 0) return 0.0;

        $bm25 = $bm25Total / $matchedSentences;
        $cos  = $cosTotal  / $matchedSentences;
        $cov  = $covTotal  / $matchedSentences;
        $kp   = $kpTotal   / $matchedSentences;
        $syn  = $synTotal  / $matchedSentences;
        $neg  = $negTotal  / $matchedSentences;

        $coverageRatio = $matchedSentences / max(1, $n);

        $score = 0.0;
        $score += 0.40 * $bm25;
        $score += 0.25 * $cos;
        $score += 0.20 * $cov;
        $score += 0.10 * $kp;
        $score += 0.05 * $syn;

        $score *= (0.4 + 0.6 * $coverageRatio);
        $score -= 0.60 * $neg;
        $score *= max(0.1, $topic->priorWeight);

        return max(0.0, $score);
    }

    private function buildSignals(
        string $sent, array $tokens, int $len,
        array $dominant, int $idx, int $n, string $docType,
        array $prevTokens, float $repeatRatio
    ): array {
        $sig = [
            'topic_sim' => 0.0, 'topic_bm25' => 0.0, 'term_coverage' => 0.0,
            'keyphrase_hit' => 0.0, 'position' => 0.0, 'length' => 0.0,
            'info_density' => 0.0, 'cue' => 0.0, 'doctype' => 0.0,
            'noise' => 0.0, 'novelty' => 0.0, 'coherence' => 0.0,
        ];

        if ($len === 0) return $sig;

        $simMax = 0.0; $simSum = 0.0; $wSum = 0.0;
        foreach ($dominant as $tid => $tw) {
            $topic = $this->topics[$tid] ?? null;
            if (!$topic) continue;
            $maxTerm = 0.0; $sumTerm = 0.0;
            foreach ($tokens as $tok) {
                if (isset($topic->terms[$tok])) {
                    $w = (float)$topic->terms[$tok];
                    if ($w > $maxTerm) $maxTerm = $w;
                    $sumTerm += $w;
                }
            }
            $local = $maxTerm * 0.7 + ($sumTerm / max(1, $len)) * 0.3;
            if ($local > $simMax) $simMax = $local;
            $simSum += $local * $tw;
            $wSum   += $tw;
        }
        $ts = $wSum > 0 ? max($simMax, $simSum / $wSum) : 0.0;
        $sig['topic_sim'] = max(0.0, min(1.0, $ts));

        $bm25Sum = 0.0;
        foreach ($dominant as $tid => $tw) {
            $topic = $this->topics[$tid] ?? null;
            if (!$topic) continue;
            $bm25Sum += $tw * $this->bm25->score($tokens, $topic->terms, $topic->bm25K1, $topic->bm25B, $topic->avgLen);
        }
        $sig['topic_bm25'] = min(1.0, max(0.0, $bm25Sum));

        $covSum = 0.0; $kpSum = 0.0;
        $sLower = mb_strtolower($sent);
        foreach ($dominant as $tid => $tw) {
            $covSum += $tw * $this->kpMatcher->coverage($sLower, $tid);
            $kpSum  += $tw * $this->kpMatcher->hitScore($sLower, $tid, 3);
        }
        $sig['term_coverage'] = min(1.0, max(0.0, $covSum));
        $sig['keyphrase_hit'] = min(1.0, max(0.0, $kpSum));

        $sig['position'] = $this->position->score($idx, $n);

        $ideal = 22.0;
        $dv = abs($len - $ideal) / $ideal;
        $sig['length'] = max(0.2, exp(-$dv * $dv * 1.1));

        $content = 0;
        foreach ($tokens as $tok) {
            if (!isset($this->stopwords[$tok]) && mb_strlen($tok) >= 3) $content++;
        }
        $sig['info_density'] = $content / max(1, $len);

        $sig['cue'] = $this->cue->score($sent);

        $sig['doctype'] = $this->docTypeBoost($docType, $sent, $idx, $n);

        $sig['noise'] = $this->noise->score($sent, $len);
        if ($repeatRatio > 0.3) $sig['noise'] = min(1.0, $sig['noise'] + 0.1);

        $maxJ = 0.0;
        $start = max(0, count($prevTokens) - 5);
        for ($j = $start; $j < count($prevTokens); $j++) {
            $jc = $this->cosine->jaccard($tokens, $prevTokens[$j]);
            if ($jc > $maxJ) $maxJ = $jc;
        }
        $sig['novelty'] = max(0.0, 1.0 - $maxJ);

        $coherent = 0;
        foreach ($tokens as $tok) {
            foreach ($dominant as $tid => $_) {
                if (isset($this->topics[$tid]->terms[$tok])) { $coherent++; break; }
            }
        }
        $sig['coherence'] = $coherent / max(1, $len);

        return $sig;
    }

    private function docTypeBoost(string $docType, string $sent, int $idx, int $n): float
    {
        $b = 0.0;
        switch ($docType) {
            case DocTypeClassifier::ACADEMIC:
                if ($idx < 2) $b += 0.4;
                if ($idx >= $n - 2) $b += 0.25;
                if (preg_match('/(چکیده|نتیجه‌گیری|یافته|روش|بحث)/u', $sent)) $b += 0.3;
                break;
            case DocTypeClassifier::NEWS:
                if ($idx === 0) $b += 0.5;
                if (preg_match('/(گزارش|خبرگزاری|منبع|به نقل)/u', $sent)) $b += 0.2;
                break;
            case DocTypeClassifier::HOWTO:
                if (preg_match('/(مرحله|گام|اول|دوم|سوم|سپس)/u', $sent)) $b += 0.4;
                break;
            case DocTypeClassifier::NARRATIVE:
                if ($idx === 0) $b += 0.3;
                if (preg_match('/(گفت|رفت|آمد|دید|کرد|شد)/u', $sent)) $b += 0.2;
                break;
        }
        return min(1.0, $b);
    }

    /**
     * نرمال‌سازی با تشخیص رژیم توزیع:
     *  - توزیع پهن (range >= 0.05): نرمال percentile (رفتار سخت‌گیرانه قبلی)
     *  - توزیع باریک (range < 0.05): نرمال نسبی به max، بدون تقویت نویز
     * این باعث می‌شود هم متن سخت و هم متن ساده خروجی درست بگیرند.
     */
    private function normalizeStrict(array $weights, int $n = 0): array
    {
        if (empty($weights)) return [];

        $sorted = array_values($weights);
        sort($sorted);
        $cnt = count($sorted);

        $p90i = (int)floor(($cnt - 1) * 0.9);
        $p10i = (int)floor(($cnt - 1) * 0.1);
        $p90 = $sorted[max(0, min($cnt - 1, $p90i))];
        $p10 = $sorted[max(0, min($cnt - 1, $p10i))];
        $range = $p90 - $p10;

        // ★ رژیم توزیع باریک: نرمال نسبی به max (بدون تقویت نویز)
        if ($range < 0.05) {
            $max = max($weights);
            if ($max <= 0) {
                return array_fill_keys(array_keys($weights), 0.0);
            }
            $out = [];
            foreach ($weights as $i => $w) {
                $out[$i] = $w / $max;
            }
            return $out;
        }

        // رژیم توزیع پهن: نرمال percentile سخت‌گیرانه
        $out = [];
        foreach ($weights as $i => $w) {
            if ($w < $p10) { $out[$i] = 0.0; continue; }
            $v = ($w - $p10) / $range;
            $out[$i] = max(0.0, min(1.0, $v));
        }
        return $out;
    }

    private function splitSentences(string $text): array
    {
        $text = trim($text);
        if ($text === '') return [];
        $text = preg_replace('/(?<=[.!?؟。！？…])\s+/u', "\x1F", $text);
        $parts = explode("\x1F", $text);
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if (mb_strlen($p) >= 10) $out[] = $p;
        }
        return $out;
    }
}
