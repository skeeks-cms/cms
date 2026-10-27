<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

use yii\base\BaseObject;

/**
 * A way a person receives notifications (the CRM bell, mail, a messenger, the
 * application). The core knows only the CRM; packages register the others in
 * `notifications.channels` and deliver themselves on Notifications::EVENT_CREATED.
 * The channel tells the settings page whether it works on the site and for the
 * person, and renders its "Подключить" part.
 */
class NotifyChannel extends BaseObject
{
    /** @var string */
    public $code;

    /** @var string */
    public $name;

    /** Order of fallback channels: a lower one is tried first. */
    public $priority = 100;

    /** The channel works on this site at all (a bot exists, a mailer is set). */
    public function isAvailable(): bool
    {
        return true;
    }

    /** The person can receive here now (a chat is connected, an email exists). */
    public function isConnected(int $userId): bool
    {
        return false;
    }

    /** One line for the settings page: "Подключён: @nick", "Не подключён". */
    public function status(int $userId): string
    {
        return $this->isConnected($userId) ? 'Подключён' : 'Не подключён';
    }

    /** The last delivery problem in plain words, shown apart from the status; null — none. */
    public function problem(int $userId): ?string
    {
        return null;
    }

    /** Buttons of the channel on the settings page (connect, check, disconnect). */
    public function renderActions(int $userId, \yii\web\View $view): string
    {
        return '';
    }
}
