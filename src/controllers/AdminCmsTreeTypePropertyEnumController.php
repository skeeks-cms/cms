<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsTreeTypePropertyEnum;
use skeeks\yii2\form\fields\SelectField;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTreeTypePropertyEnumController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Managing partition property values');
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsTreeTypePropertyEnum::class;

        $this->generateAccessActions = false;

        $this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return \Yii::$app->user->can($this->uniqueId);
        };

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
                    'columns'        => [
                        'value' => [
                            'attribute' => "value",
                            'format'    => "raw",
                            'value'     => function (CmsTreeTypePropertyEnum $model) {
                                return Html::a($model->value, "#", [
                                    'class' => "sx-trigger-action",
                                ]);
                            },
                        ],
                    ],
                ],
            ],

            'create' => [
                'fields' => [$this, 'fields'],
            ],
            'update' => [
                'fields' => [$this, 'fields'],
            ],
        ]);
    }

    public function fields($action)
    {
        $model = $action->model;
        $model->load(\Yii::$app->request->get());
        
        if ($property_id = \Yii::$app->request->get('property_id')) {
            $model->property_id = $property_id;
        }
        return [
            'property_id' => [
                'class' => SelectField::class,
                'items' => \yii\helpers\ArrayHelper::map(
                    \skeeks\cms\models\CmsTreeTypeProperty::find()->all(),
                    "id",
                    "name"
                ),
            ],
            'value',
            'code',
            'priority',
        ];
    }
}
