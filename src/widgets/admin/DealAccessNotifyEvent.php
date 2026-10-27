<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use yii\base\Event;

/**
 * The place of client notification subscriptions in the deal card block
 * «Доступ и уведомления» ("Сделка как услуга"). The core shows only the place;
 * the notification package fills it (step У4b):
 *
 * ```php
 * Event::on(DealAccessNotifyEvent::class, DealAccessNotifyEvent::EVENT_NOTIFY, function (DealAccessNotifyEvent $e) {
 *     foreach ($e->people as $userId => $person) {
 *         $e->cells[$userId] = '…what this person gets and a link to the subscription window…';
 *     }
 *     $e->footer = '…';
 * });
 * ```
 *
 * With cells the people table gets the column «Уведомления»; without any
 * handler the block shows a quiet note that subscriptions come later.
 */
class DealAccessNotifyEvent extends Event
{
    /** Class-level event name, triggered on this class. */
    public const EVENT_NOTIFY = 'dealAccessNotify';

    /** @var \skeeks\cms\models\CmsDeal */
    public $deal;

    /** @var array DealAccess::explain() of the deal: user id => person with effective levels */
    public $people = [];

    /** @var string[] user id => trusted HTML of the «Уведомления» cell */
    public $cells = [];

    /** @var string trusted HTML under the people table */
    public $footer = '';

    /** @var bool a handler took the place: the note "появятся позже" is not shown */
    public $handled = false;

    public static function collect(\skeeks\cms\models\CmsDeal $deal, array $people): self
    {
        $event = new static(['deal' => $deal, 'people' => $people]);
        Event::trigger(static::class, static::EVENT_NOTIFY, $event);
        if ($event->cells || $event->footer !== '') {
            $event->handled = true;
        }
        return $event;
    }
}
