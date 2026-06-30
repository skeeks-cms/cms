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
