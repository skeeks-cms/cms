<?php
/**
 * AdminUserController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminUserController extends AdminModelEditorController
{
    public function init()
    {
        $this->_label                   = "Управление пользователями";
        $this->_modelShowAttribute      = "username";

        $this->_modelClassName          = User::className();
        $this->_modelSearchClassName    = UserSearch::className();

        parent::init();
    }
}
