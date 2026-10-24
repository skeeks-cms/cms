<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\log;

use yii\base\BaseObject;
use yii\helpers\Html;

/**
 * The content of one entry of an activity feed (CmsLog::entry()). The feed
 * draws every entry in one frame (CmsLogListWidget): the icon of the event
 * type on the left, the title with badges, the main line, muted details and
 * an optional body; the time, the author, pinning and the record menu are
 * the frame's own. A package renderer returns only this content
 * (CmsLogEntryRendererInterface), so all events look alike.
 *
 * `title` and `snippet` are plain text; `summary`, `details`, `badges` and
 * `body` are trusted HTML: encode every dynamic value.
 */
class CmsLogEntry extends BaseObject
{
    const TONE_NEUTRAL = 'neutral';
    const TONE_ACCENT = 'accent';
    const TONE_SUCCESS = 'success';
    const TONE_DANGER = 'danger';
    const TONE_WARNING = 'warning';
    const TONE_INFO = 'info';

    /** @var string skeeks\cms\backend\helpers\BackendIcon name of the event type */
    public $icon = 'info';

    /** @var string color of the icon tile: one of the TONE_* constants */
    public $tone = self::TONE_NEUTRAL;

    /** @var string plain text: "Исходящее письмо", "Входящий звонок" */
    public $title = '';

    /** @var string[] trusted HTML labels after the title, see badge() */
    public $badges = [];

    /** @var string trusted HTML: the main line (the subject of a letter, the name of a proposal) */
    public $summary = '';

    /** @var string[] trusted HTML: short muted lines ("Кому: …", "Длительность: 5 мин") */
    public $details = [];

    /** @var string plain text: the beginning of a letter or a message, shown in up to three muted lines */
    public $snippet = '';

    /** @var string trusted HTML: the text of a comment, a recording */
    public $body = '';

    /**
     * A small label after the title. Long explanations go to its tooltip:
     * "Подготовлено ИИ" with the AI client and the employee in the title.
     */
    public static function badge(string $label, string $title = '', string $tone = self::TONE_ACCENT): string
    {
        $options = ['class' => 'sx-status sx-log-entry__badge'];
        if ($tone !== self::TONE_NEUTRAL) {
            Html::addCssClass($options, 'sx-status--'.$tone);
        }
        if ($title !== '') {
            $options['title'] = $title;
            $options['data-toggle'] = 'tooltip';
        }

        return Html::tag('span', Html::encode($label), $options);
    }

    /**
     * The content without the feed frame, for places that show a log entry
     * as a fragment of HTML (CmsLogRendererInterface::render()).
     */
    public function renderContent(): string
    {
        $html = Html::tag('div', Html::encode($this->title).($this->badges ? ' '.implode(' ', $this->badges) : ''), ['class' => 'h5']);
        if ($this->summary !== '') {
            $html .= Html::tag('div', $this->summary);
        }
        foreach ($this->details as $detail) {
            $html .= Html::tag('div', $detail, ['class' => 'sx-gray']);
        }
        if ($this->snippet !== '') {
            $html .= Html::tag('div', Html::encode($this->snippet), ['class' => 'sx-gray']);
        }
        if ($this->body !== '') {
            $html .= Html::tag('div', $this->body);
        }

        return Html::tag('div', $html, ['style' => 'overflow-wrap:anywhere']);
    }
}
