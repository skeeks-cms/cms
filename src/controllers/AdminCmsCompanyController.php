<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\ContextMenuControllerActionsWidget;
use skeeks\cms\backend\widgets\ControllerActionsWidget;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompany2Contractor;
use skeeks\cms\models\CmsCompany2user;
use skeeks\cms\models\CmsCompanyCategory;
use skeeks\cms\models\CmsCompanyStatus;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\queries\CmsCompanyQuery;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\dadataClient\models\PartyModel;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\base\Exception;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsCompanyController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Компании");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsCompany::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;

        /*$this->accessCallback = function () {
            return \Yii::$app->user->can($this->uniqueId);
        };*/

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [

                'on beforeRender' => function (Event $e) {
                    return '';
                    $e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],

                        'body' => <<<HTML
<p>Добавьте компании на которые вы получаете деньги, на которые заключаете договора в этот раздел. То есть, ваши компании и ИП.</p>
HTML
                        ,
                    ]);
                },

                "filters" => [
                    'visibleFilters' => [
                        'q',
                        'status',
                        'categories',
                        'custom',
                    ],
                    "filtersModel"   => [
                        'rules'            => [
                            ['q', 'safe'],
                            ['status', 'safe'],
                            ['categories', 'safe'],
                            ['custom', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'status',
                            'categories',
                            'custom',
                        ],

                        'fields' => [
                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Название, телефон, email, ИНН...',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query CmsCompanyQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {

                                        $logsQuery = CmsLog::find()->andWhere(['like', 'comment', $e->field->value])->andWhere(['model_code' => CmsCompany::class])->select(["model_id"]);

                                        $query->search($e->field->value);

                                        $query->orWhere([
                                            CmsCompany::tableName().'.id' => $logsQuery,
                                        ]);
                                    }
                                },
                            ],

                            'custom' => [
                                'class'    => SelectField::class,
                                'label'    => "Готовый фильтр",
                                'items'    => [
                                    'overdue_deals'  => 'Есть просроченные сделки',
                                    'has_tasks'      => 'Есть дела и задачи',
                                    'has_tasks_for_me'      => 'Есть дела и задачи для меня',
                                    'not_paid_bills' => 'Есть неоплаченные счета',
                                ],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value == 'overdue_deals') {

                                        $deals = CmsDeal::find()
                                            ->select([
                                                'count(id) as total',
                                            ])
                                            ->andWhere(['<', 'end_at', time()])
                                            ->andWhere(['is_active' => 1])
                                            ->andWhere([
                                                'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                            ]);


                                        $query->addSelect([
                                            'countDealsFilter' => $deals,
                                        ]);

                                        $query->andHaving([
                                            '>',
                                            'countDealsFilter',
                                            0,
                                        ]);
                                    } elseif ($e->field->value == 'has_tasks') {
                                        $deals = CmsTask::find()
                                            ->select([
                                                'count(id) as total',
                                            ])
                                            ->status([
                                                CmsTask::STATUS_ON_PAUSE,
                                                CmsTask::STATUS_ON_CHECK,
                                                CmsTask::STATUS_ACCEPTED,
                                                CmsTask::STATUS_IN_WORK,
                                                CmsTask::STATUS_NEW,
                                            ])
                                            ->andWhere([
                                                'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                            ]);


                                        $query->addSelect([
                                            'countTasksFilter' => $deals,
                                        ]);

                                        $query->andHaving([
                                            '>',
                                            'countTasksFilter',
                                            0,
                                        ]);
                                    } elseif ($e->field->value == 'has_tasks_for_me') {
                                        $deals = CmsTask::find()
                                            ->select([
                                                'count(id) as total',
                                            ])
                                            ->executor(\Yii::$app->user->identity)
                                            ->status([
                                                CmsTask::STATUS_ON_PAUSE,
                                                CmsTask::STATUS_ON_CHECK,
                                                CmsTask::STATUS_ON_CHECK,
                                                CmsTask::STATUS_ACCEPTED,
                                                CmsTask::STATUS_IN_WORK,
                                                CmsTask::STATUS_NEW,
                                            ])
                                            ->andWhere([
                                                'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                            ]);


                                        $query->addSelect([
                                            'countTasksMeFilter' => $deals,
                                        ]);

                                        $query->andHaving([
                                            '>',
                                            'countTasksMeFilter',
                                            0,
                                        ]);
                                    } elseif ($e->field->value == 'not_paid_bills') {
                                        $deals = ShopBill::find()
                                            ->select([
                                                'count(id) as total',
                                            ])
                                            ->andWhere(['closed_at' => null])
                                            ->andWhere(['paid_at' => null])
                                            ->andWhere([
                                                'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                            ]);


                                        $query->addSelect([
                                            'countNotPaidFilter' => $deals,
                                        ]);

                                        $query->andHaving([
                                            '>',
                                            'countNotPaidFilter',
                                            0,
                                        ]);
                                    }
                                },
                            ],

                            'status'     => [

                                'label'        => 'Статус',
                                'class'        => WidgetField::class,
                                'widgetClass'  => AjaxSelectModel::class,
                                'widgetConfig' => [
                                    'modelClass' => CmsCompanyStatus::class,
                                    'multiple'   => true,
                                ],
                                'on apply'     => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query CmsCompanyQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->andWhere(['in', 'cms_company_status_id', $e->field->value]);
                                    }
                                },
                            ],
                            'categories' => [

                                'label'        => 'Категория',
                                'class'        => WidgetField::class,
                                'widgetClass'  => AjaxSelectModel::class,
                                'widgetConfig' => [
                                    'modelClass' => CmsCompanyCategory::class,
                                    'multiple'   => true,
                                ],
                                'on apply'     => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query CmsCompanyQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->joinWith("categories as categories");
                                        $query->andWhere(['in', 'categories.id', $e->field->value]);
                                    }
                                },
                            ],


                        ],
                    ],
                ],

                'grid' => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query CmsCompanyQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $query->forManager();
                    },

                    'defaultOrder' => [
                        'id' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'managers',
                        'countDeals',
                        'countNotPaidBills',
                        'countTasks',
                        /*'contacts',
                        'users',*/
                        //'code',
                        'is_active',
                        'priority',
                    ],

                    'columns' => [
                        'custom'   => [
                            'format'    => 'raw',
                            'attribute' => 'name',
                            'value'     => function (CmsCompany $model) {

                                $data = [];
                                $data[] = Html::a($model->asText, "#", [
                                    'class' => 'sx-trigger-action',
                                    'style' => 'font-size: 15px;
                                                display: block;',
                                ]);

                                $additionalData = [];
                                if ($model->emails) {
                                    $additionalData[] = $model->emails[0]->value;
                                }
                                if ($model->phones) {
                                    $additionalData[] = $model->phones[0]->value;
                                }
                                if ($model->links) {
                                    $url = $model->links[0]->url;
                                    $additionalData[] = parse_url($url, PHP_URL_HOST);
                                }

                                if ($additionalData) {
                                    $data[] = implode(" / ", $additionalData);
                                }
                                $info = implode("", $data);

                                return "<div class='row no-gutters'>
                                                <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none;
    border-bottom: 0;
    width: 54px;
    border-radius: 50%;
    border: 2px solid #ededed;
    height: 54px;
    
    display: flex;
    overflow: hidden;'>
                                                    <img src='".($model->cmsImage ? \Yii::$app->imaging->thumbnailUrlOnRequest($model->cmsImage->src,
                                        new \skeeks\cms\components\imaging\filters\Thumbnail([
                                            'h' => 50,
                                            'w' => 50,
                                            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
                                        ])) : Image::getCapSrc())."' style='    max-width: 50px;
    max-height: 50px;
    border-radius: 50%;
    width: 100%;
    height: 100%;
    margin: auto;' />
                                                </a>
                                                </div>
                                                <div class='my-auto' style='margin-left: 10px; line-height: 1.4;' class='my-auto''>".$info."</div></div>";;
                            },
                        ],
                        'managers' => [
                            'format' => 'raw',
                            'label'  => 'Сотрудники',
                            'value'  => function (CmsCompany $model) {

                                $data = [];
                                if ($model->managers) {
                                    foreach ($model->managers as $manager) {
                                        $data[] = \skeeks\cms\widgets\admin\CmsWorkerViewWidget::widget([
                                            'user'    => $manager,
                                            'isSmall' => true,
                                        ]);
                                    }
                                }


                                $info = implode("", $data);

                                return $info;
                            },
                        ],
                        'users'    => [
                            'format' => 'raw',
                            'label'  => 'Контакты',
                            'value'  => function (CmsCompany $model) {

                                $data = [];

                                $info = implode(", ", ArrayHelper::map($model->users, "id", "shortDisplayName"));

                                return $info;
                            },
                        ],


                        'countDeals' => [
                            'format'               => 'raw',
                            'label'                => 'Просроченные сделки',
                            'attribute'            => 'countDeals',
                            'value'                => function (CmsCompany $CmsTask) {
                                if ($CmsTask->raw_row['countDeals']) {
                                    return "<span style='color: var(--color-red);'>".\Yii::$app->formatter->asInteger($CmsTask->raw_row['countDeals'])." шт.</span>";
                                } else {
                                    return "";
                                }
                            },
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $deals = CmsDeal::find()
                                    ->select([
                                        'count(id) as total',
                                    ])
                                    ->andWhere(['<', 'end_at', time()])
                                    ->andWhere(['is_active' => 1])
                                    ->andWhere([
                                        'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                    ]);


                                $query->addSelect([
                                    'countDeals' => $deals,
                                ]);

                                $gridView->sortAttributes['countDeals'] = [
                                    'asc'     => ['countDeals' => SORT_ASC],
                                    'desc'    => ['countDeals' => SORT_DESC],
                                    'label'   => '',
                                    'default' => SORT_ASC,
                                ];
                            },
                        ],

                        'countNotPaidBills' => [
                            'format'               => 'raw',
                            'label'                => 'Неоплаченные счета',
                            'attribute'            => 'countDeals',
                            'value'                => function (CmsCompany $CmsTask) {
                                if ($CmsTask->raw_row['countNotPaidBills']) {
                                    return "<span style='color: var(--color-red);'>".\Yii::$app->formatter->asInteger($CmsTask->raw_row['countNotPaidBills'])." шт.</span>";
                                } else {
                                    return "";
                                }
                            },
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $deals = ShopBill::find()
                                    ->select([
                                        'count(id) as total',
                                    ])
                                    ->andWhere(['closed_at' => null])
                                    ->andWhere(['paid_at' => null])
                                    ->andWhere([
                                        'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                    ]);


                                $query->addSelect([
                                    'countNotPaidBills' => $deals,
                                ]);


                                $gridView->sortAttributes['countNotPaidBills'] = [
                                    'asc'     => ['countNotPaidBills' => SORT_ASC],
                                    'desc'    => ['countNotPaidBills' => SORT_DESC],
                                    'label'   => '',
                                    'default' => SORT_ASC,
                                ];
                            },
                        ],


                        'countTasks' => [
                            'format'               => 'raw',
                            'label'                => 'Задачи',
                            'attribute'            => 'countTasks',
                            'value'                => function (CmsCompany $CmsTask) {
                                if ($CmsTask->raw_row['countTasks']) {
                                    return "<span>".\Yii::$app->formatter->asInteger($CmsTask->raw_row['countTasks'])." шт.</span>";
                                } else {
                                    return "";
                                }
                            },
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $deals = CmsTask::find()
                                    ->select([
                                        'count(id) as total',
                                    ])
                                    ->status([
                                        CmsTask::STATUS_ON_PAUSE,
                                        CmsTask::STATUS_ON_CHECK,
                                        CmsTask::STATUS_ACCEPTED,
                                        CmsTask::STATUS_IN_WORK,
                                        CmsTask::STATUS_NEW,
                                    ])
                                    ->andWhere([
                                        'cms_company_id' => new Expression(CmsCompany::tableName().".id"),
                                    ]);


                                $query->addSelect([
                                    'countTasks' => $deals,
                                ]);

                                $gridView->sortAttributes['countTasks'] = [
                                    'asc'     => ['countTasks' => SORT_ASC],
                                    'desc'    => ['countTasks' => SORT_DESC],
                                    'label'   => '',
                                    'default' => SORT_ASC,
                                ];
                            },
                        ],

                        /*'is_active' => [
                            'class' => BooleanColumn::class,
                        ],*/

                        'cms_image_id' => [
                            'class' => ImageColumn2::class,
                        ],
                    ],
                ],
            ],

            'view' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Профиль',
                'icon'     => 'fa fa-user',
                "callback" => [$this, 'view'],
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
            ],

            'add-contractor' => [
                'class'     => BackendModelAction::class,
                'isVisible' => false,
                'callback'  => [$this, 'addContractor'],
            ],

            'add-user' => [
                'class'     => BackendModelAction::class,
                'isVisible' => false,
                'callback'  => [$this, 'addUser'],
            ],

            'add-log' => [
                'class'     => BackendModelAction::class,
                'isVisible' => false,
                'callback'  => [$this, 'addLog'],
            ],

            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
            ],


            'deals' => [
                'class'    => BackendGridModelRelatedAction::class,
                'priority' => 300,
                'name'     => 'Сделки',
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
                //'priority' => 90,
                /*'callback' => [$this, 'shift'],*/
                'icon'     => 'far fa-file',

                'controllerRoute' => "/cms/admin-cms-deal",
                'relation'        => ['cms_company_id' => 'id'],
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $action->relatedIndexAction->filters = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    /*ArrayHelper::removeValue($visibleColumns, 'shop_cashebox_id');*/
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                    /*$action->relatedIndexAction->grid['on init'] = function (Event $e) {
                        $query = $e->sender->dataProvider->query;

                        $query->select([
                            CmsDeal::tableName().'.*',
                            'end_at_sort' => new Expression("IF (end_at IS NULL, '0', '1')"),
                        ]);

                        $query->forManager();
                    };*/


                },
            ],


            'bills' => [
                'class'    => BackendGridModelRelatedAction::class,
                'priority' => 350,
                'name'     => 'Счета',
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
                //'priority' => 90,
                /*'callback' => [$this, 'shift'],*/
                'icon'     => 'fas fa-credit-card',

                'controllerRoute' => "/cms/admin-cms-bill",
                'relation'        => ['cms_company_id' => 'id'],
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $action->relatedIndexAction->filters = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    /*ArrayHelper::removeValue($visibleColumns, 'shop_cashebox_id');*/
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                    /*$action->relatedIndexAction->grid['on init'] = function (Event $e) {
                        $query = $e->sender->dataProvider->query;

                        $query->select([
                            CmsDeal::tableName().'.*',
                            'end_at_sort' => new Expression("IF (end_at IS NULL, '0', '1')"),
                        ]);

                        $query->forManager();
                    };*/


                },
            ],


            'payments' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Платежи',
                'priority' => 400,
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
                'callback' => [$this, 'payments'],
                'icon'     => 'fas fa-credit-card',
            ],

            'tasks' => [
                'class'    => BackendGridModelRelatedAction::class,
                'priority' => 500,
                'name'     => 'Задачи',
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
                //'priority' => 90,
                /*'callback' => [$this, 'shift'],*/
                'icon'     => 'fas fa-list',

                'controllerRoute' => "/cms/admin-cms-task",
                'relation'        => ['cms_company_id' => 'id'],
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    /*$action->relatedIndexAction->filters = false;*/
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    /*ArrayHelper::removeValue($visibleColumns, 'shop_cashebox_id');*/

                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;



                },
            ],


            'projects' => [
                'class'    => BackendGridModelRelatedAction::class,
                'priority' => 550,
                'name'     => 'Проекты',
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
                //'priority' => 90,
                /*'callback' => [$this, 'shift'],*/
                'icon'     => 'fas fa-list',

                'controllerRoute' => "/cms/admin-cms-project",
                'relation'        => ['cms_company_id' => 'id'],
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $action->relatedIndexAction->filters = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    /*ArrayHelper::removeValue($visibleColumns, 'shop_cashebox_id');*/
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],

            "log" => [
                'class' => BackendModelLogAction::class,
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-company/manage", ['model' => $this->model]);
                },
            ],

        ]);
    }



    public function payments()
    {
        if ($controller = \Yii::$app->createController('/shop/admin-payment')) {
            /**
             * @var $controller BackendController
             * @var $indexAction BackendGridModelAction
             */
            $controller = $controller[0];
            $controller->actionsMap = [
                'index' => [
                    'configKey' => $this->action->uniqueId,
                ],
            ];

            if ($indexAction = ArrayHelper::getValue($controller->actions, 'index')) {
                $indexAction->url = $this->action->urlData;
                //$indexAction->filters = false;
                $indexAction->backendShowings = false;

                $visibleColumns = $indexAction->grid['visibleColumns'];

                ArrayHelper::removeValue($visibleColumns, 'client');
                /*ArrayHelper::removeValue($visibleColumns, 'sender_crm_contractor_id');*/
                /*ArrayHelper::removeValue($visibleColumns, 'receiver_crm_contractor_id');*/

                $indexAction->grid['visibleColumns'] = $visibleColumns;
                $indexAction->grid['columns']['actions']['isOpenNewWindow'] = true;
                $indexAction->grid['on init'] = function (Event $e) {

                    $dataProvider = $e->sender->dataProvider;
                    $dataProvider->query->forManager();

                    $dataProvider->query->andWhere([
                        'or',
                        ['cms_company_id' => $this->model->id],
                    ]);
                };


                $indexAction->on('beforeRender', function (Event $event) use ($controller) {
                    if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {


                        $actions = [];
                        $createAction->isVisible = true;
                        $createAction2 = clone $createAction;

                        /**
                         * @var $createAction BackendModelCreateAction
                         */
                        $createAction->url = ArrayHelper::merge($createAction->urlData, [
                            "ShopPayment" => [
                                'cms_company_id' => $this->model->id,
                                'is_debit'       => 1,
                            ],
                        ]);


                        $createAction->name = "Заплатили нам";


                        $createAction2->url = ArrayHelper::merge($createAction2->urlData, [
                            "ShopPayment" => [
                                'cms_company_id' => $this->model->id,
                                'is_debit'       => 0,
                            ],
                        ]);
                        $createAction2->name = "Мы заплатили";


                        $actions[] = $createAction;
                        $actions[] = $createAction2;

                        $event->content = ContextMenuControllerActionsWidget::widget([
                                'actions'         => $actions,
                                'isOpenNewWindow' => true,
                                'button'          => [
                                    'class' => 'btn btn-primary',
                                    //'style' => 'font-size: 11px; cursor: pointer;',
                                    'tag'   => 'button',
                                    'label' => 'Добавить платеж',
                                ],
                            ])."<br><br>";
                    }

                });


                return $indexAction->run();
            }
        }

        throw new ForbiddenHttpException("Нет доступа к разделу");
    }

    public function view()
    {
        return $this->render($this->action->id);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsCompany
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        if ($model->isNewRecord) {
            $model->managers = [\Yii::$app->user->id];
        }

        $mainFieldSet = [

            'name',


            'cms_image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],

            'description' => [
                /*'class' => WidgetField::class,
                'widgetClass' => Ckeditor::class*/
                'class' => TextareaField::class,
            ],


            'cms_company_status_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsCompanyStatus::class,
                    'multiple'   => false,
                    /*'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->forManager();
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },*/
                ],
            ],

            'categories' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsCompanyCategory::class,
                    'multiple'   => true,
                    /*'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->forManager();
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },*/
                ],
            ],
            'users'      => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsUser::class,
                    'multiple'    => true,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->forManager();
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },
                ],
            ],

            'managers' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsUser::class,
                    'multiple'    => true,
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

            'contractors' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass'  => CmsContractor::class,
                    'multiple'    => true,
                    'searchQuery' => function ($word = '') {
                        $query = CmsContractor::find()->forManager();
                        if ($word) {
                            if ($word) {
                                $query->search($word);
                            }
                        }
                        return $query;
                    },
                ],
            ],

        ];


        $result = [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основное',
                'fields' => $mainFieldSet,
            ],
        ];


        return $result;
    }


    public function addContractor()
    {
        $rr = new RequestResponse();

        try {
            /**
             * @var $model CmsCompany
             */
            $model = $this->model;
            if ($rr->isRequestAjaxPost()) {
                if (!$inn = trim(\Yii::$app->request->post("inn"))) {
                    throw new Exception("Инн не указан");
                }

                $q = CmsContractor::find()->typeIndividualAndLegal()->inn($inn);
                //print_r($q->createCommand()->rawSql);die;
                $contractor = $q->one();
                if ($contractor) {

                    if ($model->getCmsCompany2contractors()->andWhere(['cms_contractor_id' => $contractor->id])->exists()) {
                        throw new Exception("Эта компания уже добавлена");
                    }

                    $map = new CmsCompany2contractor();
                    $map->cms_company_id = $model->id;
                    $map->cms_contractor_id = $contractor->id;
                    if (!$map->save()) {
                        throw new Exception("Не удалось добавить компанию: ".print_r($map->errors, true));
                    }
                } else {
                    $dadata = \Yii::$app->dadataClient->suggest->findByIdParty($inn);
                    if (isset($dadata[0]) && $dadata[0]) {
                        //Создать компанию
                        $party = new PartyModel($dadata[0]);
                        $contractor = new CmsContractor();

                        $contractor->setAttributesFromDadata($party);
                        if (!$contractor->save()) {
                            throw new Exception("Не удалось создать компанию: ".print_r($contractor->errors, true));
                        }

                        $map = new CmsCompany2Contractor();
                        $map->cms_company_id = $model->id;
                        $map->cms_contractor_id = $contractor->id;
                        if (!$map->save()) {
                            throw new Exception("Не удалось добавить компанию: ".print_r($map->errors, true));
                        }
                    } else {
                        throw new Exception("Компания с таким ИНН не найдена");
                    }
                }

                $rr->success = true;
            }

        } catch (\Exception $e) {
            throw $e;
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }

    public function actionTipUsers()
    {
        $rr = new RequestResponse();

        try {
            if ($rr->isRequestAjaxPost()) {

                $userClass = \Yii::$app->user->identityClass;

                $tmpCrmContractor = new $userClass();
                $tmpCrmContractor->load(\Yii::$app->request->post());

                //TODO: Искать только среди доступных пользователей
                $tipUsers = $userClass::find();

                $wherePhone = [];
                $whereEmail = [];

                $where = [];
                $where = ['or'];
                if ($tmpCrmContractor->phone || $tmpCrmContractor->email) {
                    if ($tmpCrmContractor->phone) {
                        $tipUsers->joinWith("cmsUserPhones as cmsUserPhones");
                        $where[] = ['like', 'cmsUserPhones.value', $tmpCrmContractor->phone];
                        //$tipUsers->phone($tmpCrmContractor->phone);
                    }
                    if ($tmpCrmContractor->email) {
                        $tipUsers->joinWith("cmsUserEmails as cmsUserEmails");
                        /*$tipUsers->email($tmpCrmContractor->email);*/
                        $where[] = ['like', 'cmsUserEmails.value', $tmpCrmContractor->email];
                    }
                } else {
                    if ($tmpCrmContractor->first_name) {
                        $where[] = ['like', 'first_name', $tmpCrmContractor->first_name];
                    }
                    if ($tmpCrmContractor->last_name) {
                        $where[] = ['like', 'last_name', $tmpCrmContractor->last_name];
                    }
                    if ($tmpCrmContractor->patronymic) {
                        $where[] = ['like', 'patronymic', $tmpCrmContractor->patronymic];
                    }
                }


                $tipUsers->andWhere($where);
                $tipUsers->groupBy(CmsUser::tableName().'.id');
                $tipUsers = $tipUsers->limit(25)->all();

                $rr->data = [
                    'tipUsers' => $tipUsers,
                    'htmlTip'  => $this->renderPartial("@skeeks/cms/views/helpers/user-tip-list", [
                        'models' => $tipUsers,
                    ]),
                ];

                $rr->success = true;
            }

        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }


    public function addUser()
    {
        $rr = new RequestResponse();

        try {
            $userClass = \Yii::$app->user->identityClass;

            if ($rr->isRequestAjaxPost()) {

                $user = null;
                if ($exist_contact = \Yii::$app->request->post('sx-user-id')) {

                    $user = $userClass::find()->andWhere(['id' => $exist_contact])->one();
                }

                if ($user) {
                    if (CmsCompany2user::find()->where([
                        'cms_company_id' => $this->model->id,
                        'cms_user_id'    => $user->id,
                    ])->exists()) {
                        throw new Exception("Этот контакт уже добавлен");
                    }

                    $map = new CmsCompany2user();
                    $map->cms_company_id = $this->model->id;
                    $map->cms_user_id = $user->id;
                    if (!$map->save()) {
                        throw new Exception("Не удалось добавить контакт: ".print_r($map->errors, true));
                    }
                } else {
                    $user = new $userClass();

                    if ($user->load(\Yii::$app->request->post()) && $user->save()) {
                        //Создать компанию
                        $map = new CmsCompany2user();
                        $map->cms_company_id = $this->model->id;
                        $map->cms_user_id = $user->id;
                        if (!$map->save()) {
                            throw new Exception("Не удалось привязать контакт: ".print_r($map->errors, true));
                        }
                    } else {
                        throw new Exception("Не удалось создать контакт: ".print_r($user->errors, true));
                    }
                }

                $rr->success = true;
            }

        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }


    public function addLog()
    {
        $rr = new RequestResponse();

        try {

            if ($rr->isRequestAjaxPost()) {

                $log = new CmsLog();

                $log->log_type = CmsLog::LOG_TYPE_COMMENT;
                $log->model_code = $this->model->skeeksModelCode;
                $log->model_id = $this->model->id;

                if ($log->load(\Yii::$app->request->post()) && $log->save()) {

                } else {
                    throw new Exception("Не удалось создать: ".print_r($log->errors, true));
                }

                $rr->message = "Сохранено";
                $rr->success = true;
            }

        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }
}
