<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsUserUniversalPropertyEnum;
use skeeks\yii2\form\fields\SelectField;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsUserUniversalPropertyEnumController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление значениями свойств пользователя";
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsUserUniversalPropertyEnum::class;

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
                        'property_id'
                    ]
                ],
                'grid' => [
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'property_id',
                        'value',
                        'code',
                        'priority',
                    ]
                ]
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
        $model->load(\Yii::$app->request->get());

        if ($property_id = \Yii::$app->request->get("property_id")) {
            $model->property_id = $property_id;
        }
        return [
            'property_id' => [
                'class' => SelectField::class,
                'items' => function() {
                    return \yii\helpers\ArrayHelper::map(
                        \skeeks\cms\models\CmsUserUniversalProperty::find()->all(),
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
