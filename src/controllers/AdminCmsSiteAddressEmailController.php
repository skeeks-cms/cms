<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsSiteAddressEmail;
use skeeks\cms\models\CmsSiteEmail;
use skeeks\cms\models\CmsSitePhone;
use skeeks\yii2\form\fields\NumberField;
use yii\base\Event;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteAddressEmailController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Email сайта");
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsSiteAddressEmail::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-cms-site-address-email';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'index'  => [


                "backendShowings" => false,
                "filters"         => false,
                'grid'            => [


                    'defaultOrder' => [
                        'priority' => SORT_ASC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'priority',
                    ],
                    'columns'        => [
                        'custom' => [
                            'attribute' => 'value',
                            'format'    => "raw",
                            'value'     => function ($model) {
                                $data[] = Html::a($model->value, "#", [
                                    'class' => "sx-trigger-action",
                                    'style' => "font-size: 18px;",
                                ]);

                                if ($model->name) {
                                    $data[] = "<span style='color: gray;'>(".$model->name.")</span>";
                                }

                                return implode(" ", $data);
                            },
                        ],
                    ],
                ],
            ],
            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],
        ]);

        return $actions;
    }

    public function updateFields($action)
    {
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [
            'value',
            'name',
            'priority' => [
                'class' => NumberField::class
            ],
        ];

        return $result;
    }

}
