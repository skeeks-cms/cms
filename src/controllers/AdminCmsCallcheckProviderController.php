<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\DefaultActionColumn;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCallcheckProvider;
use skeeks\cms\models\CmsSiteEmail;
use skeeks\cms\models\CmsSitePhone;
use skeeks\cms\models\CmsSiteSocial;
use skeeks\cms\models\CmsSmsProvider;
use skeeks\cms\query\CmsActiveQuery;
use skeeks\yii2\form\Builder;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\base\Exception;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsCallcheckProviderController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Авторизация по звонку");
        $this->modelShowAttribute = 'asText';
        $this->modelClassName = CmsCallcheckProvider::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-settings';

        parent::init();
    }

    /**
     * @inheritdoc
     */
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
<p>Для авторизации на сайте по звонку, нужно настроить посредника, который будет осуществлять это действие!</p>
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
                         * @var $query CmsActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $query->cmsSite();
                    },


                    'defaultOrder' => [
                        'priority' => SORT_ASC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',

                        'custom',
                        'priority',
                        'is_main',
                    ],
                    'columns'        => [
                        'custom' => [
                            'attribute' => 'name',
                            'format'    => "raw",
                            'class' => DefaultActionColumn::class
                        ],
                        'is_main' => [
                            'class' => BooleanColumn::class,
                            'trueValue' => true,
                            'falseValue' => false,
                        ],
                    ],
                ],
            ],
            "create" => [
                'fields' => [$this, 'updateFields'],
                'on beforeSave' => function (Event $e) {
                    /**
                     * @var $action BackendModelUpdateAction;
                     * @var $model CmsUser;
                     */
                    $action = $e->sender;
                    $model = $action->model;
                    $action->isSaveFormModels = false;

                    if (isset($action->formModels['handler'])) {
                        $handler = $action->formModels['handler'];
                        $model->component_config = $handler->toArray();
                    }

                    if ($model->save()) {
                        //$action->afterSaveUrl = Url::to(['update', 'pk' => $newModel->id, 'content_id' => $newModel->content_id]);
                    } else {
                        throw new Exception(print_r($model->errors, true));
                    }

                },
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
                'on beforeSave' => function (Event $e) {
                    /**
                     * @var $action BackendModelUpdateAction;
                     * @var $model CmsUser;
                     */
                    $action = $e->sender;
                    $model = $action->model;
                    $action->isSaveFormModels = false;

                    if (isset($action->formModels['handler'])) {
                        $handler = $action->formModels['handler'];
                        $model->component_config = $handler->toArray();
                    }


                    if ($model->save()) {
                        //$action->afterSaveUrl = Url::to(['update', 'pk' => $newModel->id, 'content_id' => $newModel->content_id]);
                    } else {
                        throw new Exception(print_r($model->errors, true));
                    }

                },

            ],
        ]);

        return $actions;
    }

    public function updateFields($action)
    {
        $handlerFields = [];
        /**
         * @var $handler DeliveryHandlerComponent
         */
        if ($action->model && $action->model->handler) {
            $handler = $action->model->handler;
            $handlerFields = $handler->getConfigFormFields();
            $handlerFields = Builder::setModelToFields($handlerFields, $handler);

            $action->formModels['handler'] = $handler;
            if ($post = \Yii::$app->request->post()) {
                $handler->load($post);
            }
        }

        if (!$action->model->isNewRecord) {
            \Yii::$app->view->registerCss(<<<CSS
.field-cmssmsprovider-component {
    display: none;
}
CSS
            );
        }

        $result = [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => \Yii::t('skeeks/shop/app', 'Main'),
                'fields' => [

                    'is_main' => [
                        'class'     => BoolField::class,
                        'allowNull' => false,
                    ],
                    'name',
                    'priority',


                    'component' => [
                        'class'   => SelectField::class,
                        'items'   => \Yii::$app->cms->getCallcheckHandlersForSelect(),
                        'elementOptions' => [
                            RequestResponse::DYNAMIC_RELOAD_FIELD_ELEMENT => "true",
                        ],
                    ],

                ],
            ],
        ];

        if ($handlerFields) {
            $result = ArrayHelper::merge($result, [
                'handler' => [
                    'class'  => FieldSet::class,
                    'name'   => "Настройки обработчика",
                    'fields' => $handlerFields
                ]
            ]);
        }


        return $result;
    }

}
