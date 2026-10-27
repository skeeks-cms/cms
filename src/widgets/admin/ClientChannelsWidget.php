<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\ClientAccessAsset;
use skeeks\cms\backend\widgets\BackendSurfaceWidget;
use skeeks\cms\models\CmsUser;
use yii\base\Event;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * «Каналы связи» of a client person: how we can reach them and in what state
 * each way is. Core lists emails and phones; packages add the rest (messenger
 * chats, the mobile app, bounces and opt-outs) through {@see EVENT_CHANNELS}
 * without the core knowing them:
 *
 * ```php
 * Event::on(ClientChannelsWidget::class, ClientChannelsWidget::EVENT_CHANNELS, function (ClientChannelsEvent $e) {
 *     $e->channels['max'] = ['kind' => 'messenger', 'icon' => 'chat', 'label' => 'MAX', 'state' => 'ok', 'notes' => ['привязан']];
 *     $e->channels['email:'.$value]['notes'][] = 'отписан от рекламы';
 * });
 * ```
 *
 * The block of the client card renders every channel; the compact form (deal
 * participants, a person of a company) shows icons of the working ones.
 */
class ClientChannelsWidget extends Widget
{
    /** Class-level event with a {@see ClientChannelsEvent}. */
    public const EVENT_CHANNELS = 'channels';

    public const STATE_OK = 'ok';
    public const STATE_WARNING = 'warning';
    public const STATE_DANGER = 'danger';
    public const STATE_OFF = 'off';

    /** @var CmsUser */
    public $user;

    /** @var bool icons of working channels only */
    public $compact = false;

    /** @var array user id => channels within one request */
    private static $_cache = [];

    /**
     * Channels of the person, sorted by priority.
     *
     * @return array[] key => channel (see ClientChannelsEvent::$channels)
     */
    public static function channels(CmsUser $user): array
    {
        $id = (int)$user->id;
        if (isset(self::$_cache[$id])) {
            return self::$_cache[$id];
        }

        $channels = [];
        foreach ($user->cmsUserEmails as $email) {
            $value = mb_strtolower(trim((string)$email->value));
            $channels['email:'.$value] = [
                'kind'     => 'email',
                'icon'     => 'mail',
                'label'    => 'Email',
                'value'    => (string)$email->value,
                'state'    => self::STATE_OK,
                'notes'    => [$email->is_approved ? 'подтверждён' : 'не подтверждён'],
                'priority' => 100,
            ];
        }
        foreach ($user->cmsUserPhones as $phone) {
            $digits = preg_replace('/\D+/', '', (string)$phone->value);
            $channels['phone:'.$digits] = [
                'kind'     => 'phone',
                'icon'     => 'phone',
                'label'    => 'Телефон',
                'value'    => (string)$phone->value,
                'state'    => self::STATE_OK,
                'notes'    => [$phone->is_approved ? 'подтверждён' : 'не подтверждён'],
                'priority' => 200,
            ];
        }

        $event = new ClientChannelsEvent(['user' => $user, 'channels' => $channels]);
        Event::trigger(self::class, self::EVENT_CHANNELS, $event);

        $result = [];
        foreach ((array)$event->channels as $key => $channel) {
            if (empty($channel['label'])) {
                continue;
            }
            $result[(string)$key] = array_merge([
                'kind' => (string)$key, 'icon' => 'info', 'value' => '', 'state' => self::STATE_OK, 'notes' => [], 'priority' => 500,
            ], $channel);
        }
        uasort($result, static function ($a, $b) {
            return (int)$a['priority'] <=> (int)$b['priority'];
        });

        return self::$_cache[$id] = $result;
    }

    /**
     * The line of a channel for a tooltip: «Email ivan@site.ru: подтверждён, отписан от рекламы».
     */
    public static function describe(array $channel): string
    {
        $text = $channel['label'].($channel['value'] !== '' ? ' '.$channel['value'] : '');
        $notes = array_filter(array_map('strval', (array)$channel['notes']));
        return $notes ? $text.': '.implode(', ', $notes) : $text;
    }

    public function run()
    {
        if (!$this->user) {
            return '';
        }
        ClientAccessAsset::register($this->view);
        $channels = static::channels($this->user);

        if ($this->compact) {
            $icons = '';
            foreach ($channels as $channel) {
                if ($channel['state'] === self::STATE_OFF) {
                    continue;
                }
                $icons .= Html::tag('span', CrmContactBlockWidget::icon((string)$channel['icon'], 14), [
                    'class' => 'sx-client-channel-icon sx-client-channel-icon--'.$channel['state'],
                    'title' => static::describe($channel),
                    'aria-label' => static::describe($channel),
                    'role' => 'img',
                ]);
            }
            return $icons !== ''
                ? Html::tag('span', $icons, ['class' => 'sx-client-channels-compact'])
                : Html::tag('span', 'нет каналов', ['class' => 'sx-client-channels-none']);
        }

        $rows = '';
        foreach ($channels as $channel) {
            $notes = array_values(array_filter(array_map('strval', (array)$channel['notes'])));
            $value = (string)$channel['value'];
            //A channel that is not connected says why in its value line: «не установлено», «личный чат не привязан».
            if ($value === '' && $channel['state'] === self::STATE_OFF) {
                $value = $notes ? array_shift($notes) : 'не подключён';
            }
            $body = Html::tag('span', Html::encode($channel['label']), ['class' => 'sx-contact-label'])
                .Html::tag('span', Html::encode($value), ['class' => 'sx-contact-value'.($channel['state'] === self::STATE_OFF ? ' sx-client-channel--off' : '')]);
            if ($notes) {
                $body .= Html::tag('span', Html::encode(implode(' · ', $notes)), ['class' => 'sx-contact-note']);
            }
            $rows .= Html::tag('div',
                Html::tag('span', CrmContactBlockWidget::icon((string)$channel['icon'], 16), ['class' => 'sx-contact-icon sx-client-channel-icon--'.$channel['state']])
                .Html::tag('div', $body, ['class' => 'sx-contact-body']),
                ['class' => 'sx-contact-row']
            );
        }
        if ($rows === '') {
            $rows = Html::tag('div', 'Ни одного способа связи пока нет', ['class' => 'sx-contact-empty']);
        }

        return BackendSurfaceWidget::widget([
            'title'      => 'Каналы связи',
            'titleTag'   => 'h2',
            'raised'     => true,
            'responsive' => true,
            'options'    => ['class' => 'sx-surface--compact sx-client-channels'],
            'actions'    => CrmContactBlockWidget::hintIcon('Как с человеком можно связаться и в каком состоянии каждый способ: подтверждён ли адрес, доходят ли письма, привязан ли личный чат с ботом, установлено ли приложение.'),
            'content'    => Html::tag('div', $rows, ['class' => 'sx-contact-list']),
        ]);
    }
}
