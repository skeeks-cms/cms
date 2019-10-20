<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsContentPropertyEnum;
use skeeks\yii2\form\fields\SelectField;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsContentPropertyEnumController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Managing property values');
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsContentPropertyEnum::class;

        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'filters' => [
                    'visibleFilters' => [
                        'value',
                        'property_id',
                    ],
                ],
                'grid'    => [
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'value',
                        'property_id',
                        'code',
                        'priority',
                    ],
                    'columns' => [
                        'value' => [
                            'attribute' => "value",
                            'format'    => "raw",
                            'value'     => function (CmsContentPropertyEnum $model) {
                                return Html::a($model->value, "#", [
                                    'class' => "sx-trigger-action",
                                ]);
                            },
                        ],
                    ]
                ],
            ],
            'create' => [
                'fields' => [$this, 'updateFields'],
            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
            ],
        ]);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsTreeTypeProperty
         */
        $model = $action->model;
        //$model->load(\Yii::$app->request->get());

        return [
            'property_id' => [
                'class' => SelectField::class,
                'items' => function() {
                    return \yii\helpers\ArrayHelper::map(
                        \skeeks\cms\models\CmsContentProperty::find()->all(),
                        "id",
                        "name"
                    );
                }
            ],
            'value',
            'code',
            'priority',
        ];
    }
}
