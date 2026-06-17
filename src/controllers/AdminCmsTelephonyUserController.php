<?php
/**
 * @author Semenov Alexander
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\UserColumnData;
use skeeks\cms\models\CmsTelephonyCall;
use skeeks\cms\models\CmsTelephonyProvider;
use skeeks\cms\models\CmsTelephonyUser;
use skeeks\cms\models\CmsUser;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;

class AdminCmsTelephonyUserController extends BackendModelStandartController
{
    public function init()
    {
        /*$model = new CmsTelephonyUser();
        print_r($model->toArray());die;*/
        $this->name = \Yii::t('skeeks/cms', "Пользователи телефонии");
        $this->modelShowAttribute = 'asText';
        $this->modelClassName = CmsTelephonyUser::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            'index'  => [
                'on beforeRender' => function (Event $e) {
                    $e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],
                        'body' => <<<HTML
<p>
В этом разделе отображается пользователи телефонии и сязь с сотрудниками CRM
</p>
HTML
                        ,
                    ]);
                },

                "backendShowings" => false,
                "filters"         => false,

                'grid'            => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         */
                        $query = $e->sender->dataProvider->query;

                        // при необходимости можно ограничить по сайту / пользователю
                        // $query->cmsSite();
                    },

                    'defaultOrder' => [
                        'created_at' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',

                        'cms_worker_user_id',
                        'provider_user_num',
                        'cms_telephony_provider_id',
                        'is_active',
                    ],

                    'columns' => [

                        /*'cms_worker_user_id' => [
                            "class" => UserColumnData::class,
                        ],*/
                        'cms_worker_user_id' => [
                            'format' => 'raw',
                            'value' => function (CmsTelephonyUser $model) {

                                if (!$model->workerUser) {
                                    return "";
                                }
                                return \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                    'user'    => $model->workerUser,
                                    'isSmall' => true,
                                ]);
                            }
                        ],


                        'cms_telephony_provider_id' => [
                            'format' => 'raw',
                            'value' => function (CmsTelephonyUser $model) {
                                return $model->provider->name;
                            }
                        ],


                        'created_at' => [
                            'class' => DateTimeColumnData::class
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

        return $actions;
    }

    public function updateFields($action)
    {
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $result = [

            'telephony_provider' => [
                'class'  => FieldSet::class,
                'name'   => 'Данные провайдера',
                'fields' => [
                    'cms_telephony_provider_id' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsTelephonyProvider::class,
                            'multiple'    => false,
                        ],
                    ],
                    'provider_user_num',
                ]
            ],

            'crm' => [
                'class'  => FieldSet::class,
                'name'   => 'Данные CRM',
                'fields' => [
                    'is_active' => [
                        'class' => BoolField::class,
                    ],
                    'cms_worker_user_id' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsUser::class,
                            'multiple'    => false,
                            'searchQuery' => function ($word = '') {
                                $query = CmsUser::find()->isWorker();
                                if ($word) {
                                    if ($word) {
                                        $query->search($word);
                                    }
                                }
                                return $query;
                            },
                        ],
                    ],
                ]
            ],


            'ws' => [
                'class'  => FieldSet::class,
                'name'   => 'Работа через софтфон в браузере',
                'fields' => [
                    'ws_url',

                    'sip_uri',
                    'sip_password',
                ]
            ],

        ];

        return $result;
    }
}
