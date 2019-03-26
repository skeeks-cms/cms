<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.04.2015
 */

namespace skeeks\cms\helpers\elfinder;

use skeeks\cms\rbac\CmsManager;
use Yii;

class UserPath extends \mihaildev\elfinder\volume\UserPath
{
    public function isAvailable()
    {
        if (!\Yii::$app->user->can(CmsManager::PERMISSION_ELFINDER_USER_FILES)) {
            return false;
        }

        return parent::isAvailable();
    }
}