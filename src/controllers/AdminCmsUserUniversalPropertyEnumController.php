<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsUserUniversalPropertyEnum;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
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
                        'property_id',
                    ],
                ],
                'grid'    => [
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        'property_id',
                        'value',
                        'code',
                        'priority',
                    ],
                ],
            ],

            'create' => [
                'size'    => BackendAction::SIZE_SMALL,
                'fields'  => [$this, 'updateFields'],
                'buttons' => ['save'],
            ],
            'update' => [
                'size'    => BackendAction::SIZE_SMALL,
                'fields'  => [$this, 'updateFields'],
                'buttons' => ['save'],
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

        $result = [

            'main'        => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/cms', 'Основное'),
                'fields' => [
                    'value',
                ],
            ],
            'additionals' => [
                'class'          => FieldSet::class,
                'name'           => \Yii::t('skeeks/cms', 'Additionally'),
                'elementOptions' => ['isOpen' => false],
                'fields'         => [

                    'code',

                    [
                        'class'   => HtmlBlock::class,
                        'content' => "<div style='display: none;'>",
                    ],
                    'property_id',
                    [
                        'class'   => HtmlBlock::class,
                        'content' => "</div>",
                    ],

                    'priority' => [
                        'class' => NumberField::class,
                    ],


                ],
            ],
        ];

        return $result;
    }

}
