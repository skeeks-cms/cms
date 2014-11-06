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

use skeeks\cms\App;
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
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminUserGroupController extends AdminModelEditorSmartController
{
    public function init()
    {
        $this->_label                   = "Управление группами пользователей";
        $this->_modelShowAttribute      = "groupname";
        $this->_modelClassName          = UserGroup::className();
        parent::init();
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            self::BEHAVIOR_ACTION_MANAGER =>
            [
                "actions" =>
                [
                    'users' =>
                    [
                        "label" => "Пользователи",
                        "rules" =>
                        [
                            [
                                "class" => HasModel::className()
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function actionUsers()
    {
        $search = new Search(User::className());
        $dataProvider   = $search->search(\Yii::$app->request->queryParams);
        $searchModel    = $search->getLoadedModel();

        $dataProvider->query->andWhere(["group_id" => $this->getCurrentModel()->id]);

        $controller = App::moduleCms()->createControllerByID("admin-user");

        return $this->output(App::moduleCms()->renderFile("admin-user/index.php", [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $controller,
        ]));
    }
}
