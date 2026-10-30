<?php
/**
 * Feed comments are shown through helpers\CommentHtml: no scripts or styles,
 * no widths, images of other sites wait for «Показать картинки», "[URL=…]" of
 * the Bitrix24 import is a link. No database.
 *
 * php tests/comment-html.php (inside the php container: cd /app && php vendor/skeeks/cms/tests/comment-html.php)
 */
require (getenv('TEST_APP_ROOT') ?: '/app').'/vendor/autoload.php';
require (getenv('TEST_APP_ROOT') ?: '/app').'/vendor/yiisoft/yii2/Yii.php';
new yii\console\Application(['id' => 'comment-html-test', 'basePath' => __DIR__]);

use skeeks\cms\helpers\CommentHtml;

function commentHtmlExpect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$checks = 0;
$render = static function (string $html, ?int &$hidden = null) use (&$checks) {
    $checks++;
    return CommentHtml::render($html, $hidden);
};

commentHtmlExpect($render("Просто текст\nстрока") === "Просто текст\nстрока", 'Plain text must stay as it is.');

$html = $render('<p onclick="alert(1)">a<script>alert(2)</script><style>body{display:none}</style><iframe src="https://e.example"></iframe></p>');
commentHtmlExpect($html === '<p>a</p>', 'Scripts, styles, frames and handlers must go: '.$html);

$letter = '<div style="min-width:700px;color:#363636;background:#eaeaea"><table><tr><td>Test <img style="max-height:50px" src="http://dead.example/a.PNG" alt="67dc"></td></tr></table>'
    .'<img src="/uploads/a.png"> <a href="mailto:a@b.ru">напишите</a> <a href="javascript:alert(1)">x</a></div>';
$html = $render($letter, $hidden);
commentHtmlExpect($hidden === 1, 'One external image must wait for a click.');
commentHtmlExpect(!preg_match('~\ssrc="http://dead~', $html) &&strpos($html, 'data-sx-src="http://dead.example/a.PNG"') !== false, 'The external image must not load: '.$html);
commentHtmlExpect(strpos($html, 'sx-log-images__show') !== false && strpos($html, 'Показать картинки') !== false, 'The «Показать картинки» button is missing.');
commentHtmlExpect(strpos($html, '<img src="/uploads/a.png"') !== false, 'A site image must load as before.');
commentHtmlExpect(strpos($html, 'min-width') === false && strpos($html, 'color') === false && strpos($html, '<table') === false, 'Widths, colors and tables must go: '.$html);
commentHtmlExpect(strpos($html, 'alert') === false && strpos($html, 'target="_blank" rel="noopener noreferrer" href="mailto:a@b.ru"') !== false, 'Links: a new tab, no javascript: '.$html);

$html = $render('<img src="//evil.example/a.png"><img src="https://x.example/b.png" onerror="alert(1)">', $hidden);
commentHtmlExpect($hidden === 1 && strpos($html, 'evil.example') === false && strpos($html, 'onerror') === false, 'Protocol-relative and handler images: '.$html);

$html = $render('[URL=https://sitika.bitrix24.ru/crm/company/details/1/?IFRAME=Y&amp;T=1#]Продлен сайт[/URL] 16280 [URL=javascript:alert(1)]x[/URL]');
commentHtmlExpect(strpos($html, '<a target="_blank" rel="noopener noreferrer" href="https://sitika.bitrix24.ru/crm/company/details/1/?IFRAME=Y&amp;T=1#">Продлен сайт</a> 16280 x') === 0, 'Bitrix24 [URL] links: '.$html);

echo "OK comment-html: {$checks} renders\n";
