<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\formInputs\datetime;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * The platform date and time field: a button with the value ("Сегодня, 15:30",
 * "пн, 3 нояб., 10:00") opening a window with the month, the time (hours and
 * minutes by a step) and quick choices ("Сегодня", "Завтра", "В конце
 * недели", "Через неделю", "В конце месяца"). No third-party pickers.
 *
 * It replaces kartik DateControl by changing the class: `type`,
 * `displayFormat`, `saveFormat`, `options`, `saveOptions` and
 * `widgetOptions.pluginOptions.minuteStep` are understood the same way.
 *
 * Markup: a hidden input with the saved value (the id and name of the field,
 * so ActiveForm, WidgetField and filters work as with any input; a `change`
 * event is fired on it), a hidden text mirror `<id>-disp` with the shown
 * text (as the DateControl display input), the trigger button and the clear
 * button. The window is built by the script on demand, so fields loaded by
 * PJAX or into windows need no initialization. Setting the hidden input
 * from outside and firing `change` on it (or emptying the mirror) updates
 * the field.
 *
 * The value can also be typed in the window ("05.11.2026 14:30", "5.11 14:30",
 * "2026-11-05 14:30"): it is checked with a hint and applied by Enter.
 *
 * Wall time is in `timeZone` (the formatter time zone by default); the
 * unix timestamp is converted, string save formats keep that wall time.
 *
 * JS: sx.DateTimeInput.open(root), close(), setValue(root, value).
 */
class DateTimeInputWidget extends InputWidget
{
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';

    /** DateControl-compatible names of the types. */
    const FORMAT_DATE = self::TYPE_DATE;
    const FORMAT_DATETIME = self::TYPE_DATETIME;

    /** The shown text: "Сегодня, 15:30", "пн, 3 нояб., 10:00" (+ the year when it is not the current one). */
    const DISPLAY_HUMAN = 'human';

    /** Built-in quick choices of the window. */
    const QUICK_DEFAULT = ['today', 'tomorrow', 'weekEnd', 'nextWeek', 'monthEnd'];

    const QUICK_LABELS = [
        'today'    => 'Сегодня',
        'tomorrow' => 'Завтра',
        'weekEnd'  => 'В конце недели',
        'nextWeek' => 'Через неделю',
        'monthEnd' => 'В конце месяца',
    ];

    const DAYS_SHORT = ['вс', 'пн', 'вт', 'ср', 'чт', 'пт', 'сб'];

    const MONTHS_SHORT = ['янв.', 'февр.', 'мар.', 'апр.', 'мая', 'июн.', 'июл.', 'авг.', 'сент.', 'окт.', 'нояб.', 'дек.'];

    /** @var string date|datetime (a period is a separate field, not a type of this one) */
    public $type = self::TYPE_DATETIME;

    /** @var string "human" or a PHP date pattern, with or without the DateControl prefix "php:" */
    public $displayFormat = self::DISPLAY_HUMAN;

    /**
     * @var string the posted value: "php:U" (unix timestamp), "php:Y-m-d",
     * "php:Y-m-d H:i:s" or another pattern of d j m n Y y H G i s
     */
    public $saveFormat = 'php:U';

    /** @var array attributes of the hidden input with the saved value (DateControl saveOptions) */
    public $saveOptions = [];

    /** @var array DateControl compatibility: only pluginOptions.minuteStep is used */
    public $widgetOptions = [];

    /** @var int step of the minutes grid */
    public $minuteStep = 5;

    /**
     * @var int|string|null the earliest value: a unix timestamp or a strtotime()
     * string in the time zone ("today", "now", "2026-11-01")
     */
    public $min = null;

    /** @var int|string|null the latest value, as $min */
    public $max = null;

    /** @var bool the value may be emptied ("Очистить" and × on the field) */
    public $allowClear = true;

    /** @var string|null text of the empty field */
    public $placeholder = null;

    /**
     * @var array|false|null quick choices: keys of QUICK_LABELS or
     * ['label' => 'Через 3 дня', 'days' => 3]; null — QUICK_DEFAULT; false — none
     */
    public $quickOptions = null;

    /** @var string time for a day chosen without a time ("H:i"); today it is not earlier than now */
    public $defaultTime = '09:00';

    /** @var string|null time zone of the wall time; null — Yii::$app->formatter->timeZone */
    public $timeZone = null;

    /** @var bool */
    public $disabled = false;

    /** @var bool the value is shown but cannot be changed */
    public $readonly = false;

    /** DateControl compatibility. */
    public function setDisplayTimezone($value)
    {
        $this->timeZone = $value;
    }

    public function init()
    {
        parent::init();

        if (!in_array($this->type, [self::TYPE_DATE, self::TYPE_DATETIME], true)) {
            throw new InvalidConfigException("DateTimeInputWidget: unknown type '{$this->type}'.");
        }

        if ($step = ArrayHelper::getValue($this->widgetOptions, 'pluginOptions.minuteStep')) {
            $this->minuteStep = (int)$step;
        }
        $this->minuteStep = max(1, min(30, (int)$this->minuteStep));

        if ($this->timeZone === null) {
            $this->timeZone = \Yii::$app->formatter->timeZone ?: \Yii::$app->timeZone;
        }
        if ($this->placeholder === null) {
            $this->placeholder = $this->type === self::TYPE_DATE ? 'Выберите дату' : 'Выберите дату и время';
        }
    }

    public function run()
    {
        DateTimeInputAsset::register($this->view);

        $tz = new \DateTimeZone($this->timeZone);
        $save = self::pattern($this->saveFormat);
        $date = $this->parse($this->hasModel() ? Html::getAttributeValue($this->model, $this->attribute) : $this->value, $save, $tz);

        $inputId = $this->options['id'];
        $saveOptions = array_merge($this->saveOptions, ['id' => $inputId, 'class' => trim('sx-dt__value '.($this->saveOptions['class'] ?? ''))]);
        $saved = $date ? $this->format($date, $save) : '';
        $hidden = $this->hasModel()
            ? Html::activeHiddenInput($this->model, $this->attribute, array_merge($saveOptions, ['value' => $saved]))
            : Html::hiddenInput($this->name, $saved, $saveOptions);

        $label = $date ? $this->label($date, $tz) : '';
        $disabled = $this->disabled || !empty($this->options['disabled']);
        $readonly = $this->readonly || !empty($this->options['readonly']);

        $trigger = $this->options;
        unset($trigger['id'], $trigger['autocomplete'], $trigger['disabled'], $trigger['readonly'], $trigger['placeholder']);
        // The field draws itself: no Bootstrap control class from ActiveField/WidgetField.
        Html::removeCssClass($trigger, 'form-control');
        Html::addCssClass($trigger, 'sx-dt__trigger');
        $trigger = array_merge($trigger, [
            'type'          => 'button',
            'id'            => $inputId.'-btn',
            'aria-haspopup' => 'dialog',
            'aria-expanded' => 'false',
            'disabled'      => $disabled || $readonly,
        ]);

        $config = [
            'type'        => $this->type,
            'save'        => $save,
            'display'     => $this->displayFormat === self::DISPLAY_HUMAN ? self::DISPLAY_HUMAN : self::pattern($this->displayFormat),
            'tz'          => $this->timeZone,
            'step'        => $this->minuteStep,
            'min'         => $this->limit($this->min, $tz),
            'max'         => $this->limit($this->max, $tz),
            'clear'       => (bool)$this->allowClear,
            'quick'       => $this->quick(),
            'defaultTime' => $this->defaultTime,
            'placeholder' => $this->placeholder,
        ];

        $root = ['class' => 'sx-dt'.($date ? '' : ' is-empty'), 'id' => $inputId.'-widget', 'data-sx-datetime' => Json::encode($config)];

        return Html::tag('div',
            $hidden
            .Html::textInput(null, $label, ['id' => $inputId.'-disp', 'class' => 'sx-dt__display', 'hidden' => true, 'tabindex' => -1, 'aria-hidden' => 'true', 'readonly' => true])
            .Html::tag('button',
                self::icon($this->type === self::TYPE_DATE ? 'calendar' : 'calendar-clock')
                .Html::tag('span', Html::encode($label !== '' ? $label : $this->placeholder), ['class' => 'sx-dt__label']),
                $trigger
            )
            .($this->allowClear && !$disabled && !$readonly
                ? Html::button('×', ['class' => 'sx-dt__clear', 'aria-label' => 'Очистить', 'title' => 'Очистить', 'tabindex' => -1])
                : ''),
            $root
        );
    }

    /** "php:Y-m-d" → "Y-m-d". */
    public static function pattern(string $format): string
    {
        return strncmp($format, 'php:', 4) === 0 ? substr($format, 4) : $format;
    }

    /** The shown text of a date in the time zone (the same rules as the script). */
    public function label(\DateTimeInterface $date, \DateTimeZone $tz): string
    {
        if ($this->displayFormat !== self::DISPLAY_HUMAN) {
            return $this->format($date, self::pattern($this->displayFormat));
        }

        $today = new \DateTimeImmutable('today', $tz);
        $day = (new \DateTimeImmutable($date->format('Y-m-d'), $tz));
        $diff = (int)round(($day->getTimestamp() - $today->getTimestamp()) / 86400);
        $relative = [0 => 'Сегодня', 1 => 'Завтра', -1 => 'Вчера'];
        if (isset($relative[$diff])) {
            $text = $relative[$diff];
        } else {
            $text = self::DAYS_SHORT[(int)$date->format('w')].', '.$date->format('j').' '.self::MONTHS_SHORT[(int)$date->format('n') - 1];
            if ($date->format('Y') !== $today->format('Y')) {
                $text .= ' '.$date->format('Y');
            }
        }

        return $this->type === self::TYPE_DATETIME ? $text.', '.$date->format('H:i') : $text;
    }

    /** Formats by a pattern of d j m n Y y H G i s U D M (D, M — Russian short names). */
    public function format(\DateTimeInterface $date, string $pattern): string
    {
        $result = '';
        for ($i = 0, $n = strlen($pattern); $i < $n; $i++) {
            $char = $pattern[$i];
            if ($char === '\\' && $i + 1 < $n) {
                $result .= $pattern[++$i];
            } elseif ($char === 'D') {
                $result .= self::DAYS_SHORT[(int)$date->format('w')];
            } elseif ($char === 'M') {
                $result .= self::MONTHS_SHORT[(int)$date->format('n') - 1];
            } elseif (strpos('djmnYyHGisU', $char) !== false) {
                $result .= $date->format($char);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    /** The value as a date in the time zone, or null when it is empty or not a date. */
    protected function parse($value, string $save, \DateTimeZone $tz): ?\DateTimeImmutable
    {
        if ($value === null || $value === '' || $value === false || (is_string($value) && strncmp($value, '0000-00-00', 10) === 0)) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return (new \DateTimeImmutable('@'.$value->getTimestamp()))->setTimezone($tz);
        }

        $value = (string)$value;
        if ($save !== 'U') {
            $date = \DateTimeImmutable::createFromFormat('!'.$save, $value, $tz);
            if ($date) {
                return $date;
            }
        }
        if (preg_match('/^-?\d+$/', $value)) {
            return (new \DateTimeImmutable('@'.$value))->setTimezone($tz);
        }
        $time = strtotime($value);

        return $time === false ? null : (new \DateTimeImmutable('@'.$time))->setTimezone($tz);
    }

    /** A min/max limit as the wall time "Y-m-d H:i" in the time zone. */
    protected function limit($value, \DateTimeZone $tz): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $date = is_int($value) || preg_match('/^-?\d+$/', (string)$value)
            ? (new \DateTimeImmutable('@'.$value))->setTimezone($tz)
            : new \DateTimeImmutable((string)$value, $tz);

        return $date->format('Y-m-d H:i');
    }

    /** Normalized quick choices for the script: key, label and, for an own choice, days. */
    protected function quick(): array
    {
        if ($this->quickOptions === false) {
            return [];
        }

        $result = [];
        foreach ($this->quickOptions ?? self::QUICK_DEFAULT as $key => $option) {
            if (is_string($option) && isset(self::QUICK_LABELS[$option])) {
                $result[] = ['key' => $option, 'label' => self::QUICK_LABELS[$option]];
            } elseif (is_string($key) && isset(self::QUICK_LABELS[$key]) && is_string($option)) {
                $result[] = ['key' => $key, 'label' => $option];
            } elseif (is_array($option) && isset($option['label'], $option['days'])) {
                $result[] = ['key' => 'days', 'label' => (string)$option['label'], 'days' => (int)$option['days']];
            } else {
                throw new InvalidConfigException('DateTimeInputWidget: a quick option is a key of QUICK_LABELS or [label, days].');
            }
        }

        return $result;
    }

    protected static function icon(string $name): string
    {
        $paths = [
            'calendar'       => '<rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
            'calendar-clock' => '<path d="M21 10V7a2.5 2.5 0 0 0-2.5-2.5h-13A2.5 2.5 0 0 0 3 7v11a2.5 2.5 0 0 0 2.5 2.5H11M3 9.5h18M8 2.5v4M16 2.5v4"/><circle cx="17.5" cy="17.5" r="4"/><path d="M17.5 15.8v1.9l1.2.8"/>',
        ];

        return Html::tag('svg', $paths[$name], [
            'class' => 'sx-dt__icon', 'viewBox' => '0 0 24 24', 'width' => 18, 'height' => 18, 'fill' => 'none',
            'stroke' => 'currentColor', 'stroke-width' => 1.8, 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round',
            'focusable' => 'false', 'aria-hidden' => 'true',
        ]);
    }
}
