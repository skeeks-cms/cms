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
                            'value'     => function (CmsTreeTypePropertyEnum $model) {
                                return Html::a($model->value, "#", [
                                    'class' => "sx-trigger-action",
                                ]);
                            },
                        ],
                    ]
                ],
            ],
        ]);
    }

}
