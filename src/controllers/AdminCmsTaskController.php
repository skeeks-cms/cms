<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use kartik\datecontrol\DateControl;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTaskSchedule;
use skeeks\cms\models\CmsUser;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\admin\CmsTaskStatusWidget;
use skeeks\cms\widgets\admin\CmsTaskViewWidget;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\ckeditor\Ckeditor;
use skeeks\cms\widgets\formInputs\SmartDurationInputWidget;
use skeeks\cms\widgets\formInputs\SmartTimeInputWidget;
use skeeks\cms\widgets\GridView;
use skeeks\crm\helpers\CrmScheduleHelper;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTaskController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Задачи");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsTask::class;

        $this->permissionName = 'cms/admin-task';
        $this->generateAccessActions = false;

        $this->modelHeader = function () {
            /**
             * @var $model CmsTask
             */
            $model = $this->model;
            
            return $this->renderPartial("@skeeks/cms/views/admin-cms-task/_model_header", [
                'model' => $model
            ]);
        };
        /*$this->accessCallback = function () {
            return \Yii::$app->user->can($this->uniqueId);
        };*/

        parent::init();
    }

    static public function initQuery(ActiveQuery $query)
    {
        $query->forManager();
        $query->groupBy([CmsTask::tableName().'.id']);
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [

                'name' => 'Список задач',

                'filters' => [
                    'visibleFilters' => [
                        'q',
                        //'date',
                        'cms_project_id',
                        'cms_company_id',
                        'cms_user_id',
                        
                        'created_by',
                        'executor_id',
                        
                        'status',

                        'ready',
                    ],
                    'filtersModel'   => [
                        'rules'            => [
                            ['q', 'safe'],
                            ['ready', 'safe'],
                            //['date', 'safe'],
                        ],
                        'attributeDefines' => [
                            //'date',
                            'q',
                            'ready',
                        ],

                        'fields' => [
                            'q'      => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск (Название, описание)',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->joinWith("cmsProject");

                                        $logsQuery = CmsLog::find()->andWhere(['like', 'comment', $e->field->value])->andWhere(['model_code' => CmsTask::class])->select(["model_id"]);
                                        
                                        $query->andWhere([
                                            'or',

                                            [CmsTask::tableName().'.id' => $logsQuery],

                                            ['like', CmsTask::tableName().'.id', $e->field->value],
                                            ['like', CmsTask::tableName().'.name', $e->field->value],
                                            ['like', CmsTask::tableName().'.description', $e->field->value],
                                            
                                            ['like', CmsProject::tableName().'.id', $e->field->value],
                                            ['like', CmsProject::tableName().'.name', $e->field->value],
                                            ['like', CmsProject::tableName().'.description', $e->field->value],
                                        ]);
                                    }
                                },
                            ],
                            'status' => [
                                'defaultMode'       => FilterModeEq::ID,
                                'isAllowChangeMode' => false,
                                'field'             => [
                                    'class'    => SelectField::class,
                                    //'widgetClass' => SelectModelDialogUserWidget::class,
                                    'items'    => CmsTask::statuses(),
                                    'multiple' => true
                                    //'multiple'    => new UnsetArrayValue(),
                                ],
                            ],
                            
                            'ready' => [
                                'class'    => SelectField::class,
                                'label' => "Готовый фильтр",
                                'items' => [
                                    'my_executor' => 'Сделать мне',
                                    //'my_for_check' => 'Проверить мне',
                                    'my_created' => 'Я поставил'
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value == 'my_executor') {
                                        $query->andWhere([
                                            CmsTask::tableName().'.executor_id' => \Yii::$app->user->id,
                                        ]);
                                    } elseif ($e->field->value == 'my_created') {
                                        $query->andWhere([
                                            CmsTask::tableName().'.created_by' => \Yii::$app->user->id,
                                        ]);
                                    }
                                },
                            ],

                            'cms_project_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsProject::find()->forManager();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                            'cms_company_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsCompany::find()->forManager();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                            'cms_user_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsUser::find()->forManager();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                            'executor_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsUser::find()->isWorker();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                            'created_by' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $q = CmsUser::find()->forManager();

                                            if ($word) {
                                                $q->search($word);
                                            }

                                            return $q;
                                        },
                                    ],
                                ],
                            ],
                        ],
                    ],

                ],

                'grid' => [

                    'on init' => function ($e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        //$query = $e->sender->dataProvider->query;

                        self::initQuery($e->sender->dataProvider->query);

                        //$e->sender->dataProvider->query = $query;
                    },

                    'defaultOrder' => [
                        'sort' => SORT_ASC,
                        'id' => SORT_DESC,
                    ],
                    
                    /*'sortAttributes' => [
                        'sort' => [
                            'asc'  => ['sort' => SORT_ASC],
                            'desc' => ['sort' => SORT_DESC],
                            'name' => "Сортировка",
                        ],
                    ],*/

                    'visibleColumns' => [
                        'checkbox',
                        'actions',

                        //'crm_project_id',
                        'customName',
                        'scheduleTotalTime',
                        'status',
                        'created_by',
                        'executor_id',
                    ],

                    'columns' => [
                        'executor_id' => [
                            'value' => function (CmsTask $CmsTask) {
                                return CmsWorkerViewWidget::widget(['user' => $CmsTask->executor, 'isSmall' => true]);
                            },
                        ],

                        'scheduleTotalTime' => [
                            'format'    => 'raw',
                            'label'     => 'Отработанное время',
                            'attribute' => 'scheduleTotalTime',
                            'value'     => function (CmsTask $CmsTask) {
                                if ($CmsTask->raw_row['scheduleTotalTime']) {
                                    return CrmScheduleHelper::durationAsText($CmsTask->raw_row['scheduleTotalTime']);
                                } else {
                                    return " - ";
                                }
                            },
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $scheduleTotalTime = CmsTaskSchedule::find()->select([
                                    'SUM(end_at - start_at) as total_timestamp',
                                ])->where([
                                    'cms_task_id' => new Expression(CmsTask::tableName().".id"),
                                ]);

                                $query->addSelect([
                                    'scheduleTotalTime' => $scheduleTotalTime,
                                ]);

                                $gridView->sortAttributes['scheduleTotalTime'] = [
                                    'asc'     => ['scheduleTotalTime' => SORT_ASC],
                                    'desc'    => ['scheduleTotalTime' => SORT_DESC],
                                    'label'   => '',
                                    'default' => SORT_ASC,
                                ];
                            },
                        ],

                        'status'     => [
                            'value' => function (CmsTask $CmsTask, $key, $index) {


                                if ($CmsTask->status == CmsTask::STATUS_IN_WORK) {

                                    \Yii::$app->view->registerJs(<<<JS
$('tr[data-key={$key}]').addClass('g-bg-in-work');
JS
                                    );

                                }

                                return CmsTaskStatusWidget::widget(['task' => $CmsTask, 'isShort' => true]);
                            },
                        ],
                        'created_by' => [
                            'value' => function (CmsTask $CmsTask) {
                                return CmsWorkerViewWidget::widget(['user' => $CmsTask->createdBy, 'isSmall' => true]);
                            },
                        ],
                        'customName' => [
                            'format' => 'raw',
                            'label'  => 'Задача',
                            'value'  => function (CmsTask $CmsTask) {
                                $result = "";
                           
                                return CmsTaskViewWidget::widget(['task' => $CmsTask]);
                            },

                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $work = CmsTask::STATUS_IN_WORK;
                                $ready = CmsTask::STATUS_READY;
                                $canceled = CmsTask::STATUS_CANCELED;
                                $pause = CmsTask::STATUS_ON_PAUSE;

                                $query->addSelect([
                                    new Expression("
                                    (CASE
                                        WHEN status = '{$work}'
                                            THEN 1
                                        WHEN status = '{$pause}'
                                            THEN 2
                                        WHEN status = '{$ready}'
                                            THEN 4
                                        WHEN status = '{$canceled}'
                                            THEN 5
                                        ELSE 3
                                    END) AS sort
                                    ")
                                ]);

                                $gridView->sortAttributes['sort'] = [
                                    'asc'  => ['sort' => SORT_ASC],
                                    'desc' => ['sort' => SORT_DESC],
                                    'name' => "Сортировка",
                                ];
                            },

                        ],
                    ],
                ],
            ],

            'calendar' => [
                'class'    => ViewBackendAction::class,
                'name' => 'Мой календарь',
                'icon' => 'fa fa-calendar',
                'priority' => 100,
            ],


            'view' => [
                'class'    => BackendModelAction::class,
                'name'     => "Карточка задачи",
                'icon'     => 'fa fa-eye',
                'callback' => [$this, 'view'],
                'priority' => 50,

                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-task/manage", ['model' => $this->model]);
                },
            ],


            "create" => [
                'fields' => [$this, 'updateFields'],
                'priority' => 200,
            ],

            "update" => [
                'fields' => [$this, 'updateFields'],
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-task/manage", ['model' => $this->model]);
                },
            ],

            "log" => [
                'class' => BackendModelLogAction::class,
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-task/manage", ['model' => $this->model]);
                },
            ],

            'schedule' => [
                'class'    => BackendGridModelRelatedAction::class,
                'priority' => 500,
                'name'     => 'Рабочее время',
                //'priority' => 90,
                /*'callback' => [$this, 'shift'],*/
                'icon'           => 'far fa-clock',

                'controllerRoute' => "/cms/admin-cms-task-schedule",
                'relation'        => ['cms_task_id' => 'id'],
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-task/manage", ['model' => $this->model]);
                },
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

        ]);
    }


    public function view()
    {
        return $this->render($this->action->id);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsTask
         */
        $model = $action->model;

        if ($model->isNewRecord) {
            $model->executor_id = \Yii::$app->user->id;
        }

        $model->load(\Yii::$app->request->get());




        $result = [
            'name',

            'executor_id'   => [
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



            'description' => [
                /*'class'          => TextareaField::class,
                'elementOptions' => [
                    'rows' => 10,
                ],*/
                'class'        => WidgetField::class,
                'widgetClass'  => Ckeditor::class,
                'widgetConfig' => [
                    'options' => ['rows' => 20],
                    'preset'  => 'htmlmixed',
                ],
            ],

            'fileIds' => [
                'class' => WidgetField::class,
                'widgetClass' => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'multiple' => true
                ],
            ],


            'plan_duration' => [
                'class'  => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class
            ],
            /*'plan_duration' => [
                'class'  => NumberField::class,
                'step'   => 0.01,
                'append' => 'ч',
            ],*/




            'plan_start_at' => [
                'class'        => WidgetField::class,
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                ],
            ],

            'plan_end_at' => [
                'class'        => WidgetField::class,
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                ],
            ],

            'fact_duration' => [
                'class'  => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class
            ],


            'client' => [
                'class'  => FieldSet::class,
                'name'   => 'Связи',
                'fields' => [


                    'cms_project_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass' => CmsProject::class,
                            /*'searchQuery' => function($word = '')  {

                                if ($word) {
                                    $findProjects->search($word);
                                }

                                return $findProjects;
                            },*/
                            /*'options' => [
                                'data' => [
                                    'form-reload' => 'true',
                                ],
                            ],*/
                        ],


                    ],

                    'cms_company_id' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsCompany::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsCompany::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],
                    'cms_user_id'    => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsUser::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsUser::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],


                ],
            ],



        ];

        /*if ($model->crmProject) {
            if ($model->crmProject->crmContractors) {
                $contrator_ids = ArrayHelper::map($model->crmProject->crmContractors, "id", "id");

                $acts = CrmAct::find()
                    ->joinWith("crmDeal as crmDeal")
                    ->joinWith("crmDeal.customerCrmContractor as customerCrmContractor")
                    ->andWhere(['customerCrmContractor.id' => $contrator_ids])
                    ->all();

                if ($acts) {
                    $result["crm_act_id"] = [
                        'class' => SelectField::class,
                        'items' => ArrayHelper::map($acts, "id", "asText"),
                    ];
                }

            }
        }*/


        return $result;
    }


}
