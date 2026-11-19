<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\components;

use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\helpers\FileHelper;
use yii\web\Response;
use yii\web\View;

/**
 * Шрифты Google со своего сервера.
 *
 * Сайт держит копию CSS и файлов шрифтов в `<assets>/sx-fonts/<ключ>/` и
 * подключает её прямым путём — файлы раздаёт веб-сервер, PHP в загрузке
 * шрифтов не участвует. После очистки assets копия скачивается заново.
 *
 * Пока копии нет (и при любом внутреннем сбое), страница подключает шрифты
 * Google как раньше: сайт всегда выглядит собранным (решение владельца
 * 19.11.2026). Скачивание копии — задание cms-job `cms.local-fonts`
 * (jobs\LocalFontsJobHandler, очередь maintenance; cms-job и её обработчики
 * есть во всех проектах). Когда копия готова, страницы подключают её.
 *
 * Подменяются только места, откуда браузер загружает стили: теги <link> и
 * блоки <style> (@import), в HTML ответа (data и content: PJAX, PageCache).
 * Содержимое <textarea>, <script> и HTML-комментариев не меняется. Если сжатие
 * ассетов само скачивает внешний CSS (cssFileRemouteCompile), ссылка на Google
 * в $view->cssFiles заранее заменяется готовой копией.
 *
 * Работает, когда включены `enabled` (конфиг) и настройка сайта «Шрифты Google
 * со своего сервера» в «Правовой информации» (по умолчанию включена).
 */
class LocalFonts extends Component implements BootstrapInterface
{
    const CSS_FILE = 'fonts.css';
    const JOB_TYPE = 'cms.local-fonts';
    const URL_PATTERN = '~(?:https?:)?//fonts\.googleapis\.com/css2?\?[^"\'\s<>()]+~i';

    /**
     * @var bool заменять ссылки на шрифты Google локальными
     */
    public $enabled = true;

    /**
     * @var string папка копий внутри assets
     */
    public $dirName = 'sx-fonts';

    /**
     * @var string браузер, для которого Google отдаёт woff2
     */
    public $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

    /**
     * @var int ограничения на одно подключение шрифтов
     */
    public $maxFiles = 300;
    public $maxCssBytes = 1048576;
    public $maxFileBytes = 6291456;
    public $maxTotalBytes = 52428800;
    public $timeout = 20;

    /**
     * @var int пауза после сбоя скачивания или постановки задания, секунд;
     * столько же действует отметка «задание поставлено»
     */
    public $retryAfter = 600;

    /**
     * @var int общий предел времени одной сборки, секунд
     */
    public $buildTimeLimit = 30;

    /**
     * @var int сколько секунд ждать, если копию собирает другой процесс
     */
    public $lockWait = 20;

    /**
     * @var int сколько заданий может поставить один запрос
     */
    public $maxBuildsPerRequest = 3;

    /**
     * @var array адреса, для которых в этом запросе поставлено задание
     */
    private $_queued = [];

    /**
     * @var array|null [папка, адрес] хранилища копий
     */
    private $_storage;

    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        if (!$app instanceof \yii\web\Application) {
            return;
        }

        //Раньше обработчика сжатия ассетов (он тоже слушает EVENT_END_PAGE).
        $app->view->on(View::EVENT_END_PAGE, function (Event $event) {
            //Флажок сайта читается только когда есть что подменять (см. rewriteCssFiles).
            if ($event->sender instanceof View) {
                $this->rewriteCssFiles($event->sender);
            }
        }, null, false);

        $app->response->on(Response::EVENT_BEFORE_SEND, function (Event $event) {
            /** @var Response $response */
            $response = $event->sender;
            if ($response->format !== Response::FORMAT_HTML || $response->stream !== null) {
                return;
            }
            if (is_string($response->data)) {
                if (stripos($response->data, 'fonts.googleapis.com') !== false && $this->isActive()) {
                    $response->data = $this->rewriteHtml($response->data);
                }
            } elseif ($response->data === null && is_string($response->content)) {
                //PJAX и PageCache передают готовый HTML через content.
                if (stripos($response->content, 'fonts.googleapis.com') !== false && $this->isActive()) {
                    $response->content = $this->rewriteHtml($response->content);
                }
            }
        });
    }

    /**
     * Включена ли подмена: конфиг (enabled) и настройка сайта «Шрифты Google со
     * своего сервера» в «Правовой информации» (по умолчанию включена).
     * @return bool
     */
    public function isActive()
    {
        if (!$this->enabled) {
            return false;
        }
        try {
            if (\Yii::$app->has('legal') && isset(\Yii::$app->legal->is_local_google_fonts)) {
                return (bool) \Yii::$app->legal->is_local_google_fonts;
            }
        } catch (\Throwable $e) {
            \Yii::warning("LocalFonts: ".$e->getMessage(), self::class);
        }
        return true;
    }

    /**
     * Только когда сжатие ассетов само скачивает и склеивает внешний CSS:
     * ссылка на Google в $view->cssFiles заменяется готовой копией (её склеят).
     * Пока копии нет, ссылка остаётся как есть. Без удалённой склейки шрифты,
     * как и раньше, остаются отдельной ссылкой.
     * @param View $view
     */
    public function rewriteCssFiles(View $view)
    {
        if (!$view->cssFiles || !$this->isRemoteCssCompile()) {
            return;
        }
        $hasGoogle = false;
        foreach (array_keys($view->cssFiles) as $key) {
            if (is_string($key) && stripos($key, 'fonts.googleapis.com') !== false) {
                $hasGoogle = true;
                break;
            }
        }
        if (!$hasGoogle || !$this->isActive()) {
            return;
        }

        $result = [];
        foreach ($view->cssFiles as $key => $tag) {
            if (!is_string($key) || !preg_match(self::URL_PATTERN, $key)) {
                $result[$key] = $tag;
                continue;
            }
            try {
                $local = $this->localUrl($key);
            } catch (\Throwable $e) {
                \Yii::warning("LocalFonts: ".$e->getMessage(), self::class);
                $local = null;
            }
            if ($local !== null) {
                $result[$local] = $this->rewriteHtml((string) $tag);
            } else {
                $result[$key] = $tag;
            }
        }
        $view->cssFiles = $result;
    }

    /**
     * Заменяет ссылки на шрифты Google только там, откуда браузер грузит стили:
     * значение атрибута href у настоящего тега <link> и адрес @import внутри
     * блока <style>. Всё остальное — как есть: HTML-комментарии, текстовые
     * блоки <title>, <textarea>, <script> (и незакрытые — до конца документа),
     * открывающие теги остальных элементов вместе с атрибутами (data-template
     * и т. п.), другие атрибуты самого <link>. Имя тега сравнивается точно
     * (<link-preview> — не <link>). При ошибке регулярного выражения
     * возвращается исходный HTML.
     *
     * @param string $html
     * @return string
     */
    public function rewriteHtml($html)
    {
        $html = (string) $html;
        //Атрибуты тега — с учётом кавычек (">" внутри значения не закрывает тег).
        $attrs = '(?:[^>"\']|"[^"]*"|\'[^\']*\')*';
        $end = '(?=[\s/>])';
        $text = function ($name) use ($attrs, $end) {
            return '<'.$name.$end.$attrs.'>.*?(?:</'.$name.'\s*>|\z)';
        };
        $result = preg_replace_callback(
            '~<!--.*?(?:-->|\z)'
            .'|'.$text('title').'|'.$text('textarea').'|'.$text('script').'|'.$text('style')
            .'|<[a-z][\w:.-]*'.$attrs.'>~is',
            function ($tag) use ($end) {
                $chunk = $tag[0];
                if (stripos($chunk, 'fonts.googleapis.com') === false) {
                    return $chunk;
                }
                if (preg_match('~^<link'.$end.'~i', $chunk)) {
                    $replaced = $this->rewriteLinkHref($chunk);
                } elseif (preg_match('~^<style'.$end.'~i', $chunk)) {
                    $replaced = $this->rewriteStyleImports($chunk);
                } else {
                    return $chunk;
                }
                return $replaced === null ? $chunk : $replaced;
            },
            $html
        );

        if ($result === null) {
            \Yii::warning("LocalFonts: ошибка регулярного выражения (".preg_last_error()."), HTML оставлен без изменений", self::class);
            return $html;
        }
        return $result;
    }

    /**
     * Меняет значение href тега <link>, если это CSS шрифтов Google.
     * @param string $tag
     * @return string|null
     */
    protected function rewriteLinkHref($tag)
    {
        //Атрибуты разбираются по порядку: каждое совпадение съедает имя и значение
        //целиком вместе с кавычками, поэтому текст внутри другого атрибута
        //(data-template='<link href=…>') не принимается за href.
        $start = 5; //после «<link»
        $body = substr($tag, $start);
        if (!preg_match_all('~([^\s"\'>/=]+)(?:\s*=\s*("[^"]*"|\'[^\']*\'|[^\s"\'>]+))?~', $body, $all, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $tag;
        }
        foreach ($all as $attr) {
            if (strtolower($attr[1][0]) !== 'href') {
                continue;
            }
            //Первый href решает (как и в браузере).
            if (!isset($attr[2]) || $attr[2][1] < 0) {
                return $tag;
            }
            $raw = $attr[2][0];
            $quote = ($raw[0] === '"' || $raw[0] === "'") ? $raw[0] : '';
            $value = $quote ? substr($raw, 1, -1) : $raw;
            if (!preg_match('~^\s*(?:https?:)?//fonts\.googleapis\.com/css2?\?[^\s"\'<>]*\s*$~i', $value)) {
                return $tag;
            }
            $local = $this->safeLocalUrl(trim($value));
            if ($local === null) {
                return $tag;
            }
            $offset = $start + $attr[2][1];
            return substr($tag, 0, $offset).$quote.$local.$quote.substr($tag, $offset + strlen($raw));
        }
        return $tag;
    }

    /**
     * Меняет адреса @import шрифтов Google внутри блока <style>.
     * @param string $block
     * @return string|null
     */
    protected function rewriteStyleImports($block)
    {
        //Открывающий тег (с атрибутами) не трогаем — только тело CSS.
        $attrs = '(?:[^>"\']|"[^"]*"|\'[^\']*\')*';
        if (!preg_match('~^(<style'.$attrs.'>)(.*?)(</style\s*>)?$~is', $block, $parts)) {
            return $block;
        }
        $open = $parts[1];
        $css = $parts[2];
        $close = $parts[3] ?? '';

        //Тело CSS по порядку: комментарии и строки съедаются целиком и остаются
        //как есть, меняется только адрес настоящей директивы @import.
        $google = '(?:https?:)?//fonts\.googleapis\.com/css2?\?[^"\'\s<>()]+';
        $css = preg_replace_callback(
            '~/\*.*?(?:\*/|\z)'
            .'|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\''
            .'|@import\s+(?:url\(\s*(["\']?)('.$google.')\1\s*\)|(["\'])('.$google.')\3)~is',
            function ($m) {
                if (stripos($m[0], '@import') !== 0) {
                    return $m[0];
                }
                $url = (isset($m[2]) && $m[2] !== '') ? $m[2] : ($m[4] ?? '');
                $local = $url !== '' ? $this->safeLocalUrl($url) : null;
                return $local === null ? $m[0] : str_replace($url, $local, $m[0]);
            },
            $css
        );
        if ($css === null) {
            return $block;
        }
        return $open.$css.$close;
    }

    /**
     * localUrl() без исключений: при сбое — null (остаются шрифты Google).
     * @param string $googleUrl
     * @return string|null
     */
    protected function safeLocalUrl($googleUrl)
    {
        try {
            return $this->localUrl($googleUrl);
        } catch (\Throwable $e) {
            \Yii::warning("LocalFonts: ".$e->getMessage(), self::class);
            return null;
        }
    }

    /**
     * Адрес готовой копии. Если копии нет — ставит задание на скачивание и
     * возвращает null: до готовности копии подключаются шрифты Google.
     *
     * @param string $googleUrl адрес как в HTML
     * @return string|null
     */
    public function localUrl($googleUrl)
    {
        $url = $this->normalize($googleUrl);
        if (!$this->isGoogleFontsUrl($url)) {
            return null;
        }
        if ($this->isBuilt($url)) {
            return $this->staticUrl(md5($url));
        }
        $this->queue($url);
        return null;
    }

    /**
     * @param string $url
     * @return string
     */
    public function normalize($url)
    {
        $url = html_entity_decode(trim((string) $url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($url, '//') === 0) {
            $url = 'https:'.$url;
        }
        return preg_replace('~^http:~i', 'https:', $url);
    }

    /**
     * Проверяет, что адрес — CSS шрифтов Google.
     * @param string $url
     * @return bool
     */
    public function isGoogleFontsUrl($url)
    {
        $parts = parse_url($url);
        return $parts
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 'fonts.googleapis.com'
            && !isset($parts['user']) && !isset($parts['port'])
            && in_array(($parts['path'] ?? ''), ['/css', '/css2'], true)
            && !empty($parts['query']);
    }

    /**
     * Папка копий.
     * @return string
     */
    public function baseDir()
    {
        return $this->storage()[0];
    }

    /**
     * @param string $key
     * @return string
     */
    public function dir($key)
    {
        return $this->baseDir().DIRECTORY_SEPARATOR.$key;
    }

    /**
     * Прямой адрес файла копии (раздаёт веб-сервер).
     * @param string $key
     * @return string
     */
    public function staticUrl($key)
    {
        $file = $this->dir($key).DIRECTORY_SEPARATOR.self::CSS_FILE;
        return $this->storage()[1].'/'.$key.'/'.self::CSS_FILE.'?v='.@filemtime($file);
    }

    /**
     * Есть ли готовая локальная копия.
     * @param string $url нормализованный адрес
     * @return bool
     */
    public function isBuilt($url)
    {
        return is_file($this->dir(md5($url)).DIRECTORY_SEPARATOR.self::CSS_FILE);
    }

    /**
     * Ставит задание cms-job `cms.local-fonts` на скачивание копии.
     *
     * Не чаще раза в retryAfter на адрес (отметка `<ключ>.queued` ставится до
     * постановки, поэтому и сбой очереди не повторяется на каждой странице),
     * не во время паузы после сбоя скачивания, не больше maxBuildsPerRequest за
     * запрос. Дубли в очереди отсекает dedupKey типа (адрес + папка сайта).
     *
     * @param string $url нормализованный адрес
     */
    public function queue($url)
    {
        if (isset($this->_queued[$url]) || count($this->_queued) >= $this->maxBuildsPerRequest) {
            return;
        }
        $key = md5($url);
        $dir = $this->baseDir();
        $failMark = $dir.DIRECTORY_SEPARATOR.$key.'.failed';
        $queuedMark = $dir.DIRECTORY_SEPARATOR.$key.'.queued';
        if ($this->isFresh($failMark) || $this->isFresh($queuedMark)) {
            return;
        }
        if (!$this->canUseJobs()) {
            return;
        }

        $this->_queued[$url] = true;
        try {
            if (!$this->reserve($queuedMark)) {
                return;
            }
            \Yii::$app->jobs->push(self::JOB_TYPE, ['url' => $url, 'dir' => $dir]);
        } catch (\Throwable $e) {
            \Yii::warning("LocalFonts: не удалось поставить задание: ".$e->getMessage(), self::class);
        }
    }

    /**
     * Атомарно занимает право поставить задание: неблокирующая блокировка
     * отметки, повторная проверка её свежести и обновление времени. Ставит
     * задание только тот запрос, который обновил отметку.
     *
     * @param string $mark
     * @return bool
     */
    protected function reserve($mark)
    {
        FileHelper::createDirectory(dirname($mark));
        $handle = @fopen($mark, 'c');
        if (!$handle) {
            return false;
        }
        try {
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                return false; //другой запрос ставит задание прямо сейчас
            }
            clearstatcache(true, $mark);
            if (filesize($mark) > 0 && filemtime($mark) > time() - $this->retryAfter) {
                return false;
            }
            //Время постановки — в содержимом и в mtime; без записи паузы не будет, задание не ставим.
            if (!ftruncate($handle, 0) || fwrite($handle, (string) time()) === false || !fflush($handle) || !@touch($mark)) {
                return false;
            }
            return true;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return string[] адреса, для которых в этом запросе поставлено задание
     */
    public function getQueued()
    {
        return array_keys($this->_queued);
    }

    /**
     * Зарегистрирован ли тип задания cms-job.
     * @return bool
     */
    public function canUseJobs()
    {
        try {
            return \Yii::$app->has('jobs')
                && method_exists(\Yii::$app->jobs, 'getRegistry')
                && \Yii::$app->jobs->getRegistry()->has(self::JOB_TYPE);
        } catch (\Throwable $e) {
            \Yii::warning("LocalFonts: cms-job недоступен: ".$e->getMessage(), self::class);
            return false;
        }
    }

    /**
     * Папка копий, заданная явно (задание cms-job в консоли, где нет assets сайта).
     * @param string $dir
     */
    public function setStorageDir($dir)
    {
        $this->_storage = [rtrim((string) $dir, '/\\'), ''];
    }

    /**
     * Скачивает CSS и файлы шрифтов в папку сайта.
     *
     * Ошибки скачивания и записи не бросает (false + отметка `.failed`).
     * Исключение из $progress (например, отмена задания) прерывает сборку и
     * пробрасывается дальше; временная папка удаляется, копия не публикуется.
     *
     * @param string $url нормализованный адрес
     * @param bool $force не учитывать паузу после сбоя (задание cms-job)
     * @param callable|null $progress вызывается между шагами: продление аренды, проверка отмены
     * @param callable|null $beforePublish вызывается прямо перед публикацией копии
     *                                     (свежая проверка отмены); исключение отменяет публикацию
     * @return bool
     */
    public function build($url, $force = false, callable $progress = null, callable $beforePublish = null)
    {
        if (!$this->isGoogleFontsUrl($url)) {
            return false;
        }

        $key = md5($url);
        $lock = null;
        $tmp = null;
        $interrupt = null;
        $tick = function (callable $callback = null) use ($progress, &$interrupt) {
            $callback = $callback ?: $progress;
            if ($callback) {
                try {
                    $callback();
                } catch (\Throwable $e) {
                    $interrupt = $e;
                    throw $e;
                }
            }
        };

        try {
            if ($this->isBuilt($url)) {
                return true;
            }
            FileHelper::createDirectory($this->baseDir());
            $failMark = $this->baseDir().DIRECTORY_SEPARATOR.$key.'.failed';
            if (!$force && $this->isFresh($failMark)) {
                return false;
            }

            //Одна сборка на ключ; ждём другой процесс не дольше lockWait.
            $lockFile = $this->baseDir().DIRECTORY_SEPARATOR.$key.'.lock';
            $lock = fopen($lockFile, 'c');
            if (!$lock) {
                throw new \RuntimeException("не удалось открыть блокировку {$key}");
            }
            @chmod($lockFile, 0666);
            $waitUntil = microtime(true) + $this->lockWait;
            while (!flock($lock, LOCK_EX | LOCK_NB)) {
                if (microtime(true) >= $waitUntil) {
                    //Занятость — не сбой: отметку .failed не ставим.
                    fclose($lock);
                    $lock = null;
                    return $this->isBuilt($url);
                }
                $tick();
                usleep(250000);
            }
            if ($this->isBuilt($url)) {
                return true;
            }
            clearstatcache(true, $failMark);
            if (!$force && $this->isFresh($failMark)) {
                return false;
            }

            //Общий предел времени сборки (отсчёт после получения блокировки).
            $deadline = microtime(true) + $this->buildTimeLimit;

            $tick();
            $css = $this->download($url, $this->maxCssBytes, $deadline);
            if ($css === null || stripos($css, '@font-face') === false) {
                throw new \RuntimeException("не удалось скачать {$url}");
            }

            preg_match_all('~url\(\s*[\'"]?(https://fonts\.gstatic\.com/[^\'")\s]+)[\'"]?\s*\)~i', $css, $m);
            $fontUrls = array_values(array_unique($m[1]));
            if (!$fontUrls || count($fontUrls) > $this->maxFiles) {
                throw new \RuntimeException("неожиданный состав шрифтов в {$url}");
            }

            $tmp = $this->dir($key).'.tmp'.getmypid().mt_rand(1000, 9999);
            FileHelper::createDirectory($tmp);

            $total = 0;
            $replaces = [];
            foreach ($fontUrls as $fontUrl) {
                $tick();
                $path = (string) parse_url($fontUrl, PHP_URL_PATH);
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, ['woff2', 'woff', 'ttf', 'otf', 'eot', 'svg'], true)) {
                    $ext = 'woff2';
                }
                $name = substr(md5($fontUrl), 0, 16).'.'.$ext;

                $data = $this->download($fontUrl, $this->maxFileBytes, $deadline);
                if ($data === null) {
                    throw new \RuntimeException("не удалось скачать {$fontUrl}");
                }
                $total += strlen($data);
                if ($total > $this->maxTotalBytes) {
                    throw new \RuntimeException("слишком большой набор шрифтов {$url}");
                }
                $this->writeFile($tmp.DIRECTORY_SEPARATOR.$name, $data);
                $replaces[$fontUrl] = $name;
            }

            $css = strtr($css, $replaces);
            //В копии не должно остаться внешних адресов.
            if (preg_match('~url\(\s*[\'"]?(?:https?:)?//|@import~i', $css)) {
                throw new \RuntimeException("в CSS остались внешние ресурсы {$url}");
            }
            $this->writeFile($tmp.DIRECTORY_SEPARATOR.self::CSS_FILE, "/* Локальная копия {$url} */\n".$css);

            //Последняя, свежая проверка отмены перед публикацией.
            $tick();
            if ($beforePublish) {
                $tick($beforePublish);
            }
            if (!@rename($tmp, $this->dir($key))) {
                throw new \RuntimeException("не удалось опубликовать копию {$key}");
            }
            $tmp = null;
            @unlink($failMark);
            return true;
        } catch (\Throwable $e) {
            if ($interrupt !== null && $e === $interrupt) {
                throw $e;
            }
            \Yii::warning("LocalFonts: ".$e->getMessage(), self::class);
            if (isset($failMark) && is_dir(dirname($failMark))) {
                @touch($failMark);
            }
            return false;
        } finally {
            if ($tmp !== null && is_dir($tmp)) {
                try {
                    FileHelper::removeDirectory($tmp);
                } catch (\Throwable $e) {
                    \Yii::warning("LocalFonts: не удалось удалить {$tmp}: ".$e->getMessage(), self::class);
                }
            }
            if ($lock) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    /**
     * Есть ли отметка, поставленная меньше retryAfter секунд назад.
     * @param string $file
     * @return bool
     */
    protected function isFresh($file)
    {
        return is_file($file) && filemtime($file) > time() - $this->retryAfter;
    }

    /**
     * [папка, адрес] хранилища копий: `<assets>/sx-fonts`.
     * @return array
     */
    protected function storage()
    {
        if ($this->_storage === null) {
            $this->_storage = [
                \Yii::$app->assetManager->basePath.DIRECTORY_SEPARATOR.$this->dirName,
                \Yii::$app->assetManager->baseUrl.'/'.$this->dirName,
            ];
        }
        return $this->_storage;
    }

    /**
     * @param string $file
     * @param string $data
     */
    protected function writeFile($file, $data)
    {
        if (file_put_contents($file, $data) !== strlen($data)) {
            throw new \RuntimeException("не удалось записать {$file}");
        }
    }

    /**
     * Скачивание без переходов по редиректам, с ограничением размера во время загрузки.
     * @param string $url
     * @param int $maxBytes
     * @param float|null $deadline момент (microtime), после которого скачивать нельзя
     * @return string|null
     */
    protected function download($url, $maxBytes, $deadline = null)
    {
        $data = '';

        $timeout = $this->timeout;
        if ($deadline !== null) {
            $left = (int) floor($deadline - microtime(true));
            if ($left < 1) {
                throw new \RuntimeException("истёк общий предел времени сборки ({$this->buildTimeLimit} c)");
            }
            $timeout = min($timeout, $left);
        }

        if (function_exists('curl_init')) {
            $tooBig = false;
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT        => $timeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                CURLOPT_USERAGENT      => $this->userAgent,
                CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
                CURLOPT_WRITEFUNCTION  => function ($ch, $chunk) use (&$data, &$tooBig, $maxBytes) {
                    $data .= $chunk;
                    if (strlen($data) > $maxBytes) {
                        $tooBig = true;
                        return 0;
                    }
                    return strlen($chunk);
                },
            ]);
            $ok = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($ok === false || $tooBig || $code !== 200) {
                return null;
            }
        } else {
            $context = stream_context_create(['http' => [
                'timeout'         => $timeout,
                'follow_location' => 0,
                'header'          => "User-Agent: {$this->userAgent}\r\n",
            ]]);
            $result = @file_get_contents($url, false, $context, 0, $maxBytes + 1);
            if ($result === false || !isset($http_response_header[0]) || strpos($http_response_header[0], ' 200') === false) {
                return null;
            }
            $data = (string) $result;
        }

        if ($data === '' || strlen($data) > $maxBytes) {
            return null;
        }
        return $data;
    }

    /**
     * Сжатие ассетов скачивает и склеивает внешний CSS.
     * @return bool
     */
    protected function isRemoteCssCompile()
    {
        if (!\Yii::$app->has('assetsAutoCompress')) {
            return false;
        }
        $compress = \Yii::$app->get('assetsAutoCompress');
        return !empty($compress->enabled) && !empty($compress->cssFileCompile) && !empty($compress->cssFileRemouteCompile);
    }
}
