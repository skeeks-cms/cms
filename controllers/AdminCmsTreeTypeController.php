<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeType;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsTreeTypeController
 * @package skeeks\cms\controllers
 */
class AdminCmsTreeTypeController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                   = "Настройки разделов";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsTreeType::className();

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
                    "gridConfig" =>
                    [
                        'settingsData' =>
                        [
                            'order' => SORT_ASC,
                            'orderBy' => "priority",
                        ]
                    ],

                    "columns" => [
                        'name',
                        'code',

                        [
                            'class'         => DataColumn::className(),
                            'label'         => \Yii::t('skeeks/cms', 'Number of sections'),
                            'value'     => function(CmsTreeType $cmsTreeType)
                            {
                                return $cmsTreeType->getCmsTrees()->count();
                            }
                        ],

                        [
                            'class'         => \skeeks\cms\grid\BooleanColumn::className(),
                            'attribute'     => 'active'
                        ],
                        'priority',
                    ],
                ],

                "activate-multi" =>
                [
                    'class' => AdminMultiModelEditAction::className(),
                    "name" => "Активировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiActivate'],
                ],

                "inActivate-multi" =>
                [
                    'class' => AdminMultiModelEditAction::className(),
                    "name" => "Деактивировать",
                    //"icon"              => "glyphicon glyphicon-trash",
                    "eachCallback" => [$this, 'eachMultiInActivate'],
                ]
            ]
        );
    }

}
