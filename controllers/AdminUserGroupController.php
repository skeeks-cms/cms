<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\UserGroup;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;

/**
 * Class AdminUserGroupController
 * @package skeeks\cms\controllers
 */
class AdminUserGroupController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление группами пользователей";
        $this->modelShowAttribute      = "groupname";
        $this->modelClassName          = UserGroup::className();

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

        $controller = \Yii::$app->cms->moduleCms()->createControllerByID("admin-user");

        return $this->output(\Yii::$app->cms->moduleCms()->renderFile("admin-user/index_.php", [
            'searchModel'   => $searchModel,
            'dataProvider'  => $dataProvider,
            'controller'    => $controller,
        ]));
    }
}
