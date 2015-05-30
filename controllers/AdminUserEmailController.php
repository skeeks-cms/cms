<?php
/**
 * AdminUserEmailController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\user\UserEmail;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\widgets\ActiveForm;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\rbac\Item;
use yii\web\Response;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminUserEmailController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->name                   = "Управление email адресами";
        $this->modelShowAttribute      = "value";
        $this->modelClassName          = UserEmail::className();

        parent::init();

    }

}
