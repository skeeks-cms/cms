<?php
namespace skeeks\cms\events;

/** Transactional extension point. Contains record IDs, never credentials. */
class UserSessionsRevokedEvent extends \yii\base\Event
{
    public $sessionIds = [];
    public $userId;
    public $realm;
}
