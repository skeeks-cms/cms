<?php

namespace skeeks\cms\widgets\admin;

use yii\base\Event;

/**
 * {@see ClientChannelsWidget::EVENT_CHANNELS}: packages add channels of a
 * client person or add notes and a state to the ones already listed.
 */
class ClientChannelsEvent extends Event
{
    /** @var \skeeks\cms\models\CmsUser */
    public $user;

    /**
     * @var array[] key => channel. Core keys: "email:<value in lower case>",
     * "phone:<digits>". A channel:
     * - kind: email, phone, messenger, app or a package code;
     * - icon: BackendIcon name;
     * - label: plain text ("Email", "MAX", "Приложение");
     * - value: plain text (the address, the chat name) or empty;
     * - state: ok (works), warning (works with a problem), danger (does not
     *   deliver), off (not connected);
     * - notes: plain texts ("подтверждён", "отписан от рекламы");
     * - priority: order, lower first (core: email 100, phone 200).
     */
    public $channels = [];
}
