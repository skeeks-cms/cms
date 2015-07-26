<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\components\Cms;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsContent;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsContentController
 * @package skeeks\cms\controllers
 */
class AdminCmsAgentController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                     = "Управление агентами";
        $this->modelShowAttribute       = "id";
        $this->modelClassName           = CmsAgent::className();

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(),
            [
                'index' =>
                [
                    "columns"      => [
                        'id',
                        'name',
                        'description',

                        [
                            'class'         => \skeeks\cms\grid\DateTimeColumnData::className(),
                            'attribute'     => "last_exec_at"
                        ],

                        [
                            'class'         => \skeeks\cms\grid\DateTimeColumnData::className(),
                            'attribute'     => "next_exec_at"
                        ],

                        [
                            'attribute'     => "agent_interval"
                        ],

                        [
                            'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                            'attribute'     => "active"
                        ],
                    ],
                ],

                "activate-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" =>
                [
                    'class'             => AdminMultiModelEditAction::className(),
                    "name"              => "Деактивировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback"      => [$this, 'eachMultiInActivate'],
                ]
            ]
        );
    }

}
