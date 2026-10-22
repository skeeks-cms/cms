<?php
/**
 * Server side of the rich-text editor (RichTextInputWidget): cleaning presets
 * and RichTextBehavior on an isolated SQLite memory database, and the session
 * binding of TemporaryUploads with a stub session (parallel uploads run as
 * child processes). Editor uploads of the behavior checks are "expired".
 *
 * Usage: php tests/rich-text-editor.php [vendor/autoload.php]
 */

$autoloadCandidates = array_filter([
    $argv[1] ?? null,
    dirname(__DIR__).'/vendor/autoload.php',
    dirname(__DIR__, 3).'/autoload.php',
    '/app/vendor/autoload.php',
]);
foreach ($autoloadCandidates as $autoload) {
    if (is_file($autoload)) {
        require $autoload;
        require_once dirname($autoload).'/yiisoft/yii2/Yii.php';
        break;
    }
}

use skeeks\cms\behaviors\RichTextBehavior;
use skeeks\cms\helpers\RichTextHtml;
use skeeks\cms\services\TemporaryUploads;

function rteExpect($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** Session with a given id and no storage: TemporaryUploads must not need session data. */
class RteSession extends \yii\web\Session
{
    public $fakeId = '';

    public function open()
    {
    }

    public function getIsActive()
    {
        return true;
    }

    public function getId()
    {
        return $this->fakeId;
    }
}

$uploadRoot = getenv('RTE_UPLOAD_ROOT') ?: sys_get_temp_dir().'/rte-uploads-'.getmypid();
new \yii\console\Application([
    'id' => 'cms-rich-text-editor',
    'basePath' => sys_get_temp_dir(),
    'components' => [
        'db' => ['class' => \yii\db\Connection::class, 'dsn' => 'sqlite::memory:'],
        'session' => ['class' => RteSession::class, 'fakeId' => (string)getenv('RTE_SESSION')],
    ],
    'modules' => [
        'ajaxfileupload' => ['class' => \skeeks\yii2\ajaxfileupload\AjaxFileUploadModule::class, 'private_tmp_dir' => $uploadRoot],
    ],
]);

// Child process of the parallel upload check: wait for the common start, upload, print the token.
if (getenv('RTE_SESSION') !== false) {
    time_sleep_until((float)getenv('RTE_START'));
    echo TemporaryUploads::putBytes('bytes of '.getmypid(), 'скрин '.getmypid().'.png')['token'];
    exit(0);
}

$html = '<h2 class="x">Заголовок</h2><p style="font-family:Arial;font-size:large;color:red;position:fixed">Текст <b>жирный</b></p>'
    .'<table><tr><td colspan="2" onclick="x()">ячейка</td></tr></table>'
    .'<img src="https://ext.example/a.png" width="40"><img src="/x" data-sx-upload="rt-0123456789abcdef0123456789abcdef/скрин.png">'
    .'<script>alert(1)</script><a href="javascript:x()">плохая</a><a href="tel:+79990000000">звонок</a>';

$basic = RichTextHtml::clean($html, 'basic');
rteExpect($basic === '<h2>Заголовок</h2><p style="color:red">Текст <b>жирный</b></p>ячейка<a>плохая</a><a href="tel:+79990000000">звонок</a>',
    'basic: no fonts, sizes, tables or images: '.$basic);

$standard = RichTextHtml::clean($html, 'standard');
rteExpect($standard === '<h2>Заголовок</h2><p style="font-size:large;color:red">Текст <b>жирный</b></p>ячейка'
    .'<img data-sx-upload="rt-0123456789abcdef0123456789abcdef/скрин.png" alt=""><a>плохая</a><a href="tel:+79990000000">звонок</a>',
    'standard: sizes and editor uploads, no font family: '.$standard);

$full = RichTextHtml::clean($html, ['preset' => 'full', 'images' => true, 'fonts' => true]);
rteExpect(strpos($full, '<table><tr><td colspan="2">ячейка</td></tr></table>') !== false
    && strpos($full, '<img src="https://ext.example/a.png" width="40" alt="">') !== false
    && strpos($full, 'font-family:Arial') !== false && strpos($full, 'script') === false,
    'full with images and fonts keeps tables, external pictures and the font: '.$full);

rteExpect(RichTextHtml::uploadTokens($standard) === ['rt-0123456789abcdef0123456789abcdef/скрин.png'], 'Upload tokens must be found.');
rteExpect(!TemporaryUploads::isToken('../../etc/passwd') && !TemporaryUploads::isToken('rt-0123/x.png'), 'Only random tokens are accepted.');
rteExpect(RichTextHtml::isEmpty('<p>&nbsp;</p><br>') && !RichTextHtml::isEmpty('<p><img src="/a.png"></p>'), 'Empty text detection.');

// Temporary uploads: bound to the session by an owner mark, not by session data, so parallel uploads all stay usable.
$session = \Yii::$app->session;
$session->fakeId = '';
try {
    TemporaryUploads::putBytes('x', 'a.png');
    rteExpect(false, 'An upload without a session must be refused.');
} catch (RuntimeException $e) {
    rteExpect(strpos($e->getMessage(), 'must be refused') === false, $e->getMessage());
}
$session->fakeId = 'session-a';
$start = microtime(true) + 1;
$children = $childPipes = [];
for ($i = 0; $i < 4; $i++) {
    $children[] = proc_open([PHP_BINARY, __FILE__, ...array_slice($argv, 1, 1)], [1 => ['pipe', 'w']], $pipes,
        null, array_merge(getenv(), ['RTE_SESSION' => 'session-a', 'RTE_START' => (string)$start, 'RTE_UPLOAD_ROOT' => $uploadRoot]));
    $childPipes[] = $pipes[1];
}
$tokens = [];
foreach ($children as $i => $child) {
    $tokens[] = trim((string)stream_get_contents($childPipes[$i]));
    rteExpect(proc_close($child) === 0, 'Parallel upload process failed: '.end($tokens));
}
rteExpect(count(array_unique(array_filter($tokens, [TemporaryUploads::class, 'isToken']))) === 4, 'Each parallel upload gets its token: '.implode(', ', $tokens));
foreach ($tokens as $token) {
    $path = TemporaryUploads::path($token);
    rteExpect($path && strpos((string)file_get_contents($path), 'bytes of ') === 0, 'Every parallel upload must stay usable: '.$token);
}
$session->fakeId = 'session-b';
rteExpect(TemporaryUploads::path($tokens[0]) === null, 'Another session must not see the upload.');
TemporaryUploads::forget($tokens[0]);
$session->fakeId = 'session-a';
rteExpect(TemporaryUploads::path($tokens[0]) !== null, 'Another session must not forget the upload.');
rteExpect(TemporaryUploads::path('rt-0123456789abcdef0123456789abcdef/скрин.png') === null, 'An unknown token has no path.');
TemporaryUploads::forget($tokens[0]);
rteExpect(TemporaryUploads::path($tokens[0]) === null && !is_dir($uploadRoot.'/'.dirname($tokens[0])), 'forget() removes the file, the mark and the directory.');
// A caller that consumed the file (store() moves it into the storage) still gets the directory removed.
unlink(TemporaryUploads::path($tokens[1]));
TemporaryUploads::forget($tokens[1]);
rteExpect(!is_dir($uploadRoot.'/'.dirname($tokens[1])), 'forget() after the file was consumed removes the mark and the directory.');
// The daily cleanup removes old uploads together with their marks (the emptied directory on a later run).
$oldDir = $uploadRoot.'/'.dirname($tokens[2]);
foreach ([$uploadRoot.'/'.$tokens[2], $oldDir.'/.owner'] as $old) {
    touch($old, time() - 7200);
}
(new \skeeks\yii2\ajaxfileupload\services\TemporaryFileCleanup())->run($uploadRoot, 3600);
rteExpect(count(scandir($oldDir)) === 2 && TemporaryUploads::path($tokens[3]) !== null, 'Cleanup removes expired uploads with their marks and keeps fresh ones.');
touch($oldDir, time() - 7200);
(new \skeeks\yii2\ajaxfileupload\services\TemporaryFileCleanup())->run($uploadRoot, 3600);
rteExpect(!is_dir($oldDir), 'Cleanup removes the emptied upload directory.');
TemporaryUploads::forget($tokens[3]);
@rmdir($uploadRoot);

// The behavior: changed values are cleaned, untouched and plain ones are kept, expired uploads are errors.
\Yii::$app->db->createCommand()->createTable('rte_item', ['id' => 'pk', 'body' => 'text', 'page' => 'text'])->execute();

class RteItem extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'rte_item';
    }

    public function rules()
    {
        return [[['body', 'page'], 'string']];
    }

    public function behaviors()
    {
        return [
            'richText' => ['class' => RichTextBehavior::class, 'attributes' => ['body' => 'basic', 'page' => false]],
        ];
    }
}

$old = '<p class="legacy" style="font-family:Tahoma">старый</p>';
\Yii::$app->db->createCommand()->insert('rte_item', ['body' => $old, 'page' => '<div class="hero"><script>x()</script></div>'])->execute();
$item = RteItem::findOne(1);
$item->save();
rteExpect($item->body === $old && RteItem::findOne(1)->body === $old, 'An untouched value must not be cleaned.');

// A browser posts an untouched textarea with CRLF line breaks: still untouched, stored byte for byte.
$oldLines = "<p style=\"font-family:Tahoma\">первая</p>\n<p>вторая</p>";
\Yii::$app->db->createCommand()->update('rte_item', ['body' => $oldLines])->execute();
$item = RteItem::findOne(1);
$item->body = str_replace("\n", "\r\n", $oldLines);
rteExpect($item->save() && RteItem::findOne(1)->body === $oldLines, 'CRLF of an untouched textarea must not count as an edit.');
\Yii::$app->db->createCommand()->update('rte_item', ['body' => $old])->execute();
$item = RteItem::findOne(1);

$item->body = '<p onclick="x()">новый</p>';
$item->page = '<div class="hero"><script>y()</script></div>';
rteExpect($item->save(), 'Save must succeed.');
rteExpect($item->body === '<p>новый</p>', 'A changed value must be cleaned: '.$item->body);
rteExpect($item->page === '<div class="hero"><script>y()</script></div>', 'Trusted HTML must stay as written.');

$item->body = "строка 1\na < b";
$item->save();
rteExpect($item->body === "строка 1\na < b", 'Plain text must not be touched.');

$item->body = '<p>картинка <img data-sx-upload="rt-0123456789abcdef0123456789abcdef/скрин.png"></p>';
rteExpect($item->validate() && $item->body === '<p>картинка </p>', 'The basic preset drops pictures: '.$item->body);
$item->page = '<p>картинка <img data-sx-upload="rt-0123456789abcdef0123456789abcdef/скрин.png"></p>';
rteExpect(!$item->validate() && strpos((string)$item->getFirstError('page'), 'устарела') !== false, 'An expired editor upload must be a validation error.');

// Pictures of pasted text are downloaded by the server: never from internal addresses (no network used here).
$downloader = \skeeks\cms\services\RemoteImageDownloader::class;
foreach (['127.0.0.1', '10.1.2.3', '172.18.0.2', '192.168.1.1', '169.254.169.254', '100.64.0.1', '0.0.0.0', '::1', 'fd00::1', 'fe80::1', '::ffff:127.0.0.1'] as $ip) {
    rteExpect(!$downloader::isPublic($ip), 'Internal address must be refused: '.$ip);
}
rteExpect($downloader::isPublic('93.184.216.34') && $downloader::isPublic('2606:4700::1111'), 'Public addresses are allowed.');
foreach (['http://127.0.0.1/a.png', 'http://[::1]/a.png', 'http://localhost/a.png', 'ftp://example.com/a.png',
    'http://example.com:8080/a.png', 'http://user:pass@example.com/a.png', 'file:///etc/passwd', 'javascript:alert(1)'] as $url) {
    try {
        $downloader::download($url);
        rteExpect(false, 'URL must be refused: '.$url);
    } catch (RuntimeException $e) {
        rteExpect(strpos($e->getMessage(), 'must be refused') === false, $e->getMessage());
    }
}

// ComboText allows pasted pictures only where the behavior stores them.
rteExpect(RichTextBehavior::covers($item, 'page') && !RichTextBehavior::covers($item, 'id') && !RichTextBehavior::covers(new \yii\base\DynamicModel(['page']), 'page'),
    'covers() must report only attributes handled by RichTextBehavior.');

echo "cms rich-text editor: OK\n";
