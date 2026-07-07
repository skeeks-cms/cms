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
use skeeks\cms\helpers\CmsScheduleHelper;
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
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\web\JsExpression;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTaskController extends BackendModelStandartController
{
    public $defaultAction = "calendar";
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
            
            'calendar' => [
                'class'    => ViewBackendAction::class,
                'name' => 'Мои задачи',
                'icon' => 'fa fa-calendar',
                'priority' => 10,
                /*'isOpenNewWindow' => false,*/
            ],

            'tasks-calendar' => [
                'class'    => ViewBackendAction::class,
                'name' => 'Мой календарь',
                'icon' => 'fa fa-calendar',
                'priority' => 15,
                /*'isOpenNewWindow' => false,*/
            ],

            'report' => [
                'class'    => ViewBackendAction::class,
                'name'     => 'Отчет',
                'icon'     => 'fa fa-chart-bar',
                'priority' => 25,
            ],
            
            'index' => [

                'priority' => 20,
                
                'name' => 'Все задачи',

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

                        'plan_end_at' => [
                            'format' => 'raw',
                            'label'  => 'Планируемое завершение',
                            'value'  => function (CmsTask $CmsTask) {
                                if ($CmsTask->plan_end_at) {
                                    return \Yii::$app->formatter->asDatetime((int) $CmsTask->plan_end_at, "php:d.m.Y H:i");
                                }

                                return " - ";
                            },
                        ],

                        'scheduleTotalTime' => [
                            'format'    => 'raw',
                            'label'     => 'Отработанное время',
                            'attribute' => 'scheduleTotalTime',
                            'value'     => function (CmsTask $CmsTask) {
                                if ($CmsTask->raw_row['scheduleTotalTime']) {
                                    return CmsScheduleHelper::durationAsText($CmsTask->raw_row['scheduleTotalTime']);
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
                        
                        
                        'scheduleTotalTimeHour' => [
                            'format'    => 'raw',
                            'label'     => 'Отработанное время (ч.)',
                            'attribute' => 'scheduleTotalTimeHour',
                            'value'     => function (CmsTask $CmsTask) {
                                if ($CmsTask->raw_row['scheduleTotalTimeHour']) {
                                    return \Yii::$app->formatter->asDecimal($CmsTask->raw_row['scheduleTotalTimeHour'] / 3600, 1);
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
                                    'scheduleTotalTimeHour' => $scheduleTotalTime,
                                ]);

                                $gridView->sortAttributes['scheduleTotalTimeHour'] = [
                                    'asc'     => ['scheduleTotalTimeHour' => SORT_ASC],
                                    'desc'    => ['scheduleTotalTimeHour' => SORT_DESC],
                                    'label'   => '',
                                    'default' => SORT_ASC,
                                ];
                            },
                        ],

                        'end_task_at' => [
                            'format'    => 'raw',
                            'label'     => 'Дата завершения',
                            'attribute' => 'end_task_at',
                            'value'     => function (CmsTask $CmsTask) {
                                if ($CmsTask->raw_row['end_task_at']) {
                                    return \Yii::$app->formatter->asDate($CmsTask->raw_row['end_task_at']);
                                } else {
                                    return " - ";
                                }
                            },
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;

                                $gridView->dataProvider->query->andWhere(['status' => CmsTask::STATUS_READY]);

                                $scheduleTotalTime = CmsTaskSchedule::find()->select([
                                    'MAX(end_at) as total_timestamp',
                                ])->where([
                                    'cms_task_id' => new Expression(CmsTask::tableName().".id"),
                                ]);

                                $query->addSelect([
                                    'end_task_at' => $scheduleTotalTime,
                                ]);

                                $gridView->sortAttributes['end_task_at'] = [
                                    'asc'     => ['end_task_at' => SORT_ASC],
                                    'desc'    => ['end_task_at' => SORT_DESC],
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

    public function actionReportExport($format = 'csv')
    {
        $report = $this->buildTaskReport($this->getTaskReportParams());
        $format = strtolower((string)$format);

        if ($format == 'xlsx') {
            return $this->downloadTaskReportXlsx($report);
        }

        if ($format == 'pdf') {
            return $this->downloadTaskReportPdf($report);
        }

        return $this->downloadTaskReportCsv($report);
    }

    public function getTaskReportParams()
    {
        $request = \Yii::$app->request;
        $period = (string)$request->get('period');

        if (!$period) {
            $period = date('01.m.Y').' - '.date('t.m.Y');
        }

        $columns = (array)$request->get('columns', []);
        if (!$columns) {
            $columns = ['name', 'result', 'project', 'completed_at'];
        }

        $columns = array_values(array_intersect(array_keys($this->taskReportColumns()), $columns));
        if (!$columns) {
            $columns = ['name', 'result', 'project', 'completed_at'];
        }

        $display = (string)$request->get('display', 'charts_data');
        if (!in_array($display, ['charts_data', 'charts', 'data'])) {
            $display = 'charts_data';
        }

        $taskView = (string)$request->get('task_view', 'list');
        if (!in_array($taskView, ['table', 'list'])) {
            $taskView = 'list';
        }

        return [
            'period'         => $period,
            'periodStart'    => $this->parseTaskReportPeriod($period, false),
            'periodEnd'      => $this->parseTaskReportPeriod($period, true),
            'cms_company_id' => (int)$request->get('cms_company_id'),
            'cms_user_id'    => (int)$request->get('cms_user_id'),
            'cms_project_id' => (int)$request->get('cms_project_id'),
            'executor_id'    => (int)$request->get('executor_id'),
            'status'         => (array)$request->get('status', []),
            'columns'        => $columns,
            'display'        => $display,
            'task_view'      => $taskView,
        ];
    }

    public function taskReportColumns()
    {
        return [
            'name'         => 'Задача',
            'result'       => 'Результат',
            'project'      => 'Проект',
            'company'      => 'Компания',
            'client'       => 'Клиент',
            'executor'     => 'Исполнитель',
            'status'       => 'Статус',
            'fact_time'    => 'Отработанное время',
            'fact_hours'   => 'Отработанное время (ч.)',
            'completed_at' => 'Дата завершения',
        ];
    }

    public function buildTaskReport(array $params)
    {
        $periodStart = (int)ArrayHelper::getValue($params, 'periodStart');
        $periodEnd = (int)ArrayHelper::getValue($params, 'periodEnd');

        if (!$periodStart || !$periodEnd) {
            $periodStart = strtotime(date('Y-m-01 00:00:00'));
            $periodEnd = strtotime(date('Y-m-t 23:59:59'));
        }

        if ($periodStart > $periodEnd) {
            $tmp = $periodStart;
            $periodStart = $periodEnd;
            $periodEnd = $tmp;
        }

        $tasksQuery = CmsTask::find()->select([CmsTask::tableName().'.id']);
        self::initQuery($tasksQuery);

        if (!empty($params['cms_company_id'])) {
            $tasksQuery->andWhere([CmsTask::tableName().'.cms_company_id' => (int)$params['cms_company_id']]);
        }
        if (!empty($params['cms_user_id'])) {
            $tasksQuery->andWhere([CmsTask::tableName().'.cms_user_id' => (int)$params['cms_user_id']]);
        }
        if (!empty($params['cms_project_id'])) {
            $tasksQuery->andWhere([CmsTask::tableName().'.cms_project_id' => (int)$params['cms_project_id']]);
        }
        if (!empty($params['executor_id'])) {
            $tasksQuery->andWhere([CmsTask::tableName().'.executor_id' => (int)$params['executor_id']]);
        }
        if (!empty($params['status'])) {
            $tasksQuery->andWhere([CmsTask::tableName().'.status' => (array)$params['status']]);
        }

        $query = CmsTaskSchedule::find()
            ->alias('s')
            ->andWhere(['s.cms_task_id' => $tasksQuery])
            ->andWhere(['<=', 's.start_at', $periodEnd])
            ->andWhere([
                'or',
                ['s.end_at' => null],
                ['>=', 's.end_at', $periodStart],
            ])
            ->orderBy(['s.start_at' => SORT_ASC]);

        $tasks = [];
        foreach ($query->with(['cmsTask.cmsProject', 'cmsTask.cmsCompany', 'cmsTask.cmsUser', 'cmsTask.executor'])->all() as $schedule) {
            /**
             * @var CmsTaskSchedule $schedule
             */
            $task = $schedule->cmsTask;
            if (!$task) {
                continue;
            }

            $taskEndAt = $schedule->end_at ? (int)$schedule->end_at : time();
            $startAt = max((int)$schedule->start_at, $periodStart);
            $endAt = min($taskEndAt, $periodEnd);
            if ($endAt < $startAt) {
                continue;
            }

            if (!isset($tasks[$task->id])) {
                $tasks[$task->id] = [
                    'task'        => $task,
                    'duration'    => 0,
                    'completedAt' => null,
                ];
            }

            $tasks[$task->id]['duration'] += $endAt - $startAt;
            $tasks[$task->id]['completedAt'] = max((int)$tasks[$task->id]['completedAt'], $taskEndAt);
        }

        $results = $this->getTaskReportResults(array_keys($tasks));
        $rows = [];
        $totalDuration = 0;
        $byExecutor = [];
        $byStatus = [];

        foreach ($tasks as $taskId => $taskData) {
            /**
             * @var CmsTask $task
             */
            $task = $taskData['task'];
            $duration = (int)$taskData['duration'];
            $totalDuration += $duration;

            $executorName = $task->executor ? (string)$task->executor->asText : '';
            $statusName = $task->statusAsText;

            if (!isset($byExecutor[$executorName])) {
                $byExecutor[$executorName] = ['name' => $executorName ?: 'Не указан', 'tasks' => 0, 'duration' => 0];
            }
            $byExecutor[$executorName]['tasks']++;
            $byExecutor[$executorName]['duration'] += $duration;

            if (!isset($byStatus[$statusName])) {
                $byStatus[$statusName] = ['name' => $statusName, 'tasks' => 0, 'duration' => 0];
            }
            $byStatus[$statusName]['tasks']++;
            $byStatus[$statusName]['duration'] += $duration;

            $completedAt = $task->status == CmsTask::STATUS_READY ? (int)$taskData['completedAt'] : null;
            $rows[] = [
                'name'         => (string)$task->name,
                'result'       => (string)ArrayHelper::getValue($results, $taskId, ''),
                'project'      => $task->cmsProject ? (string)$task->cmsProject->asText : '',
                'company'      => $task->cmsCompany ? (string)$task->cmsCompany->asText : '',
                'client'       => $task->cmsUser ? (string)$task->cmsUser->asText : '',
                'executor'     => $executorName,
                'status'       => $statusName,
                'fact_time'    => CmsScheduleHelper::durationAsText($duration),
                'fact_hours'   => \Yii::$app->formatter->asDecimal($duration / 3600, 1),
                'completed_at' => $completedAt ? \Yii::$app->formatter->asDate($completedAt) : '',
            ];
        }

        usort($rows, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return [
            'params'     => $params,
            'title'      => $this->buildTaskReportTitle($params),
            'columns'    => $this->taskReportColumns(),
            'rows'       => $rows,
            'summary'    => [
                'tasks'    => count($rows),
                'duration' => $totalDuration,
                'hours'    => $totalDuration / 3600,
            ],
            'byExecutor' => array_values($byExecutor),
            'byStatus'   => array_values($byStatus),
        ];
    }

    protected function buildTaskReportTitle(array $params)
    {
        $parts = ['Отчет'];

        if (!empty($params['cms_company_id'])) {
            $model = CmsCompany::findOne((int)$params['cms_company_id']);
            if ($model) {
                $parts[] = (string)$model->asText;
            }
        }
        if (!empty($params['cms_project_id'])) {
            $model = CmsProject::findOne((int)$params['cms_project_id']);
            if ($model) {
                $parts[] = (string)$model->asText;
            }
        }
        if (!empty($params['cms_user_id'])) {
            $model = CmsUser::findOne((int)$params['cms_user_id']);
            if ($model) {
                $parts[] = (string)$model->asText;
            }
        }
        if (!empty($params['executor_id'])) {
            $model = CmsUser::findOne((int)$params['executor_id']);
            if ($model) {
                $parts[] = (string)$model->asText;
            }
        }
        if (!empty($params['status'])) {
            $statuses = CmsTask::statuses();
            $statusNames = [];
            foreach ((array)$params['status'] as $status) {
                $statusName = (string)ArrayHelper::getValue($statuses, $status, $status);
                if ($statusName !== '') {
                    $statusNames[] = $statusName;
                }
            }
            if ($statusNames) {
                $parts[] = implode(', ', $statusNames);
            }
        }
        if (!empty($params['period'])) {
            $parts[] = trim((string)$params['period']);
        }

        return preg_replace('/\s+/u', ' ', trim(implode(' ', array_filter($parts))));
    }

    protected function taskReportDownloadName(array $report, $extension)
    {
        $title = (string)ArrayHelper::getValue($report, 'title', 'Отчет по задачам');
        $name = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]+/u', ' ', $title);
        $name = preg_replace('/\s+/u', ' ', trim($name));
        $name = trim($name, " .\t\n\r\0\x0B");

        if ($name === '') {
            $name = 'Отчет по задачам';
        }
        if (function_exists('mb_strlen') && mb_strlen($name, 'UTF-8') > 160) {
            $name = mb_substr($name, 0, 160, 'UTF-8');
        }

        return $name.'.'.ltrim((string)$extension, '.');
    }

    protected function taskReportSheetTitle(array $report)
    {
        $title = (string)ArrayHelper::getValue($report, 'title', 'Отчет');
        $title = preg_replace('/[\[\]\:\*\?\/\\\\]+/u', ' ', $title);
        $title = preg_replace('/\s+/u', ' ', trim($title));
        if ($title === '') {
            $title = 'Отчет';
        }

        return function_exists('mb_substr') ? mb_substr($title, 0, 31, 'UTF-8') : substr($title, 0, 31);
    }

    protected function getTaskReportResults(array $taskIds)
    {
        if (!$taskIds) {
            return [];
        }

        $result = [];
        $logs = CmsLog::find()
            ->comments()
            ->pinned()
            ->andWhere([
                'model_code' => CmsTask::class,
                'model_id'   => $taskIds,
            ])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        foreach ($logs as $log) {
            $comment = trim(strip_tags((string)$log->comment));
            if (!$comment) {
                continue;
            }
            if (!isset($result[$log->model_id])) {
                $result[$log->model_id] = [];
            }
            $result[$log->model_id][] = $comment;
        }

        foreach ($result as $taskId => $comments) {
            $result[$taskId] = implode("\n", $comments);
        }

        return $result;
    }

    protected function parseTaskReportPeriod($period, $isEnd = false)
    {
        $data = preg_split('/\s+-\s+/', (string)$period);
        $date = trim((string)ArrayHelper::getValue($data, $isEnd ? 1 : 0));
        if (!$date) {
            $date = trim((string)ArrayHelper::getValue($data, 0));
        }

        $timeZone = new \DateTimeZone(\Yii::$app->formatter->timeZone);
        foreach (['d/m/Y', 'd.m.Y', 'Y-m-d'] as $format) {
            $dateTime = \DateTime::createFromFormat($format.' H:i:s', $date.' '.($isEnd ? '23:59:59' : '00:00:00'), $timeZone);
            if ($dateTime instanceof \DateTime && $dateTime->format($format) == $date) {
                return $dateTime->getTimestamp();
            }
        }

        return strtotime($date.' '.($isEnd ? '23:59:59' : '00:00:00'));
    }

    protected function downloadTaskReportCsv(array $report)
    {
        $content = "\xEF\xBB\xBF";
        $out = fopen('php://temp', 'r+');
        $columns = (array)ArrayHelper::getValue($report, 'params.columns', []);
        fputcsv($out, array_values(array_intersect_key($report['columns'], array_flip($columns))), ';');

        foreach ($report['rows'] as $row) {
            $cells = [];
            foreach ($columns as $column) {
                $cells[] = ArrayHelper::getValue($row, $column, '');
            }
            fputcsv($out, $cells, ';');
        }

        rewind($out);
        $content .= stream_get_contents($out);
        fclose($out);

        return \Yii::$app->response->sendContentAsFile($content, $this->taskReportDownloadName($report, 'csv'), [
            'mimeType' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function downloadTaskReportXlsx(array $report)
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            return $this->downloadTaskReportCsv($report);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->taskReportSheetTitle($report));
        $columns = (array)ArrayHelper::getValue($report, 'params.columns', []);

        $columnIndex = 1;
        foreach ($columns as $column) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, ArrayHelper::getValue($report['columns'], $column, $column));
            $columnIndex++;
        }

        $rowIndex = 2;
        foreach ($report['rows'] as $row) {
            $columnIndex = 1;
            foreach ($columns as $column) {
                $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, ArrayHelper::getValue($row, $column, ''));
                $columnIndex++;
            }
            $rowIndex++;
        }

        foreach (range(1, max(1, count($columns))) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $file = \Yii::getAlias('@runtime').DIRECTORY_SEPARATOR.'task-report-'.time().'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = \Yii::$app->response->sendFile($file, $this->taskReportDownloadName($report, 'xlsx'));
        $response->on(Response::EVENT_AFTER_SEND, function () use ($file) {
            @unlink($file);
        });

        return $response;
    }

    protected function downloadTaskReportPdf(array $report)
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            return $this->downloadTaskReportCsv($report);
        }

        $html = $this->renderPartial('report-pdf', [
            'report' => $report,
        ]);

        $tempDir = \Yii::getAlias('@runtime/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'format'  => 'A4-L',
        ]);
        $mpdf->SetTitle((string)ArrayHelper::getValue($report, 'title', 'Отчет по задачам'));
        $mpdf->WriteHTML($html);

        return \Yii::$app->response->sendContentAsFile($mpdf->Output('', 'S'), $this->taskReportDownloadName($report, 'pdf'), [
            'mimeType' => 'application/pdf',
        ]);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsTask
         */
        $model = $action->model;

        if ($model->isNewRecord) {
            $model->executor_id = \Yii::$app->user->id;
            if (!$model->plan_duration) {
                $model->plan_duration = 60 * 15;
            }

            $this->applyParentTaskFromRequest($model);
        }

        $model->load(\Yii::$app->request->get());
        $isExecutorLocked = !$model->isNewRecord && CmsTaskSchedule::find()->task($model)->exists();

        $projectAjaxUrl = \yii\helpers\Json::encode(\yii\helpers\Url::current([
            'ajaxid' => 'cmstask-project-relation-select',
        ]));
        $estimateEndAtUrl = \yii\helpers\Json::encode(\yii\helpers\Url::current([
            'ajaxid' => 'cmstask-estimate-end-at',
        ]));

        if (\Yii::$app->request->isAjax && \Yii::$app->request->get('ajaxid') == 'cmstask-estimate-end-at') {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            $executorId = (int)\Yii::$app->request->get('executor_id');
            $duration = (int)\Yii::$app->request->get('duration');
            $executor = $executorId ? CmsUser::find()->isWorker()->andWhere([CmsUser::tableName().'.id' => $executorId])->one() : null;
            $estimatedEndAt = ($executor && $duration > 0) ? $this->calculateEstimatedTaskEndAt($executor, $duration, $model->isNewRecord ? null : (int)$model->id) : null;

            \Yii::$app->response->data = [
                'success' => (bool)$estimatedEndAt,
                'end_at' => $estimatedEndAt,
                'text'    => $estimatedEndAt ? $this->formatEstimatedTaskEndAt($estimatedEndAt) : '',
            ];
            \Yii::$app->end();
        }

        $this->view->registerCSS(<<<CSS
.field-cmstask-cms_company_id {
    display: none;
}
.field-cmstask-cms_user_id {
    display: none;
}
.field-cmstask-cms_project_id {
    display: none;
}
.sx-task-company-project-field [data-sx-quick-access-picker="projects"] {
    display: none !important;
}
.sx-choose-task-relation .btn.sx-active {
    background: #6c757d !important;
    color: white;
}
.sx-task-estimate-end {
    margin-top: 8px;
    margin-bottom: 8px;
    color: #657786;
    font-size: 14px;
}
.sx-task-estimate-hint {
    margin-left: 5px;
    color: silver;
    cursor: help;
}
.sx-task-estimate-end__value {
    color: #2f3b45;
    font-weight: 600;
}
.sx-task-estimate-end.is-empty .sx-task-estimate-end__value {
    color: #9aa8b2;
    font-weight: 400;
}
.sx-task-fixed-start-actions {
    margin-bottom: 14px;
}
.field-cmstask-plan_start_at {
    display: none;
    max-width: 380px;
}
.field-cmstask-plan_start_at .input-group,
.field-cmstask-plan_start_at input {
    width: 100%;
}
.sx-task-auto-start-actions {
    display: none;
    margin-top: 10px;
    margin-bottom: 14px;
}
CSS
        );

        $this->view->registerJs(<<<JS
var taskRelationProjectAjaxUrl = {$projectAjaxUrl};
var taskEstimateEndAtUrl = {$estimateEndAtUrl};
var taskRelationProjectsRequest = null;
var taskEstimateEndAtRequest = null;
var taskRelationSilentProjectChange = false;

function clearTaskProjectRelationValue() {
    taskRelationSilentProjectChange = true;
    $("#cmstask-cms_project_id").val("").trigger("change");
    taskRelationSilentProjectChange = false;
}

function normalizeTaskPlanStartDisplay() {
    $(".field-cmstask-plan_start_at input[type='text']").each(function() {
        var value = $(this).val();
        if (value) {
            $(this).val(value.replace(/(:\\d{2})\\d{2}$/, "$1").replace(/(:\\d{2}):\\d{2}$/, "$1"));
        }
    });
}

function hasTaskPlanStartValue() {
    return !!($("#cmstask-plan_start_at").val() || $(".field-cmstask-plan_start_at input[type='text']").val());
}

function updateTaskPlanStartMode() {
    if (hasTaskPlanStartValue()) {
        $(".sx-task-estimate-end").slideUp();
        $(".field-cmstask-plan_start_at").show();
        $(".sx-task-fixed-start-actions").hide();
        $(".sx-task-auto-start-actions").show();
    } else {
        $(".sx-task-estimate-end").slideDown();
        $(".field-cmstask-plan_start_at").hide();
        $(".sx-task-fixed-start-actions").show();
        $(".sx-task-auto-start-actions").hide();
    }
}

function clearTaskPlanStart() {
    $("#cmstask-plan_start_at").val("").trigger("change");
    $(".field-cmstask-plan_start_at input[type='text']").val("").trigger("change");
    updateTaskPlanStartMode();
}

function updateTaskRelationCompanyProjects() {
    var cms_company_id = $("#cmstask-cms_company_id").val();
    var isCompanyActive = $(".cms_company_id-btn").hasClass("sx-active");
    var field = $(".field-cmstask-cms_project_id");

    if (taskRelationProjectsRequest) {
        taskRelationProjectsRequest.abort();
        taskRelationProjectsRequest = null;
    }

    if (!cms_company_id || !isCompanyActive) {
        field.removeClass("sx-task-company-project-field");
        field.slideUp();
        return false;
    }

    taskRelationProjectsRequest = $.getJSON(taskRelationProjectAjaxUrl, {
        cms_company_id: cms_company_id,
        q: ''
    }, function(data) {
        var hasProjects = data && data.results && data.results.length;

        if (hasProjects) {
            field.addClass("sx-task-company-project-field");
            field.children("label").first().text("Проект компании");
            field.insertAfter(".field-cmstask-cms_company_id").slideDown();
        } else {
            clearTaskProjectRelationValue();
            field.removeClass("sx-task-company-project-field");
            field.slideUp();
        }
    });

    return false;
}

$("body").on("click", ".sx-choose-task-relation .btn", function(e, data) {
    var projectField = $(".field-cmstask-cms_project_id");
    var wasCompanyProjectMode = projectField.hasClass("sx-task-company-project-field");

    $(".field-cmstask-cms_company_id").slideUp();
    $(".field-cmstask-cms_user_id").slideUp();
    projectField.slideUp();

    $(".sx-choose-task-relation .btn").removeClass("sx-active");
    $(this).addClass("sx-active");
    $($(this).data("view")).slideDown();

    if ($(this).hasClass("cms_company_id-btn")) {
        updateTaskRelationCompanyProjects();
    } else if ($(this).hasClass("cms_project_id-btn")) {
        if (wasCompanyProjectMode) {
            clearTaskProjectRelationValue();
        }
        projectField.removeClass("sx-task-company-project-field").children("label").first().text("Проект");
    }

    return false;
});

$("body").on("select2:select select2:unselect", "#cmstask-cms_company_id", function() {
    $("#cmstask-cms_user_id").val("").trigger("change");
    clearTaskProjectRelationValue();
    updateTaskRelationCompanyProjects();
});

$("body").on("select2:select select2:unselect", "#cmstask-cms_user_id", function() {
    $("#cmstask-cms_company_id").val("").trigger("change");
    clearTaskProjectRelationValue();
});

$("body").on("select2:select select2:unselect", "#cmstask-cms_project_id", function() {
    if (taskRelationSilentProjectChange) {
        return;
    }
    $("#cmstask-cms_user_id").val("").trigger("change");
    if (!$(".cms_company_id-btn").hasClass("sx-active")) {
        $("#cmstask-cms_company_id").val("").trigger("change");
    }
});

function normalizeTaskRelationValues() {
    if ($(".cms_company_id-btn").hasClass("sx-active")) {
        $("#cmstask-cms_user_id").val("");
        if (!$("#cmstask-cms_company_id").val()) {
            $("#cmstask-cms_project_id").val("");
        }
    } else if ($(".cms_user_id-btn").hasClass("sx-active")) {
        $("#cmstask-cms_company_id").val("");
        $("#cmstask-cms_project_id").val("");
    } else if ($(".cms_project_id-btn").hasClass("sx-active")) {
        $("#cmstask-cms_company_id").val("");
        $("#cmstask-cms_user_id").val("");
    }
}

function updateTaskEstimateEndAt() {
    var executorId = $("#cmstask-executor_id").val();
    var duration = $("#cmstask-plan_duration").val();
    var estimate = $(".sx-task-estimate-end");

    if (taskEstimateEndAtRequest) {
        taskEstimateEndAtRequest.abort();
        taskEstimateEndAtRequest = null;
    }

    if (!executorId || !duration || !estimate.length) {
        estimate.addClass("is-empty");
        $(".sx-task-estimate-end__value", estimate).text("—");
        return false;
    }

    $(".sx-task-estimate-end__value", estimate).text("считаем...");

    taskEstimateEndAtRequest = $.getJSON(taskEstimateEndAtUrl, {
        executor_id: executorId,
        duration: duration
    }, function(data) {
        if (data && data.success && data.text) {
            estimate.removeClass("is-empty");
            $(".sx-task-estimate-end__value", estimate).text(data.text);
        } else {
            estimate.addClass("is-empty");
            $(".sx-task-estimate-end__value", estimate).text("нет данных по графику");
        }
    }).fail(function(xhr, status) {
        if (status !== "abort") {
            estimate.addClass("is-empty");
            $(".sx-task-estimate-end__value", estimate).text("не удалось рассчитать");
        }
    });

    return false;
}

$("body").on("change select2:select select2:unselect", "#cmstask-executor_id", function() {
    updateTaskEstimateEndAt();
});

$("body").on("keyup change input", "#cmstask-plan_duration, .field-cmstask-plan_duration .sx-not-real input, .field-cmstask-plan_duration .sx-not-real select", function() {
    setTimeout(updateTaskEstimateEndAt, 50);
});

$("body").on("click", ".sx-task-fixed-start-btn", function() {
    $(".field-cmstask-plan_start_at").slideDown();
    $(".field-cmstask-plan_start_at input").attr({
        autocomplete: "off",
        "data-lpignore": "true"
    });
    normalizeTaskPlanStartDisplay();
    $(".sx-task-fixed-start-actions").slideUp();
    $(".sx-task-estimate-end").slideUp();
    $(".sx-task-auto-start-actions").slideDown();
    return false;
});

$("body").on("change keyup", ".field-cmstask-plan_start_at input[type='text']", function() {
    normalizeTaskPlanStartDisplay();
    updateTaskPlanStartMode();
});

$("body").on("click", ".sx-task-auto-start-btn", function() {
    clearTaskPlanStart();
    updateTaskEstimateEndAt();
    return false;
});

$("body").on("beforeSubmit submit", "form", function() {
    if ($(this).find("#cmstask-name").length) {
        normalizeTaskRelationValues();
    }
});

function reloadTaskRelationView() {
    var cms_company_id = $("#cmstask-cms_company_id").val();
    var cms_user_id = $("#cmstask-cms_user_id").val();
    var cms_project_id = $("#cmstask-cms_project_id").val();

    if (cms_company_id) {
        $(".cms_company_id-btn").trigger("click", {
            'is_first' : true
        });
    } else if (cms_user_id) {
        $(".cms_user_id-btn").trigger("click", {
            'is_first' : true
        });
    } else if (cms_project_id) {
        $(".cms_project_id-btn").trigger("click", {
            'is_first' : true
        });
    } else {
        $(".cms_company_id-btn").trigger("click", {
            'is_first' : true
        });
    }

    return false;
}

reloadTaskRelationView();
updateTaskEstimateEndAt();
$(".field-cmstask-plan_start_at input").attr({
    autocomplete: "off",
    "data-lpignore": "true"
});
normalizeTaskPlanStartDisplay();
updateTaskPlanStartMode();

$(document).on('pjax:complete', function (e) {
    setTimeout(function() {
        reloadTaskRelationView();
        updateTaskEstimateEndAt();
        $(".field-cmstask-plan_start_at input").attr({
            autocomplete: "off",
            "data-lpignore": "true"
        });
        normalizeTaskPlanStartDisplay();
        updateTaskPlanStartMode();
    }, 200);
});
JS
        );

        $result = [
            'name',

            'parent_cms_task_id' => [
                'class' => HtmlBlock::class,
                'content' => \yii\helpers\Html::activeHiddenInput($model, 'parent_cms_task_id'),
            ],

            'executor_id'   => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'hint'         => $isExecutorLocked ? 'Исполнителя нельзя изменить, потому что по задаче уже начата работа.' : null,
                'widgetConfig' => [
                    'modelClass'  => CmsUser::class,
                    'multiple'    => false,
                    'options'     => [
                        'disabled' => $isExecutorLocked,
                    ],
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


            'plan_duration' => [
                'class'  => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class,
                'label' => 'Время на выполнение задачи',
                'widgetConfig' => [
                    'availableUnits' => [
                        'min' => 'мин',
                        'hour' => 'час',
                    ],
                    'defaultUnit' => 'min',
                ],
            ],

            'estimate_end_at' => [
                'class' => HtmlBlock::class,
                'content' => '<div class="col-12 sx-task-estimate-end is-empty">Задача будет сделана примерно <span class="sx-task-estimate-end__value">—</span> <i class="far fa-question-circle sx-task-estimate-hint" data-toggle="tooltip" data-html="true" title="Дата считается по рабочему графику исполнителя, его текущей очереди задач и указанному времени на выполнение."></i></div>',
            ],

            'fixed_start_at_trigger' => [
                'class' => HtmlBlock::class,
                'content' => '<div class="col-12 sx-task-fixed-start-actions"><button type="button" class="btn btn-default btn-sm sx-task-fixed-start-btn"><i class="far fa-clock"></i> Начать делать задачу в фиксированное время</button></div>',
            ],

            'plan_start_at' => [
                'class'        => WidgetField::class,
                'label'        => 'Когда начать делать задачу',
                'widgetClass'  => DateControl::class,
                'widgetConfig' => [
                    'type' => DateControl::FORMAT_DATETIME,
                    'displayFormat' => 'php:d-m-Y H:i',
                    'saveFormat' => 'php:U',
                    'options' => [
                        'autocomplete' => 'off',
                        'data-lpignore' => 'true',
                    ],
                    'saveOptions' => [
                        'autocomplete' => 'off',
                        'data-lpignore' => 'true',
                    ],
                    'widgetOptions' => [
                        'pluginOptions' => [
                            'minuteStep' => 5,
                        ],
                    ],
                ],
            ],

            'auto_start_at_trigger' => [
                'class' => HtmlBlock::class,
                'content' => '<div class="col-12 sx-task-auto-start-actions"><button type="button" class="btn btn-default btn-sm sx-task-auto-start-btn"><i class="fas fa-magic"></i> Рассчитать время выполнения автоматически</button></div>',
            ],

            'task_relation' => [
                'class' => HtmlBlock::class,
                'content' => '<div class="col-12 sx-choose-task-relation form-group"><div class="btn-group btn-block" role="group" aria-label="Task relation">
                  <button type="button" class="btn btn-default cms_company_id-btn" data-view=".field-cmstask-cms_company_id">&#1050;&#1086;&#1084;&#1087;&#1072;&#1085;&#1080;&#1103;</button>
                  <button type="button" class="btn btn-default cms_user_id-btn" data-view=".field-cmstask-cms_user_id">&#1050;&#1083;&#1080;&#1077;&#1085;&#1090;</button>
                  <button type="button" class="btn btn-default cms_project_id-btn" data-view=".field-cmstask-cms_project_id">&#1055;&#1088;&#1086;&#1077;&#1082;&#1090;</button>
                </div></div>',
            ],

            'cms_project_id' => [
                'class'        => WidgetField::class,
                'label'        => 'Проект',
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'id' => 'cmstask-project-relation-select',
                    'modelClass'  => CmsProject::class,
                    'searchQuery' => function ($word = '') use ($model) {
                        $query = CmsProject::find()->forManager();
                        $cmsCompanyId = \Yii::$app->request->get('cms_company_id', $model->cms_company_id);
                        if ($cmsCompanyId) {
                            $query->andWhere(['cms_company_id' => $cmsCompanyId]);
                        } else {
                            $query->andWhere([
                                'or',
                                ['cms_company_id' => null],
                                ['cms_company_id' => 0],
                            ]);
                        }
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                    'pluginOptions' => [
                        'ajax' => [
                            'data' => new JsExpression('function(params) { return {q: params.term, cms_company_id: $(".cms_company_id-btn").hasClass("sx-active") ? $("#cmstask-cms_company_id").val() : ""}; }'),
                        ],
                    ],
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

            /*'plan_duration' => [
                'class'  => NumberField::class,
                'step'   => 0.01,
                'append' => 'ч',
            ],*/
            'fact_duration' => [
                'class'  => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class,
                'label' => 'Длительность для отчета',
                'hint' => 'Если вас не устраивает реальное время, посчитанное по задаче, можете указать в этом поле другое время. Это поле увидит клиент в отчете.',
                'widgetConfig' => [
                    'availableUnits' => [
                        'min' => 'мин',
                        'hour' => 'час',
                    ],
                    'defaultUnit' => 'min',
                ],
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

        unset($result['client']);
        $result['plan_duration']['label'] = 'Время на выполнение задачи';

        if ($model->isNewRecord) {
            unset($result['fact_duration']);
        }

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

    public function actionUnlinkRelatedTask($pk)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $task = CmsTask::findOne((int)$pk);
        if (!$task) {
            throw new \yii\web\NotFoundHttpException('Задача не найдена.');
        }

        $task->updateAttributes([
            'parent_cms_task_id' => null,
        ]);

        return [
            'success' => true,
        ];
    }

    public function actionLinkRelatedTask($pk, $related_task_id = null)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $task = CmsTask::find()->forManager()->andWhere([CmsTask::tableName().'.id' => (int)$pk])->one();
        $relatedTaskId = (int)($related_task_id ?: \Yii::$app->request->post('related_task_id'));
        $relatedTask = $relatedTaskId ? CmsTask::find()->forManager()->andWhere([CmsTask::tableName().'.id' => $relatedTaskId])->one() : null;

        if (!$task || !$relatedTask) {
            throw new \yii\web\NotFoundHttpException('Задача не найдена.');
        }

        if ((int)$task->id === (int)$relatedTask->id) {
            return [
                'success' => false,
                'message' => 'Нельзя связать задачу саму с собой.',
            ];
        }

        $parentTask = $task->parentCmsTask;
        while ($parentTask) {
            if ((int)$parentTask->id === (int)$relatedTask->id) {
                return [
                    'success' => false,
                    'message' => 'Нельзя создать циклическую связь задач.',
                ];
            }

            $parentTask = $parentTask->parentCmsTask;
        }

        $relatedTask->updateAttributes([
            'parent_cms_task_id' => $task->id,
        ]);

        return [
            'success' => true,
        ];
    }

    protected function applyParentTaskFromRequest(CmsTask $model): void
    {
        $parentTaskId = (int)\Yii::$app->request->get('parent_cms_task_id');
        $parentTask = $parentTaskId ? CmsTask::findOne($parentTaskId) : null;

        if (!$parentTask) {
            return;
        }

        $model->parent_cms_task_id = $parentTask->id;
        $model->executor_id = $parentTask->executor_id ?: $model->executor_id;
        $model->cms_project_id = $parentTask->cms_project_id;
        $model->cms_company_id = $parentTask->cms_company_id;
        $model->cms_user_id = $parentTask->cms_user_id;
        $model->plan_duration = $parentTask->plan_duration ?: $model->plan_duration;
    }

    /**
     * Прогноз завершения новой задачи с учетом текущей очереди и рабочего графика исполнителя.
     */
    protected function calculateEstimatedTaskEndAt(CmsUser $user, int $duration, ?int $excludeTaskId = null): ?int
    {
        $remaining = max(0, $duration);
        if (!$remaining) {
            return null;
        }

        $scheduleTotalTime = CmsTaskSchedule::find()->select([
            'SUM(end_at - start_at) as total_timestamp',
        ])->where([
            'cms_task_id' => new Expression(CmsTask::tableName().".id"),
        ]);

        $tasksQuery = CmsTask::find()->select([
            CmsTask::tableName().'.id',
            CmsTask::tableName().'.plan_duration',
            'scheduleTotalTime' => $scheduleTotalTime,
        ])->where([
            'executor_id' => $user->id,
            'status' => [
                CmsTask::STATUS_NEW,
                CmsTask::STATUS_IN_WORK,
                CmsTask::STATUS_ON_PAUSE,
                CmsTask::STATUS_ACCEPTED,
            ],
        ])->orderBy([
            'executor_sort' => SORT_ASC,
            'id'            => SORT_DESC,
        ])->asArray();

        if ($excludeTaskId) {
            $tasksQuery->andWhere(['!=', CmsTask::tableName().'.id', $excludeTaskId]);
        }

        foreach ($tasksQuery->all() as $task) {
            $taskDuration = (int)ArrayHelper::getValue($task, 'plan_duration', 0);
            $taskWorked = (int)ArrayHelper::getValue($task, 'scheduleTotalTime', 0);
            $remaining += max(0, $taskDuration - $taskWorked);
        }

        $now = time();
        $workSchedule = (array)$user->work_shedule;

        for ($i = 0; $i <= 1000; $i++) {
            $date = date("Y-m-d", strtotime("+{$i} day"));
            $periods = CmsScheduleHelper::getSchedulesByWorktimeForDate($workSchedule, $date);

            foreach ($periods as $period) {
                $startAt = (int)$period->start_at;
                $endAt = (int)$period->end_at;

                if ($endAt <= $now) {
                    continue;
                }

                if ($startAt < $now) {
                    $startAt = $now;
                }

                $available = max(0, $endAt - $startAt);
                if (!$available) {
                    continue;
                }

                if ($remaining <= $available) {
                    return $startAt + $remaining;
                }

                $remaining -= $available;
            }
        }

        return null;
    }

    protected function formatEstimatedTaskEndAt(int $timestamp): string
    {
        $date = \Yii::$app->formatter->asDate($timestamp, "php:j F");
        $time = \Yii::$app->formatter->asTime($timestamp, "php:H:i");

        $today = strtotime(date("Y-m-d"));
        $targetDay = strtotime(date("Y-m-d", $timestamp));
        $daysDiff = (int)floor(($targetDay - $today) / 86400);

        if ($daysDiff === 0) {
            return "сегодня в {$time}";
        }

        if ($daysDiff === 1) {
            return "завтра в {$time}";
        }

        if ($daysDiff > 1) {
            return "{$date} в {$time} (через ".$this->formatDaysPlural($daysDiff).")";
        }

        return "{$date} в {$time}";
    }

    protected function formatDaysPlural(int $days): string
    {
        $mod10 = $days % 10;
        $mod100 = $days % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return "{$days} день";
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return "{$days} дня";
        }

        return "{$days} дней";
    }

}
