<?php

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\CrmContactsAsset;
use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use yii\base\Event;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * One contact block of a CRM card (company, lead, ...): phones, emails,
 * addresses, requisites, links. Both cards use this widget, so the blocks look
 * and behave the same everywhere.
 *
 * Icons are semantic {@see BackendIcon} names; an older cms-backend without a
 * name falls back to the matching icon-font glyph.
 *
 * ```php
 * echo CrmContactBlockWidget::widget([
 *     'title' => 'Телефоны',
 *     'hint' => 'Первый телефон основной.',
 *     'icon' => 'phone',
 *     'items' => [[
 *         'label' => 'Рабочий',
 *         'value' => Html::a('+7 ...', 'tel:+7...'),   // trusted HTML
 *         'note' => null,                               // plain text
 *         'actions' => CrmContactBlockWidget::menu('/cms/admin-cms-company-phone', $id, 'Изменить телефон'),
 *     ]],
 *     'emptyText' => 'Телефонов пока нет',
 *     'addLabel' => 'Добавить телефон',
 *     'addRoute' => ['/cms/admin-cms-company-phone/create', 'CmsCompanyPhone' => ['cms_company_id' => $id]],
 *     'addSize' => 'small',
 * ]);
 * ```
 */
class CrmContactBlockWidget extends Widget
{
    public const CONTACT_PHONE = 'phone';
    public const CONTACT_EMAIL = 'email';

    /** Class-level event: packages add actions to phone and email rows. */
    public const EVENT_CONTACT_ACTIONS = 'contactActions';

    /** Icon-font glyphs for BackendIcon names missing in an older cms-backend. */
    private const ICON_FALLBACK = [
        'phone' => 'fas fa-phone',
        'mail' => 'far fa-envelope',
        'message' => 'fas fa-sms',
        'map-pin' => 'fas fa-map-marker-alt',
        'id-card' => 'fas fa-id-card',
        'link' => 'fas fa-link',
        'more-vertical' => 'fas fa-ellipsis-v',
        'help' => 'far fa-question-circle',
        'close' => 'fas fa-times',
        'plus' => 'fas fa-plus',
        'edit' => 'fas fa-pen',
        'building' => 'far fa-building',
    ];

    public string $title = '';
    /** Tooltip of the "?" icon in the header; plain text. */
    public string $hint = '';
    /** BackendIcon name of the row icon. */
    public string $icon = 'info';
    /** @var array[] each: label (text), value (HTML), note (text|null), actions (HTML) */
    public array $items = [];
    public string $emptyText = '';
    public string $addLabel = 'Добавить';
    /** Route of the add form, opened in the backend window; null hides the button. */
    public ?array $addRoute = null;
    /** Window size of the add form: 'small' or null for the regular one. */
    public ?string $addSize = null;
    /** Extra BackendSurfaceWidget options (e.g. density classes of the card). */
    public array $surface = [];
    /** Show only the first N rows and a link «ещё N» to $moreUrl (the card that has them all); 0 — all. */
    public int $limit = 0;
    public ?string $moreUrl = null;

    /**
     * Rows of phones (company, client or deal phone models: `id`, `name`,
     * `value`) with the same actions everywhere: the "⋮" menu of their
     * controller, SMS/chat and a call, plus package actions.
     *
     * @param array $options editable (bool, the "⋮" menu), actions (bool, SMS and the call; package actions stay),
     *                       call (array, extra attributes of the call button), note (callable|null, fn($phone): ?string)
     */
    public static function phoneItems(iterable $phones, string $controllerId, $owner, array $options = []): array
    {
        $items = [];
        foreach ($phones as $phone) {
            $value = (string)$phone->value;
            $items[] = [
                'label' => $phone->name ?: 'Телефон',
                'value' => Html::a(Html::encode($value), 'tel:'.preg_replace('/[^\d+]/', '', $value), ['data-pjax' => '0']),
                'note' => isset($options['note']) ? ($options['note'])($phone) : null,
                'actions' => (($options['editable'] ?? true) ? self::menu($controllerId, (int)$phone->id, 'Изменить телефон') : '')
                    .self::contactActions(self::CONTACT_PHONE, $value, $owner, ($options['actions'] ?? true) ? [
                        'sms' => self::iconButton('message', 'Написать SMS', ['class' => 'sx-send-sms-trigger', 'data-phone' => $value]),
                        'call' => self::iconButton('phone', 'Позвонить', array_merge(['class' => 'sx-telephony-btn', 'data-value' => $value], $options['call'] ?? [])),
                    ] : []),
            ];
        }

        return $items;
    }

    /** Rows of emails with the "⋮" menu, «Письмо» (mailto, replaced by the mail package) and package actions; see {@see phoneItems()}. */
    public static function emailItems(iterable $emails, string $controllerId, $owner, array $options = []): array
    {
        $items = [];
        foreach ($emails as $email) {
            $value = (string)$email->value;
            $items[] = [
                'label' => $email->name ?: 'Email',
                'value' => Html::a(Html::encode($value), 'mailto:'.$value, ['data-pjax' => '0']),
                'note' => isset($options['note']) ? ($options['note'])($email) : null,
                'actions' => (($options['editable'] ?? true) ? self::menu($controllerId, (int)$email->id, 'Изменить email') : '')
                    .self::contactActions(self::CONTACT_EMAIL, $value, $owner, [
                        'mailto' => self::iconButton('mail', 'Написать письмо', ['href' => 'mailto:'.$value]),
                    ]),
            ];
        }

        return $items;
    }

    /** The link «ещё N» under a shortened list. */
    public static function moreLink(int $count, ?string $url): string
    {
        $label = 'ещё '.$count;

        return $url
            ? Html::a(Html::encode($label), $url, ['class' => 'sx-contact-more', 'data-pjax' => '0'])
            : Html::tag('div', Html::encode($label), ['class' => 'sx-contact-more']);
    }

    /** A BackendIcon by name, or its icon-font fallback. */
    public static function icon(string $name, int $size = 16): string
    {
        if (BackendIcon::supports($name)) {
            return BackendIcon::render($name, ['size' => $size]);
        }

        return Html::tag('i', '', ['class' => self::ICON_FALLBACK[$name] ?? 'fas fa-circle', 'aria-hidden' => 'true']);
    }

    /** Row menu ("⋮") with the standard actions of a backend controller. */
    public static function menu(string $controllerId, int $modelId, string $title): string
    {
        ob_start();
        AjaxControllerActionsWidget::begin([
            'controllerId' => $controllerId,
            'modelId' => $modelId,
            'tag' => 'div',
            'options' => ['title' => $title, 'aria-label' => $title, 'class' => 'sx-icon-action'],
        ]);
        echo self::icon('more-vertical', 18);
        AjaxControllerActionsWidget::end();

        return ob_get_clean();
    }

    /** Small square icon button for a row action (call, write, remove). */
    public static function iconButton(string $icon, string $title, array $options = []): string
    {
        $tag = isset($options['href']) ? 'a' : 'button';
        if ($tag === 'button') {
            $options['type'] = 'button';
        } else {
            $options['data-pjax'] = '0';
        }
        Html::addCssClass($options, 'sx-button sx-button--secondary sx-button--sm sx-contact-icon-button');
        $options['title'] = $title;
        $options['aria-label'] = $title;

        return Html::tag($tag, self::icon($icon, 16), $options);
    }

    /**
     * Actions of a phone or email row: the card's defaults plus those added by
     * packages through {@see EVENT_CONTACT_ACTIONS}.
     *
     * ```php
     * Event::on(CrmContactBlockWidget::class, CrmContactBlockWidget::EVENT_CONTACT_ACTIONS, function (CrmContactActionsEvent $e) {
     *     if ($e->contactType === CrmContactBlockWidget::CONTACT_EMAIL) {
     *         $e->actions['mailto'] = CrmContactBlockWidget::iconButton('mail', 'Написать письмо', [...]);
     *     }
     * });
     * ```
     *
     * @param string[] $defaults id => trusted HTML
     */
    public static function contactActions(string $contactType, string $value, $owner = null, array $defaults = []): string
    {
        $event = new CrmContactActionsEvent([
            'contactType' => $contactType,
            'value' => $value,
            'owner' => $owner,
            'actions' => $defaults,
        ]);
        Event::trigger(self::class, self::EVENT_CONTACT_ACTIONS, $event);

        return implode('', $event->actions);
    }

    public function run()
    {
        CrmContactsAsset::register($this->view);

        $rows = '';
        $items = $this->limit > 0 ? array_slice($this->items, 0, $this->limit) : $this->items;
        foreach ($items as $item) {
            $body = Html::tag('span', Html::encode((string)($item['label'] ?? '')), ['class' => 'sx-contact-label'])
                .Html::tag('span', (string)($item['value'] ?? ''), ['class' => 'sx-contact-value']);
            if (!empty($item['note'])) {
                $body .= Html::tag('span', Html::encode((string)$item['note']), ['class' => 'sx-contact-note']);
            }
            $rows .= Html::tag('div',
                Html::tag('span', self::icon($this->icon, 16), ['class' => 'sx-contact-icon'])
                .Html::tag('div', $body, ['class' => 'sx-contact-body'])
                .(!empty($item['actions']) ? Html::tag('div', $item['actions'], ['class' => 'sx-contact-actions']) : ''),
                ['class' => 'sx-contact-row']
            );
        }
        if (count($this->items) > count($items)) {
            $rows .= self::moreLink(count($this->items) - count($items), $this->moreUrl);
        }
        if (!$this->items && $this->emptyText !== '') {
            $rows .= Html::tag('div', Html::encode($this->emptyText), ['class' => 'sx-contact-empty']);
        }
        if ($this->addRoute !== null) {
            $rows .= self::addButton($this->addLabel, $this->addRoute, $this->addSize);
        }

        $config = array_merge([
            'title' => $this->title,
            'titleTag' => 'h2',
            'raised' => true,
            'responsive' => true,
            'content' => Html::tag('div', $rows, ['class' => 'sx-contact-list']),
        ], $this->surface);
        if ($this->hint !== '') {
            $config['actions'] = self::hintIcon($this->hint);
        }

        return BackendSurfaceWidget::widget($config);
    }

    /** Full-width "+ Add …" button that opens the form in the backend window. */
    public static function addButton(string $label, array $route, ?string $size = null): string
    {
        $action = ['isOpenNewWindow' => true, 'url' => (string)BackendUrlHelper::createByParams($route)
            ->enableEmptyLayout()->enableNoActions()->url];
        if ($size !== null) {
            $action['size'] = $size;
        }

        return Html::a(self::icon('plus', 16).' '.Html::encode($label), '#', [
            'class' => 'sx-contact-add',
            'data-pjax' => '0',
            'onclick' => 'new sx.classes.backend.widgets.Action('.Json::encode($action).').go(); return false;',
        ]);
    }

    /** The quiet "?" header icon with a tooltip. */
    public static function hintIcon(string $hint): string
    {
        return Html::tag('span', self::icon('help', 16), [
            'class' => 'sx-hint-icon',
            'data-toggle' => 'tooltip',
            'data-html' => 'true',
            'data-container' => 'body',
            'data-placement' => 'top',
            'tabindex' => '0',
            'role' => 'img',
            'aria-label' => $hint,
            'title' => $hint,
        ]);
    }
}
