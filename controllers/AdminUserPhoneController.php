<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\user\UserEmail;
use skeeks\cms\models\user\UserPhone;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminUserEmailController
 * @package skeeks\cms\controllers
 */
class AdminUserPhoneController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление телефонами";
        $this->modelShowAttribute      = "value";
        $this->modelClassName          = UserPhone::className();

        parent::init();

    }

}
