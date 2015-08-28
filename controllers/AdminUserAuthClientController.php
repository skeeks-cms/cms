<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\user\UserEmail;
use skeeks\cms\models\UserAuthClient;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use yii\helpers\ArrayHelper;

/**
 * Class AdminUserAuthClientController
 * @package skeeks\cms\controllers
 */
class AdminUserAuthClientController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление социальными профилями";
        $this->modelShowAttribute      = "displayName";
        $this->modelClassName          = UserAuthClient::className();

        parent::init();

    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [

            'index' =>
            [
                "columns"      => [
                    'displayName',

                    [
                        'class'         => \skeeks\cms\grid\UserColumnData::className(),
                        'attribute'     => "user_id"
                    ],

                    [
                        'class'         => \skeeks\cms\grid\DateTimeColumnData::className(),
                        'attribute'     => "created_at"
                    ],


                ],
            ],

            'create' =>
            [
                'visible'    => false
            ]
        ]);
    }

}
