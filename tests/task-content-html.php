<?php
/** Run with the consuming application's Composer vendor directory. */
require rtrim($argv[1] ?? '/app/vendor', '/').'/autoload.php';
require rtrim($argv[1] ?? '/app/vendor', '/').'/yiisoft/yii2/Yii.php';
require_once __DIR__.'/../src/helpers/TaskContentHelper.php';
new yii\console\Application(['id' => 'task-content-test', 'basePath' => sys_get_temp_dir(), 'runtimePath' => sys_get_temp_dir()]);

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

$text = 'До <title>, после <title> и конец.';
$html = skeeks\cms\helpers\TaskContentHelper::render($text);
check(html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8') === $text, 'Literal tags/text changed');
foreach (['textarea', 'style', 'script', 'iframe', 'xmp', 'plaintext', 'noscript'] as $tag) {
    $html = skeeks\cms\helpers\TaskContentHelper::render('До <'.$tag.'> после');
    check(strpos($html, '<'.$tag) === false && strpos($html, 'после') !== false, 'Raw-text tag survived: '.$tag);
}
$html = skeeks\cms\helpers\TaskContentHelper::render('<p><strong>Текст</strong><br>строка</p><ul><li>Пункт</li></ul><pre><code>&lt;title&gt;</code></pre><a href="https://example.com/">Ссылка</a><table><tr><td>Ячейка</td></tr></table>');
foreach (['<strong>', '<br', '<ul>', '<li>', '<pre>', '<code>', 'href="https://example.com/"', '<table>', '<td>'] as $fragment) {
    check(strpos($html, $fragment) !== false, 'Formatting lost: '.$fragment);
}
$html = skeeks\cms\helpers\TaskContentHelper::render('<p onclick="alert(1)">Текст</p><a href="javascript:alert(1)">Ссылка</a><img src="x" onerror="alert(1)" alt="Фото">');
check(!preg_match('/\bon(?:click|error)\s*=|javascript:/i', $html), 'Unsafe attributes survived');
check(skeeks\cms\helpers\TaskContentHelper::render('') === '', 'Empty description changed');
check(skeeks\cms\helpers\TaskContentHelper::render('&lt;title&gt; &amp; текст') === '&lt;title&gt; &amp; текст', 'Already escaped text changed');
echo "Task content HTML: OK\n";
