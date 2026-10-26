<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\models\CmsClientAccess;
use skeeks\cms\models\CmsDealRole;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\admin\DealAccessLevelsWidget;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\TextareaField;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * «Роли доступа клиентов» — ready-made roles of client people ("Сделка как услуга").
 */
class AdminCmsDealRoleController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Роли доступа клиентов";
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsDealRole::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [
                'grid' => [
                    'defaultOrder'   => [
                        'priority' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'name',
                        'levels',
                        'people',
                        'is_active',
                    ],
                    'columns'        => [
                        'name'      => [
                            'class' => DefaultActionColumn::class,
                        ],
                        'levels'    => [
                            'label' => 'Области доступа',
                            'value' => function (CmsDealRole $model) {
                                return $model->getLevelsAsText();
                            },
                        ],
                        'people'    => [
                            'label' => 'Людей с ролью',
                            'value' => function (CmsDealRole $model) {
                                return (int)CmsClientAccess::find()->andWhere(['cms_deal_role_id' => $model->id])->count();
                            },
                        ],
                        'is_active' => [
                            'class' => BooleanColumn::class,
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
    }

    public function updateFields($action)
    {
        /** @var CmsDealRole $model */
        $model = $action->model;

        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Роль',
                'fields' => [
                    'name',
                    'description' => [
                        'class' => TextareaField::class,
                    ],
                    'is_active'   => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'priority'    => [
                        'class' => NumberField::class,
                    ],
                ],
            ],
            'areas' => [
                'class'  => FieldSet::class,
                'name'   => 'Области доступа',
                'fields' => [
                    'is_full' => [
                        'class'       => BoolField::class,
                        'allowNull'   => false,
                        'formElement' => BoolField::ELEMENT_CHECKBOX,
                    ],
                    [
                        'class'   => HtmlBlock::class,
                        'content' => DealAccessLevelsWidget::widget([
                                'name'        => Html::getInputName($model, 'levels'),
                                'levels'      => $model->getLevels(),
                                'fullInputId' => Html::getInputId($model, 'is_full'),
                            ])
                            .Html::tag('p', 'Изменение роли не меняет права людей, которым она уже назначена.', ['class' => 'sx-text-muted']),
                    ],
                ],
            ],
        ];
    }
}
