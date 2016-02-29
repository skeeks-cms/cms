<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */
namespace skeeks\cms\controllers;

use skeeks\cms\models\CmsTreeTypeProperty;
use skeeks\cms\models\CmsUserUniversalProperty;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use yii\helpers\ArrayHelper;

/**
 * Class AdminCmsTreeTypePropertyController
 * @package skeeks\cms\controllers
 */
class AdminCmsUserUniversalPropertyController extends AdminModelEditorController
{
    public function init()
    {
        $this->name                   = "Управление свойствами пользователя";
        $this->modelShowAttribute      = "name";
        $this->modelClassName          = CmsUserUniversalProperty::className();

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
                    'columns' =>
                    [
                        'name',
                        'code',
                        [
                            'class' => \skeeks\cms\grid\BooleanColumn::className(),
                            'falseValue' => \skeeks\cms\components\Cms::BOOL_N,
                            'trueValue' => \skeeks\cms\components\Cms::BOOL_Y,
                            'attribute' => 'active'
                        ],
                    ],
                ],

                "update" =>
                [
                    "modelScenario" => RelatedPropertyModel::SCENARIO_UPDATE_CONFIG,
                ],

                "create" =>
                [
                    "modelScenario" => RelatedPropertyModel::SCENARIO_UPDATE_CONFIG,
                ],
            ]
        );
    }

}
