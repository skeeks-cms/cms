<?php

namespace skeeks\cms\helpers;

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

/** Safe rich text for task descriptions, including literal HTML examples. */
class TaskContentHelper
{
    public static function render($content)
    {
        // Raw-text/document tags must be escaped before the HTML parser sees them.
        // Otherwise an unclosed <title> can consume the remainder of the fragment.
        $content = preg_replace_callback(
            '~</?(?:title|textarea|script|style|xmp|iframe|noembed|noframes|plaintext|noscript|head|html|body)\b[^>]*>~i',
            static function ($match) {
                return Html::encode($match[0]);
            },
            (string)$content
        );

        return HtmlPurifier::process($content, ['Core.EscapeInvalidTags' => true]);
    }
}
