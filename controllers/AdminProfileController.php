<?php
/**
 * AdminProfileController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\controllers;

use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\models\UserGroup;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorSmartController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;

/**
 * Class AdminProfileController
 * @package skeeks\cms\controllers
 */
class AdminProfileController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Личный кабинет";
        $this->_modelShowAttribute      = "username";
        $this->_modelClassName          = User::className();
        parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['delete']);
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['social']);
        unset($behaviors[self::BEHAVIOR_ACTION_MANAGER]['actions']['system']);

        return $behaviors;
    }




    /**
     * @return \common\models\User|null|\skeeks\cms\base\db\ActiveRecord|User|\yii\web\User
     */
    public function getCurrentModel()
    {
        if ($this->_currentModel === null)
        {
            $this->_currentModel = \Yii::$app->cms->getAuthUser();
        }

        return $this->_currentModel;
    }

    public function actionIndex()
    {
        return $this->redirect(UrlHelper::construct("cms/admin-profile/view"));
    }


}
