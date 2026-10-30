<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\helpers;

use yii\helpers\Html;

/**
 * Safe HTML of a feed comment (CmsLog::LOG_TYPE_COMMENT).
 *
 * Comments are stored as they came: from the editor, from services and from
 * the Bitrix24 import ("[Bitrix: Email]" with a whole letter inside, "[URL=…]"
 * links). The feed shows them through this helper only:
 * - the comment editor's own cleaning (RichTextHtml, preset "basic") with
 *   pictures: scripts, styles, frames, forms and tables go, inline CSS keeps
 *   only what the editor makes (no widths that break the card), and no
 *   colors: pasted and imported texts carry dark text and white backgrounds
 *   of a letter, unreadable in the dark theme (527 of 651 colored comments
 *   on a production copy came from the Bitrix24 import);
 * - images from other sites are not loaded: they wait for «Показать картинки»
 *   (cms-activity.js), as letters in «Почта» do; an image of a dead site kept
 *   the window `load` event, and the drawer spinner, for 10+ seconds;
 * - links open in a new tab, not inside the backend drawer;
 * - "[URL=…]text[/URL]" of the Bitrix24 import becomes a link.
 */
class CommentHtml
{
    /**
     * @param string   $html   the stored comment
     * @param int|null $hidden the number of images waiting for a click
     */
    public static function render(string $html, ?int &$hidden = null): string
    {
        $hidden = 0;
        if (stripos($html, '[url') !== false) {
            $html = preg_replace_callback('~\[URL=([^\]\s]+)\](.*?)\[/URL\]~is', static function ($m) {
                $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return preg_match('~^https?://~i', $url) ? Html::a($m[2], $url) : $m[2];
            }, $html);
        }
        // Plain text has nothing to clean (RichTextHtml leaves it alone too).
        if (strpos($html, '<') === false) {
            return $html;
        }

        $html = RichTextHtml::clean($html, ['preset' => 'basic', 'images' => true, 'colors' => false]);
        $html = str_replace('<a href=', '<a target="_blank" rel="noopener noreferrer" href=', $html);

        $own = \Yii::$app->has('request', true) && \Yii::$app->request instanceof \yii\web\Request ? (string)parse_url(\Yii::$app->request->hostInfo, PHP_URL_HOST) : '';
        $html = preg_replace_callback('~<img\b[^>]*>~i', static function ($m) use ($own, &$hidden) {
            if (!preg_match('~\ssrc="([^"]*)"~i', $m[0], $src) || !preg_match('~^https?://~i', $src[1])) {
                return $m[0];
            }
            $host = (string)parse_url(html_entity_decode($src[1]), PHP_URL_HOST);
            if ($host === '' || strcasecmp($host, $own) === 0) {
                return $m[0];
            }
            $hidden++;
            return str_replace($src[0], ' data-sx-src="'.$src[1].'"', $m[0]);
        }, $html);

        if ($hidden) {
            $html = Html::tag('div',
                Html::tag('span', 'Картинки с других сайтов скрыты.').' '
                .Html::button('Показать картинки', ['type' => 'button', 'class' => 'sx-log-images__show']),
                ['class' => 'sx-log-images'])
                .$html;
        }

        return $html;
    }
}
