<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\models\CmsSiteAddressEmail;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\NumberField;
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
                            'class'        => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-site-address-email',
                            'attribute'    => 'value',
                            'content'      => function (CmsSiteAddressEmail $model) {
                                $content = Html::tag('span', Html::encode($model->value), [
                                    'class' => 'sx-collection-cell__primary',
                                ]);

                                if ($model->name) {
                                    $content .= Html::tag('span', '('.Html::encode($model->name).')', [
                                        'class' => 'sx-collection-cell__secondary',
                                    ]);
                                }

                                return $content;
                            },
                            'linkOptions' => ['class' => 'sx-collection-cell sx-collection-cell--stack'],
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
                'class' => NumberField::class,
            ],
        ];

        return $result;
    }

}
