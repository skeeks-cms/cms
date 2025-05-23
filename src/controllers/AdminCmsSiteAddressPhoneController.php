<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\models\CmsSiteAddressPhone;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\NumberField;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsSiteAddressPhoneController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Телефоны адреса");
        $this->modelShowAttribute = "value";
        $this->modelClassName = CmsSiteAddressPhone::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

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
            'value'    => [
                'elementOptions'  => [
                    'placeholder' => '+7 903 722-28-73',
                ],
                'on beforeRender' => function (Event $e) {
                    /**
                     * @var $field Field
                     */
                    $field = $e->sender;
                    \skeeks\cms\admin\assets\JqueryMaskInputAsset::register(\Yii::$app->view);
                    $id = \yii\helpers\Html::getInputId($field->model, $field->attribute);
                    \Yii::$app->view->registerJs(<<<JS
                        $("#{$id}").mask("+7 999 999-99-99",{autoclear: false});
JS
                    );
                },
            ],
            'name',
            'priority' => [
                'class' => NumberField::class,
            ],
        ];

        return $result;
    }

}
