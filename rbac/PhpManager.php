<?php
/**
 * PhpManager
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\rbac;

use Yii;
use yii\rbac\Assignment;

class PhpManager extends \yii\rbac\PhpManager
{
    /** @var string */
    public $roleParam = 'role';

    public function getAssignments($userId)
    {
        $user = Yii::$app->getUser();
        $assignments = [];
        if (!$user->getIsGuest())
        {
            $assignment = new Assignment;
            $assignment->userId = $userId;
            $assignment->roleName = $user->getIdentity()->{$this->roleParam};
            $assignments[$assignment->roleName] = $assignment;
        }
        return $assignments;
    }
}