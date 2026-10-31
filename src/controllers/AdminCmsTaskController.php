<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\widgets\formInputs\datetime\DateTimeInputWidget;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\actions\BackendModelMultiWindowAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\forms\CmsTaskBulkEditForm;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsDocumentTemplate;
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
use skeeks\cms\widgets\formInputs\richText\RichTextInputWidget;
use skeeks\cms\widgets\formInputs\SmartDurationInputWidget;
use skeeks\cms\widgets\formInputs\SmartTimeInputWidget;
use skeeks\cms\widgets\GridView;
use skeeks\cms\models\CmsDeal;
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
use yii\helpers\Html;
use yii\web\Response;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsTaskController extends BackendModelStandartController
{
    public $defaultAction = "calendar";

    /** Views of the task section: the one a person opened last opens from the menu (owner's decision 31.10.2026). */
    public const TASK_VIEWS = ['calendar', 'deadlines', 'tasks-calendar', 'gantt', 'index'];
    public const TASK_VIEW_COOKIE = 'sx-task-view';

    public function beforeAction($action)
    {
        $request = \Yii::$app->request;
        if ($request instanceof \yii\web\Request && !$request->isAjax && $request->isGet && in_array($action->id, static::TASK_VIEWS, true)) {
            $last = (string)$request->cookies->getValue(static::TASK_VIEW_COOKIE, '');
            // The menu opens the section without an action: go to the last view.
            if (trim((string)\Yii::$app->requestedRoute, '/') === $this->uniqueId && $last !== '' && $last !== $action->id
                && in_array($last, static::TASK_VIEWS, true) && isset($this->actions[$last])) {
                \Yii::$app->response->redirect(\yii\helpers\Url::current([0 => '/'.$this->uniqueId.'/'.$last]));
                return false;
            }
            \Yii::$app->response->cookies->add(new \yii\web\Cookie([
                'name'   => static::TASK_VIEW_COOKIE,
                'value'  => $action->id,
                'expire' => time() + 365 * 86400,
            ]));
        }

        return parent::beforeAction($action);
    }

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

            'deadlines' => [
                'class'    => ViewBackendAction::class,
                'name'     => 'Сроки',
                'icon'     => 'fas fa-columns',
                'priority' => 12,
            ],

            'gantt' => [
                'class'    => ViewBackendAction::class,
                'name'     => 'Гант',
                'icon'     => 'fas fa-stream',
                'priority' => 17,
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
                                    'my_created' => 'Я поставил',
                                    'unassigned_client' => 'Неразобранные от клиентов',
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
                                    } elseif ($e->field->value == 'unassigned_client') {
                                        // initQuery() already applies forManager(); this scope only narrows it.
                                        $query->unassignedFromClients();
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
                                            //Только сотрудники, чьи задачи видны: сам и подчиненные (администратору — все)
                                            $q = CmsUser::find()->managedBy();

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
                            'headerOptions' => [
                                'style' => 'min-width: 190px; width: 190px;',
                            ],
                            'contentOptions' => [
                                'style' => 'min-width: 190px; width: 190px;',
                            ],
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
$('tr[data-key={$key}]').addClass('sx-row-in-work');
JS
                                    );

                                }

                                return CmsTaskStatusWidget::widget(['task' => $CmsTask, 'isShort' => true]);
                            },
                        ],
                        'created_by' => [
                            'headerOptions' => [
                                'style' => 'min-width: 190px; width: 190px;',
                            ],
                            'contentOptions' => [
                                'style' => 'min-width: 190px; width: 190px;',
                            ],
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
                'on beforeValidate' => function ($event) {
                    $this->keepAvailableLead($event->sender->model);
                },
            ],

            "update" => [
                'fields' => [$this, 'updateFields'],
                'on beforeValidate' => function ($event) {
                    $this->keepAvailableLead($event->sender->model);
                },
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-task/manage", ['model' => $this->model]);
                },
            ],

            'change-relation-multi' => [
                'class' => BackendModelMultiWindowAction::class,
                'name' => 'Редактирование',
                'icon' => 'fas fa-edit',
                'priority' => 100,
                'size' => 'medium',
                'buttons' => ['save'],
                'formModel' => function () {
                    $form = new CmsTaskBulkEditForm();
                    $projectId = (int)\Yii::$app->request->get('cms_project_id');
                    $companyId = (int)\Yii::$app->request->get('cms_company_id');
                    $clientId = (int)\Yii::$app->request->get('cms_user_id');

                    if ($projectId) {
                        $project = CmsProject::find()->forManager()->andWhere([CmsProject::tableName().'.id' => $projectId])->one();
                        if ($project) {
                            $form->cms_project_id = (int)$project->id;
                            if ($project->cms_company_id) {
                                $form->cms_company_id = (int)$project->cms_company_id;
                            }
                        }
                    } elseif ($companyId && CmsCompany::find()->forManager()->andWhere([CmsCompany::tableName().'.id' => $companyId])->exists()) {
                        $form->cms_company_id = $companyId;
                    } elseif ($clientId && CmsUser::find()->forManager()->andWhere([CmsUser::tableName().'.id' => $clientId])->exists()) {
                        $form->cms_user_id = $clientId;
                    }
                    return $form;
                },
                'fields' => [$this, 'bulkEditFields'],
                'modelsQueryCallback' => function (ActiveQuery $query) {
                    self::initQuery($query);
                    return $query;
                },
                'eachAccessCallback' => function (CmsTask $model) {
                    return \Yii::$app->user->can('cms/admin-task/manage', ['model' => $model]);
                },
                'applyCallback' => function (CmsTask $model, CmsTaskBulkEditForm $form) {
                    if ($form->executor_id !== null && $form->executor_id !== '') {
                        $model->executor_id = (int)$form->executor_id;
                    }
                    if ($form->plan_duration !== null && $form->plan_duration !== '') {
                        $model->plan_duration = (int)$form->plan_duration;
                    }
                    if ($form->fact_duration !== null && $form->fact_duration !== '') {
                        $model->fact_duration = (int)$form->fact_duration;
                    }

                    if ($form->hasRelationChange()) {
                        //A deal of another company or client does not move with the task.
                        if ($model->hasAttribute('cms_deal_id') && $model->cms_deal_id) {
                            $deal = CmsDeal::findOne((int)$model->cms_deal_id);
                            $keep = $deal && (
                                ($form->cms_company_id && (int)$deal->cms_company_id === (int)$form->cms_company_id)
                                || (!$form->cms_company_id && $form->cms_user_id && !$deal->cms_company_id && (int)$deal->cms_user_id === (int)$form->cms_user_id)
                            );
                            if (!$keep) {
                                $model->cms_deal_id = null;
                            }
                        }
                        if ($form->cms_company_id) {
                            $model->cms_company_id = (int)$form->cms_company_id;
                            $model->cms_project_id = $form->cms_project_id ? (int)$form->cms_project_id : null;
                            $model->cms_user_id = null;
                        } elseif ($form->cms_user_id) {
                            $model->cms_company_id = null;
                            $model->cms_project_id = null;
                            $model->cms_user_id = (int)$form->cms_user_id;
                        } else {
                            $model->cms_company_id = null;
                            $model->cms_project_id = (int)$form->cms_project_id;
                            $model->cms_user_id = null;
                        }
                    }

                    //The chosen deal; the task follows its company (or the owner of a personal deal), as in the single form.
                    if ($form->cms_deal_id && $model->hasAttribute('cms_deal_id')) {
                        $deal = CmsDeal::findOne((int)$form->cms_deal_id);
                        $model->cms_deal_id = (int)$deal->id;
                        if ($deal->cms_company_id) {
                            if ($model->cms_project_id && ($project = CmsProject::findOne((int)$model->cms_project_id)) && (int)$project->cms_company_id !== (int)$deal->cms_company_id) {
                                $model->cms_project_id = null;
                            }
                            $model->cms_company_id = (int)$deal->cms_company_id;
                            $model->cms_user_id = null;
                        } else {
                            $model->cms_company_id = null;
                            $model->cms_project_id = null;
                            $model->cms_user_id = (int)$deal->cms_user_id;
                        }
                        //A resource of another deal does not stay on the task.
                        if ($model->deal_resource_id) {
                            $resource = $model->getDealResource();
                            if (!$resource || (int)$resource->dealId !== (int)$deal->id) {
                                $model->deal_resource_type = null;
                                $model->deal_resource_id = null;
                            }
                        }
                    }

                    if (!$model->save()) {
                        throw new \RuntimeException(implode(' ', $model->getFirstErrors()));
                    }

                    return true;
                },
                'accessCallback' => true,
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

    public function bulkEditFields(BackendModelMultiWindowAction $action)
    {
        /** @var CmsTaskBulkEditForm $model */
        $model = $action->getFormModel();
        $executorInputId = Html::getInputId($model, 'executor_id');
        $planDurationInputId = Html::getInputId($model, 'plan_duration');
        $factDurationInputId = Html::getInputId($model, 'fact_duration');
        $companyInputId = Html::getInputId($model, 'cms_company_id');
        $clientInputId = Html::getInputId($model, 'cms_user_id');
        $projectInputId = Html::getInputId($model, 'cms_project_id');
        $relationRootId = 'sx-task-bulk-relation';

        $this->view->registerCss(<<<CSS
#{$relationRootId} .field-{$companyInputId},
#{$relationRootId} .field-{$clientInputId},
#{$relationRootId} .field-{$projectInputId} {
    display: none;
}
#{$relationRootId} .sx-task-bulk-company-project [data-sx-quick-access-picker="projects"] {
    display: none !important;
}
#{$relationRootId} .sx-task-bulk-relation-tabs {
    margin-bottom: 15px;
}
CSS
        );

        $this->view->registerJs(<<<JS
var taskBulkRelationRoot = $('#{$relationRootId}');
var taskBulkRelationProjectSilent = false;

function clearTaskBulkProject() {
    taskBulkRelationProjectSilent = true;
    $('#{$projectInputId}').val('').trigger('change');
    taskBulkRelationProjectSilent = false;
}

function showTaskBulkRelation(type) {
    var companyField = taskBulkRelationRoot.find('.field-{$companyInputId}');
    var clientField = taskBulkRelationRoot.find('.field-{$clientInputId}');
    var projectField = taskBulkRelationRoot.find('.field-{$projectInputId}');

    taskBulkRelationRoot.find('.sx-task-bulk-relation-tabs .btn').removeClass('active');
    taskBulkRelationRoot.find('[data-relation-type="' + type + '"]').addClass('active');
    companyField.hide();
    clientField.hide();
    projectField.hide().removeClass('sx-task-bulk-company-project');

    if (type === 'company') {
        companyField.show();
        if ($('#{$companyInputId}').val()) {
            projectField
                .addClass('sx-task-bulk-company-project')
                .children('label').first().text('Проект компании');
            projectField.show();
        }
    } else if (type === 'client') {
        clientField.show();
    } else {
        projectField.children('label').first().text('Проект');
        projectField.show();
    }
}

function currentTaskBulkRelationType() {
    return taskBulkRelationRoot.find('.sx-task-bulk-relation-tabs .btn.active').data('relation-type') || 'company';
}

function normalizeTaskBulkRelation() {
    var type = currentTaskBulkRelationType();
    if (type === 'company') {
        $('#{$clientInputId}').val('');
        if (!$('#{$companyInputId}').val()) {
            $('#{$projectInputId}').val('');
        }
    } else if (type === 'client') {
        $('#{$companyInputId}, #{$projectInputId}').val('');
    } else {
        $('#{$companyInputId}, #{$clientInputId}').val('');
    }
}

taskBulkRelationRoot
    .off('click.sxTaskBulkEdit', '.sx-task-bulk-relation-tabs .btn')
    .on('click.sxTaskBulkEdit', '.sx-task-bulk-relation-tabs .btn', function() {
        var previousType = currentTaskBulkRelationType();
        var nextType = $(this).data('relation-type');
        if (previousType === 'company' && nextType === 'project') {
            clearTaskBulkProject();
        }
        showTaskBulkRelation(nextType);
        return false;
    });

$('body')
    .off('.sxTaskBulkEdit', '#{$companyInputId}')
    .on('select2:select.sxTaskBulkEdit select2:unselect.sxTaskBulkEdit', '#{$companyInputId}', function() {
        $('#{$clientInputId}').val('').trigger('change');
        clearTaskBulkProject();
        showTaskBulkRelation('company');
    })
    .off('.sxTaskBulkEdit', '#{$clientInputId}')
    .on('select2:select.sxTaskBulkEdit select2:unselect.sxTaskBulkEdit', '#{$clientInputId}', function() {
        $('#{$companyInputId}').val('').trigger('change');
        clearTaskBulkProject();
    })
    .off('.sxTaskBulkEdit', '#{$projectInputId}')
    .on('select2:select.sxTaskBulkEdit select2:unselect.sxTaskBulkEdit', '#{$projectInputId}', function() {
        if (taskBulkRelationProjectSilent) {
            return;
        }
        $('#{$clientInputId}').val('').trigger('change');
        if (currentTaskBulkRelationType() !== 'company') {
            $('#{$companyInputId}').val('').trigger('change');
        }
    });

//Another company or client: the chosen deal no longer fits.
$('body')
    .off('.sxTaskBulkDeal')
    .on('select2:select.sxTaskBulkDeal select2:unselect.sxTaskBulkDeal', '#{$companyInputId}, #{$clientInputId}', function() {
        $('#cmstaskbulkeditform-cms_deal_id').val('').trigger('change');
    });

$('body').off('beforeSubmit.sxTaskBulkEdit', 'form').on('beforeSubmit.sxTaskBulkEdit', 'form', function() {
    if ($(this).find('#{$executorInputId}, #{$planDurationInputId}, #{$factDurationInputId}').length) {
        normalizeTaskBulkRelation();
    }
});

if ($('#{$companyInputId}').val()) {
    showTaskBulkRelation('company');
} else if ($('#{$clientInputId}').val()) {
    showTaskBulkRelation('client');
} else if ($('#{$projectInputId}').val()) {
    showTaskBulkRelation('project');
} else {
    showTaskBulkRelation('company');
}
JS
        );

        $fields = [
            'bulk_edit_hint' => [
                'class' => HtmlBlock::class,
                'content' => '<div class="col-12 text-muted form-group">Заполняйте только те поля, которые нужно изменить у выбранных задач.</div>',
            ],
            'task_relation_open' => [
                'class' => HtmlBlock::class,
                'content' => '<div id="' . $relationRootId . '" class="col-12"><div class="sx-task-bulk-relation-tabs btn-group btn-block" role="group" aria-label="Связь задачи">'
                    . '<button type="button" class="btn btn-default" data-relation-type="company">Компания</button>'
                    . '<button type="button" class="btn btn-default" data-relation-type="client">Клиент</button>'
                    . '<button type="button" class="btn btn-default" data-relation-type="project">Проект</button>'
                    . '</div>',
            ],
            'cms_company_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsCompany::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsCompany::find()->forManager();
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                ],
            ],
            'cms_user_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsUser::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->forManager();
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                ],
            ],
            'cms_project_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => [
                    'id' => 'cmstask-bulk-project-select',
                    'modelClass' => CmsProject::class,
                    'searchQuery' => function ($word = '') {
                        $companyId = (int)\Yii::$app->request->get('cms_company_id');
                        $query = CmsProject::find()->forManager();
                        if ($companyId) {
                            $query->andWhere(['cms_company_id' => $companyId]);
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
                            'data' => new JsExpression('function(params) { return {q: params.term, cms_company_id: $("#' . $relationRootId . ' [data-relation-type=company]").hasClass("active") ? $("#' . $companyInputId . '").val() : ""}; }'),
                        ],
                    ],
                ],
            ],
            'task_relation_close' => [
                'class' => HtmlBlock::class,
                'content' => '</div>',
            ],
            //"Сделка как услуга": the same deal select as in the single task form.
            'cms_deal_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'hint' => 'Услуги выбранной компании или клиента. Задачи перейдут к компании услуги.',
                'widgetConfig' => [
                    'id' => 'cmstask-bulk-deal-select',
                    'modelClass' => CmsDeal::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsDeal::find()->forManager();
                        $companyId = (int)\Yii::$app->request->get('cms_company_id');
                        $userId = (int)\Yii::$app->request->get('cms_user_id');
                        if ($companyId) {
                            $query->andWhere([CmsDeal::tableName().'.cms_company_id' => $companyId]);
                        } elseif ($userId) {
                            $query->andWhere([CmsDeal::tableName().'.cms_user_id' => $userId, CmsDeal::tableName().'.cms_company_id' => null]);
                        }
                        if ($word) {
                            $query->andWhere(['like', CmsDeal::tableName().'.name', $word]);
                        }
                        return $query->orderBy([CmsDeal::tableName().'.is_active' => SORT_DESC, CmsDeal::tableName().'.id' => SORT_DESC]);
                    },
                    'pluginOptions' => [
                        'ajax' => [
                            'data' => new JsExpression('function(params) { var t = $("#' . $relationRootId . ' .sx-task-bulk-relation-tabs .btn.active").data("relation-type"); return {q: params.term, cms_company_id: t === "company" ? $("#' . $companyInputId . '").val() : "", cms_user_id: t === "client" ? $("#' . $clientInputId . '").val() : ""}; }'),
                        ],
                    ],
                ],
            ],
            'executor_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsUser::class,
                    'multiple' => false,
                    'searchQuery' => function ($word = '') {
                        $query = CmsUser::find()->isWorker();
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                ],
            ],
            'plan_duration' => [
                'class' => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class,
                'widgetConfig' => [
                    'availableUnits' => [
                        'min' => 'мин',
                        'hour' => 'час',
                    ],
                    'defaultUnit' => 'min',
                ],
            ],
            'fact_duration' => [
                'class' => WidgetField::class,
                'widgetClass' => SmartDurationInputWidget::class,
                'hint' => 'Если нужно, укажите время, которое увидит клиент в отчете.',
                'widgetConfig' => [
                    'availableUnits' => [
                        'min' => 'мин',
                        'hour' => 'час',
                    ],
                    'defaultUnit' => 'min',
                ],
            ],
        ];

        if (!\skeeks\cms\models\queries\CmsTaskQuery::hasDealColumn()) {
            unset($fields['cms_deal_id']);
        }

        return $fields;
    }

    public function actionReportExport($format = 'csv')
    {
        $report = $this->buildTaskReport($this->getTaskReportParams());
        $format = strtolower((string)$format);

        if ($format == 'xlsx') {
            return $this->downloadTaskReportXlsx($report);
        }

        if ($format == 'pdf') {
            return $this->downloadTaskReportPdf($report, (bool)\Yii::$app->request->get('preview'));
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

        $documentTemplate = \Yii::$app->request->get('document_template');
        if ($documentTemplate === null) {
            $defaultTemplate = CmsDocumentTemplate::findDefault(
                \Yii::$app->skeeks->site->id,
                CmsDocumentTemplate::DOCUMENT_TASK_REPORT
            );
            $documentTemplate = $defaultTemplate ? 'id:'.$defaultTemplate->id : '';
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
            'document_template' => (string)$documentTemplate,
        ];
    }

    public function taskReportDocumentTemplateOptions()
    {
        $options = [
            '' => 'Обычный — без фирменного профиля',
            'preset:'.CmsDocumentTemplate::THEME_DARK => 'SkeekS Dark — готовая основа',
            'preset:'.CmsDocumentTemplate::THEME_LIGHT => 'SkeekS Light — готовая основа',
        ];

        $models = CmsDocumentTemplate::find()
            ->andWhere([
                'cms_site_id' => \Yii::$app->skeeks->site->id,
                'is_active' => 1,
                'document_type' => [
                    CmsDocumentTemplate::DOCUMENT_ALL,
                    CmsDocumentTemplate::DOCUMENT_TASK_REPORT,
                ],
            ])
            ->orderBy(['is_default' => SORT_DESC, 'name' => SORT_ASC])
            ->all();

        foreach ($models as $model) {
            $label = $model->name;
            if ($model->is_default) {
                $label .= ' — по умолчанию';
            }
            $options['id:'.$model->id] = $label;
        }

        return $options;
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
            $resultData = (array)ArrayHelper::getValue($results, $taskId, []);
            $rows[] = [
                'name'         => (string)$task->name,
                'result'       => (string)ArrayHelper::getValue($resultData, 'text', ''),
                'result_files' => (array)ArrayHelper::getValue($resultData, 'files', []),
                'result_items' => (array)ArrayHelper::getValue($resultData, 'items', []),
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
            ->with('files')
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        foreach ($logs as $log) {
            $taskId = (int)$log->model_id;
            if (!isset($result[$taskId])) {
                $result[$taskId] = [
                    'comments' => [],
                    'files'    => [],
                    'items'    => [],
                ];
            }

            $comment = $this->normalizeTaskReportResultComment($log->comment);
            if ($comment !== '') {
                $result[$taskId]['comments'][] = $comment;
            }

            $itemFiles = [];
            foreach ($log->files as $file) {
                $rootSrc = (string)$file->rootSrc;
                $absoluteSrc = (string)$file->absoluteSrc;
                $fileData = [
                    'id'       => (int)$file->id,
                    'name'     => (string)$file->fileName,
                    'mimeType' => (string)$file->mime_type,
                    'isImage'  => (bool)$file->isImage(),
                    'src'      => $rootSrc !== '' && is_file($rootSrc) ? $rootSrc : $absoluteSrc,
                    'url'      => $absoluteSrc,
                ];
                $itemFiles[$file->id] = $fileData;
                $result[$taskId]['files'][$file->id] = $fileData;
            }

            if ($comment !== '' || $itemFiles) {
                $result[$taskId]['items'][] = [
                    'id'    => (int)$log->id,
                    'text'  => $comment,
                    'files' => array_values($itemFiles),
                ];
            }
        }

        foreach ($result as $taskId => $resultData) {
            $result[$taskId] = [
                'text'  => implode("\n\n", $resultData['comments']),
                'files' => array_values($resultData['files']),
                'items' => array_values($resultData['items']),
            ];
        }

        return $result;
    }

    /**
     * Converts the HTML produced by the comment editor to readable plain text
     * without losing paragraph and list-item boundaries in the PDF report.
     */
    protected function normalizeTaskReportResultComment($comment)
    {
        $comment = (string)$comment;
        if ($comment === '') {
            return '';
        }

        $comment = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $comment);
        $comment = preg_replace('/<\s*li\b[^>]*>/iu', '• ', $comment);
        $comment = preg_replace('/<\/\s*(?:p|div|li|h[1-6]|tr)\s*>/iu', "\n", $comment);
        $comment = preg_replace('/<\/\s*(?:td|th)\s*>/iu', "\t", $comment);
        $comment = strip_tags($comment);
        $comment = html_entity_decode($comment, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $comment = str_replace(["\r\n", "\r"], "\n", $comment);
        $comment = preg_replace('/[ \t]+\n/u', "\n", $comment);
        // The editor may put its own line break around every block element.
        // After closing tags have also been converted to a line break this
        // otherwise produces an empty line between each visible row.
        $comment = preg_replace('/\n{2,}/u', "\n", $comment);

        return trim($comment);
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

    protected function downloadTaskReportPdf(array $report, $inline = false)
    {
        if (!class_exists('\Mpdf\Mpdf')) {
            return $this->downloadTaskReportCsv($report);
        }

        $site = \Yii::$app->skeeks->site;
        $theme = CmsDocumentTemplate::resolveTheme(
            (string)ArrayHelper::getValue($report, 'params.document_template'),
            $site,
            CmsDocumentTemplate::DOCUMENT_TASK_REPORT
        );

        if ($theme) {
            $templateModel = ArrayHelper::getValue($theme, 'model');
            $logo = $templateModel && $templateModel->logoStorageFile
                ? $templateModel->logoStorageFile
                : null;

            if (!$logo) {
                $logo = ArrayHelper::getValue($theme, 'theme') == CmsDocumentTemplate::THEME_DARK
                    ? $site->image
                    : ($site->logoLightImage ?: $site->image);
            }

            if ($logo) {
                $logoPath = $logo->getRootSrc();
                $theme['logo_src'] = $logoPath && is_file($logoPath) ? $logoPath : $logo->absoluteSrc;
            } else {
                $theme['logo_src'] = null;
            }
            $theme['company_name'] = $site->name;
            $theme['company_url'] = $site->url;
        }

        $tempDir = \Yii::getAlias('@runtime/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $report = $this->prepareTaskReportPdfImages($report, $tempDir);

        $html = $this->renderPartial('report-pdf', [
            'report' => $report,
            'theme'  => $theme,
        ]);

        $orientation = $theme && ArrayHelper::getValue($theme, 'page_orientation') == CmsDocumentTemplate::ORIENTATION_PORTRAIT
            ? 'P'
            : 'L';
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $tempDir,
            'format'  => 'A4',
            'orientation' => $orientation,
            'margin_top' => $theme ? 26 : 14,
            'margin_right' => $theme ? 16 : 14,
            'margin_bottom' => $theme && ArrayHelper::getValue($theme, 'show_footer') ? 20 : 14,
            'margin_left' => $theme ? 16 : 14,
            'margin_header' => $theme ? 10 : 9,
        ]);
        $mpdf->SetTitle((string)ArrayHelper::getValue($report, 'title', 'Отчет по задачам'));

        if ($theme) {
            $backgroundFile = $this->taskReportBackgroundFile($theme, $orientation, $tempDir);
            if ($backgroundFile) {
                $mpdf->SetDocTemplate($backgroundFile, true);
            }
        }

        if ($theme && ArrayHelper::getValue($theme, 'show_footer')) {
            $footerText = trim((string)ArrayHelper::getValue($theme, 'footer_text'));
            if ($footerText === '') {
                $footerText = trim($site->name.' · '.$site->url, ' ·');
            }
            $pageNumber = ArrayHelper::getValue($theme, 'show_page_numbers') ? '{PAGENO} / {nbpg}' : '';
            $mpdf->SetHTMLFooter(
                '<table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; border: 0; border-top: 1px solid '
                .Html::encode(ArrayHelper::getValue($theme, 'border_color')).'; color: '
                .Html::encode(ArrayHelper::getValue($theme, 'muted_color')).'; font-size: 8pt;"><tr>'
                .'<td width="80%" style="background: transparent; border: 0; padding: 3mm 0 0;">'.Html::encode($footerText).'</td>'
                .'<td width="20%" align="right" style="background: transparent; border: 0; padding: 3mm 0 0;">'.$pageNumber.'</td>'
                .'</tr></table>'
            );
        }
        $this->writeTaskReportPdfHtml($mpdf, $html);

        $response = \Yii::$app->response;
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response->sendContentAsFile($mpdf->Output('', 'S'), $this->taskReportDownloadName($report, 'pdf'), [
            'mimeType' => 'application/pdf',
            'inline' => (bool)$inline,
        ]);
    }

    /**
     * Подменяет изображения вложений уменьшенными копиями для PDF.
     *
     * mPDF встраивает картинку в исходном размере, а WEBP ещё и перекодирует
     * через GD: длинный скриншот 1920×8000 требует сотни мегабайт и роняет
     * запрос по memory_limit. В PDF вложение выводится не выше 42 мм, поэтому
     * достаточно копии до TASK_REPORT_PDF_IMAGE_BOX пикселей по большей стороне.
     * Изображение, которое не удаётся безопасно уменьшить, выводится ссылкой.
     *
     * @param array  $report
     * @param string $tempDir
     *
     * @return array
     */
    protected function prepareTaskReportPdfImages(array $report, $tempDir)
    {
        $prepared = [];
        $prepare = function (array $files) use ($tempDir, &$prepared) {
            foreach ($files as $key => $file) {
                if (!ArrayHelper::getValue($file, 'isImage') || !ArrayHelper::getValue($file, 'src')) {
                    continue;
                }

                $src = (string)$file['src'];
                if (!array_key_exists($src, $prepared)) {
                    $prepared[$src] = $this->taskReportPdfImageFile($src, $tempDir);
                }

                if ($prepared[$src]) {
                    $files[$key]['src'] = $prepared[$src];
                } else {
                    $files[$key]['isImage'] = false;
                }
            }

            return $files;
        };

        foreach ((array)ArrayHelper::getValue($report, 'rows', []) as $rowKey => $row) {
            $report['rows'][$rowKey]['result_files'] = $prepare((array)ArrayHelper::getValue($row, 'result_files', []));
            foreach ((array)ArrayHelper::getValue($row, 'result_items', []) as $itemKey => $item) {
                $report['rows'][$rowKey]['result_items'][$itemKey]['files'] = $prepare((array)ArrayHelper::getValue($item, 'files', []));
            }
        }

        return $report;
    }

    const TASK_REPORT_PDF_IMAGE_BOX = 1400;

    /**
     * @param string $src     локальный путь или URL изображения
     * @param string $tempDir
     *
     * @return string|null путь к файлу для mPDF или null, если изображение выводить нельзя
     */
    protected function taskReportPdfImageFile($src, $tempDir)
    {
        if (!is_file($src)) {
            // Удалённый файл mPDF скачает сам; размер заранее неизвестен.
            return $src;
        }

        $size = @getimagesize($src);
        if (!$size || empty($size[0]) || empty($size[1])) {
            return null;
        }

        list($width, $height, $type) = $size;
        $box = self::TASK_REPORT_PDF_IMAGE_BOX;
        $isWebp = defined('IMAGETYPE_WEBP') && $type == IMAGETYPE_WEBP;
        if (!$isWebp && $width <= $box && $height <= $box) {
            return $src;
        }

        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $isJpeg = $type == IMAGETYPE_JPEG;
        $dir = $tempDir.DIRECTORY_SEPARATOR.'report-images';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = $dir.DIRECTORY_SEPARATOR.md5(implode('|', [$src, filemtime($src), filesize($src), $box])).($isJpeg ? '.jpg' : '.png');
        if (is_file($file)) {
            return $file;
        }

        // Распакованное изображение GD занимает около 5 байт на пиксель.
        // Если его не вместить в оставшуюся память, не пытаемся.
        $limit = $this->taskReportMemoryLimit();
        if ($limit > 0 && memory_get_usage() + $width * $height * 5 + filesize($src) > $limit) {
            \Yii::warning("Изображение {$src} ({$width}x{$height}) слишком велико для PDF-отчёта", self::class);

            return null;
        }

        $image = @imagecreatefromstring((string)file_get_contents($src));
        if (!$image) {
            return null;
        }

        $scale = min(1, $box / max($width, $height));
        $newWidth = max(1, (int)round($width * $scale));
        $newHeight = max(1, (int)round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if (!$isJpeg) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        $tmp = $file.'.'.getmypid().'.tmp';
        $saved = $isJpeg ? imagejpeg($resized, $tmp, 85) : imagepng($resized, $tmp, 6);
        imagedestroy($resized);

        if (!$saved || !@rename($tmp, $file)) {
            @unlink($tmp);

            return null;
        }

        return $file;
    }

    /**
     * @return int memory_limit в байтах, 0 — без ограничения
     */
    protected function taskReportMemoryLimit()
    {
        $value = trim((string)ini_get('memory_limit'));
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $bytes = (int)$value;
        switch (strtolower(substr($value, -1))) {
            case 'g':
                $bytes *= 1024;
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }

    /**
     * Передаёт отчёт в mPDF частями по явным границам PDF-шаблона.
     *
     * mPDF отклоняет одиночный вызов WriteHTML(), если размер HTML превышает
     * pcre.backtrack_limit. Маркеры находятся между законченными блоками и не
     * попадают в PDF, поэтому потоковая запись не меняет разметку документа.
     *
     * @param \Mpdf\Mpdf $mpdf
     * @param string      $html
     */
    protected function writeTaskReportPdfHtml($mpdf, $html)
    {
        foreach (explode('<!-- skeeks-mpdf-chunk -->', (string)$html) as $chunk) {
            if ($chunk !== '') {
                $mpdf->WriteHTML($chunk);
            }
        }
    }

    /**
     * Фирменная подложка страницы: градиент и декоративное кольцо.
     *
     * mPDF печатает фоновые градиенты после всех сплошных заливок, поэтому
     * градиент документа перекрыл бы карточки. Подложка готовится отдельным
     * документом и подставляется под содержимое каждой страницы.
     *
     * @param array  $theme
     * @param string $orientation P|L
     * @param string $tempDir
     *
     * @return string|null путь к файлу подложки
     */
    protected function taskReportBackgroundFile(array $theme, $orientation, $tempDir)
    {
        $width = $orientation == 'P' ? 210 : 297;
        $height = $orientation == 'P' ? 297 : 210;
        $gradientFrom = (string)ArrayHelper::getValue($theme, 'gradient_from', ArrayHelper::getValue($theme, 'background_color'));
        $gradientTo = (string)ArrayHelper::getValue($theme, 'gradient_to', ArrayHelper::getValue($theme, 'background_color'));
        $accent = (string)ArrayHelper::getValue($theme, 'accent_color');
        $accentAlt = (string)ArrayHelper::getValue($theme, 'accent_alt_color', $accent);

        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $dir = $tempDir.DIRECTORY_SEPARATOR.'report-backgrounds';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $key = md5(implode('|', [$width, $height, $gradientFrom, $gradientTo, $accent, $accentAlt, 'v2']));
        $file = $dir.DIRECTORY_SEPARATOR.$key.'.pdf';
        if (is_file($file)) {
            return $file;
        }

        $image = $dir.DIRECTORY_SEPARATOR.$key.'.png';

        try {
            if (!is_file($image)) {
                $this->renderTaskReportBackgroundImage($image, $width, $height, $gradientFrom, $gradientTo, $accent, $accentAlt);
            }

            $background = new \Mpdf\Mpdf([
                'tempDir'       => $tempDir,
                'format'        => 'A4',
                'orientation'   => $orientation,
                'margin_top'    => 0,
                'margin_right'  => 0,
                'margin_bottom' => 0,
                'margin_left'   => 0,
            ]);
            // Прямая вставка по координатам: в потоке документа изображение
            // подгоняется под область печати и оставляет белые поля.
            $background->AddPage();
            $background->Image($image, 0, 0, $width, $height, 'png', '', true, false);
            $background->Output($file, 'F');
        } catch (\Exception $e) {
            \Yii::warning('Не удалось подготовить подложку отчёта: '.$e->getMessage(), self::class);

            return null;
        }

        return is_file($file) ? $file : null;
    }

    /**
     * Изображение подложки: вертикальный градиент и фирменное кольцо в углу.
     *
     * @param string $file    куда сохранить PNG
     * @param int    $width   ширина страницы, мм
     * @param int    $height  высота страницы, мм
     * @param string $from    верхний цвет градиента
     * @param string $to      нижний цвет градиента
     * @param string $accent
     * @param string $accentAlt
     *
     * @return void
     */
    protected function renderTaskReportBackgroundImage($file, $width, $height, $from, $to, $accent, $accentAlt)
    {
        $hexToRgb = function ($hex) {
            $hex = ltrim((string)$hex, '#');
            if (strlen($hex) == 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            if (strlen($hex) != 6) {
                $hex = '000000';
            }

            return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        };

        $fromRgb = $hexToRgb($from);
        $toRgb = $hexToRgb($to);
        $accentRgb = $hexToRgb($accent);
        $accentAltRgb = $hexToRgb($accentAlt);

        // Рисуем с двукратным запасом и уменьшаем: так сглаживаются края кольца.
        $scale = 2;
        $pageWidth = (int)round($width * 96 / 25.4);
        $pageHeight = (int)round($height * 96 / 25.4);
        $canvasWidth = $pageWidth * $scale;
        $canvasHeight = $pageHeight * $scale;

        $canvas = imagecreatetruecolor($canvasWidth, $canvasHeight);

        for ($y = 0; $y < $canvasHeight; $y++) {
            $ratio = $canvasHeight > 1 ? $y / ($canvasHeight - 1) : 0;
            $color = imagecolorallocate(
                $canvas,
                (int)round($fromRgb[0] + ($toRgb[0] - $fromRgb[0]) * $ratio),
                (int)round($fromRgb[1] + ($toRgb[1] - $fromRgb[1]) * $ratio),
                (int)round($fromRgb[2] + ($toRgb[2] - $fromRgb[2]) * $ratio)
            );
            imagefilledrectangle($canvas, 0, $y, $canvasWidth, $y, $color);
        }

        $pixelsPerMm = $canvasWidth / $width;
        $centerX = ($width - 12) * $pixelsPerMm;
        $centerY = 4 * $pixelsPerMm;
        $outerRadius = 34 * $pixelsPerMm;
        $innerRadius = 19 * $pixelsPerMm;

        $left = max(0, (int)floor($centerX - $outerRadius));
        $right = min($canvasWidth - 1, (int)ceil($centerX + $outerRadius));
        $top = max(0, (int)floor($centerY - $outerRadius));
        $bottom = min($canvasHeight - 1, (int)ceil($centerY + $outerRadius));

        for ($y = $top; $y <= $bottom; $y++) {
            for ($x = $left; $x <= $right; $x++) {
                $dx = $x - $centerX;
                $dy = $y - $centerY;
                $distance = sqrt($dx * $dx + $dy * $dy);
                if ($distance > $outerRadius || $distance < $innerRadius) {
                    continue;
                }

                $ratio = min(1, max(0, ($dx + $dy + $outerRadius) / (2 * $outerRadius)));
                imagesetpixel($canvas, $x, $y, imagecolorallocate(
                    $canvas,
                    (int)round($accentRgb[0] + ($accentAltRgb[0] - $accentRgb[0]) * $ratio),
                    (int)round($accentRgb[1] + ($accentAltRgb[1] - $accentRgb[1]) * $ratio),
                    (int)round($accentRgb[2] + ($accentAltRgb[2] - $accentRgb[2]) * $ratio)
                ));
            }
        }

        $page = imagecreatetruecolor($pageWidth, $pageHeight);
        imagecopyresampled($page, $canvas, 0, 0, 0, 0, $pageWidth, $pageHeight, $canvasWidth, $canvasHeight);
        imagepng($page, $file, 6);
        imagedestroy($canvas);
        imagedestroy($page);
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
        if ($model->isNewRecord && $model->cms_lead_id) {
            $this->applyLeadToNewTask($model);
        }
        $hasDeal = \skeeks\cms\models\queries\CmsTaskQuery::hasDealColumn();
        //A task created in a deal: show the deal's company (or client) in the relation switcher.
        if ($hasDeal && $model->isNewRecord && $model->cms_deal_id && !$model->cms_company_id && !$model->cms_user_id && !$model->cms_project_id) {
            $deal = CmsDeal::find()->forManager()->andWhere([CmsDeal::tableName().'.id' => (int)$model->cms_deal_id])->one();
            if ($deal) {
                $model->cms_company_id = $deal->cms_company_id ?: null;
                $model->cms_user_id = $deal->cms_company_id ? null : $deal->cms_user_id;
            } else {
                $model->cms_deal_id = null;
            }
        }
        $isExecutorLocked = !$model->isNewRecord && CmsTaskSchedule::find()->task($model)->exists();

        if ($model->hasAttribute('remind_at') && $model->reminder === '' && !\Yii::$app->request->isPost) {
            $model->reminder = 'none';
        }

        // A start given by the planner is not a person's choice: the form shows "automatically" for it.
        if (!$model->isNewRecord && $model->hasAttribute('is_fixed_start') && !$model->is_fixed_start && !\Yii::$app->request->isPost) {
            $model->plan_start_at = null;
        }

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

function updateTaskReminderFields() {
    var choice = $("#cmstask-reminder").val();
    $(".field-cmstask-remind_at").toggle(choice === "custom");
    $(".field-cmstask-remind_author").toggle(!!choice && choice !== "none");
}
$("body").on("change", "#cmstask-reminder", updateTaskReminderFields);
$(document).on("pjax:complete", function () { setTimeout(updateTaskReminderFields, 200); });
updateTaskReminderFields();

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
                'widgetClass'  => DateTimeInputWidget::class,
                'widgetConfig' => [
                    'type'       => DateTimeInputWidget::TYPE_DATETIME,
                    'saveFormat' => 'php:U',
                    'minuteStep' => 5,
                ],
            ],

            'auto_start_at_trigger' => [
                'class' => HtmlBlock::class,
                'content' => '<div class="col-12 sx-task-auto-start-actions"><button type="button" class="btn btn-default btn-sm sx-task-auto-start-btn"><i class="fas fa-magic"></i> Рассчитать время выполнения автоматически</button></div>',
            ],

            // A reminder: a notification in the CRM and in the channels each person chose (MAX, Telegram, mail).
            // A plain select: the form's select widget drops the «none» item and its label.
            'reminder' => [
                'class'   => HtmlBlock::class,
                'content' => '<div class="col-12 form-group field-cmstask-reminder">'
                    .Html::activeLabel($model, 'reminder', ['class' => 'control-label'])
                    .Html::activeDropDownList($model, 'reminder', ['none' => 'Не напоминать'] + CmsTask::reminderOffsets() + ['custom' => 'В своё время…'], ['class' => 'form-control'])
                    .'<div class="hint-block">Придёт важным уведомлением: в CRM и туда, куда вы настроили уведомления (MAX, Telegram, почта).</div>'
                    .'</div>',
            ],

            'remind_at' => [
                'class'        => WidgetField::class,
                'label'        => 'Когда напомнить',
                'widgetClass'  => DateTimeInputWidget::class,
                'widgetConfig' => [
                    'type'       => DateTimeInputWidget::TYPE_DATETIME,
                    'saveFormat' => 'php:U',
                    'minuteStep' => 5,
                ],
            ],

            'remind_author' => [
                'class' => BoolField::class,
                'label' => 'Напомнить и мне (автору задачи)',
                'allowNull' => false,
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

            // The deal refines the client, so it stands under the company / client, before the service:
            // open deals available to the employee, of the chosen company or client (CmsTask::validateLead()).
            'cms_lead_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'widgetConfig' => [
                    'id'          => 'cmstask-lead-relation-select',
                    'modelClass'  => CmsLead::class,
                    'searchQuery' => function ($word = '') use ($model) {
                        $query = CmsLead::find()->forManager();
                        $companyId = (int)\Yii::$app->request->get('cms_company_id', $model->cms_company_id);
                        $userId = (int)\Yii::$app->request->get('cms_user_id', $model->cms_user_id);
                        if ($companyId) {
                            $query->andWhere([CmsLead::tableName().'.cms_company_id' => $companyId]);
                        } elseif ($userId) {
                            $query->andWhere([CmsLead::tableName().'.cms_user_id' => $userId, CmsLead::tableName().'.cms_company_id' => null]);
                        }
                        // Open deals; the deal already linked stays choosable even when it is closed.
                        $query->andWhere(['or',
                            ['not in', CmsLead::tableName().'.status', [CmsLead::STATUS_SUCCESS, CmsLead::STATUS_REJECTED]],
                            [CmsLead::tableName().'.id' => (int)$model->cms_lead_id],
                        ]);
                        if ($word) {
                            $query->search($word);
                        }
                        return $query->orderBy([CmsLead::tableName().'.id' => SORT_DESC]);
                    },
                    'pluginOptions' => [
                        'ajax' => [
                            'data' => new JsExpression('function(params) { return {q: params.term, cms_company_id: $(".cms_company_id-btn").hasClass("sx-active") ? $("#cmstask-cms_company_id").val() : "", cms_user_id: $(".cms_user_id-btn").hasClass("sx-active") ? $("#cmstask-cms_user_id").val() : ""}; }'),
                        ],
                    ],
                ],
            ],

            //"Сделка как услуга": the service of the task.
            'cms_deal_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxSelectModel::class,
                'hint'         => 'Задача по услуге видна клиенту, если ему открыты задачи этой услуги',
                'widgetConfig' => [
                    'id'          => 'cmstask-deal-relation-select',
                    'modelClass'  => CmsDeal::class,
                    'searchQuery' => function ($word = '') use ($model) {
                        $query = CmsDeal::find()->forManager();
                        $companyId = (int)\Yii::$app->request->get('cms_company_id', $model->cms_company_id);
                        $userId = (int)\Yii::$app->request->get('cms_user_id', $model->cms_user_id);
                        if ($companyId) {
                            $query->andWhere([CmsDeal::tableName().'.cms_company_id' => $companyId]);
                        } elseif ($userId) {
                            $query->andWhere([CmsDeal::tableName().'.cms_user_id' => $userId, CmsDeal::tableName().'.cms_company_id' => null]);
                        }
                        if ($word) {
                            $query->andWhere(['like', CmsDeal::tableName().'.name', $word]);
                        }
                        return $query->orderBy([CmsDeal::tableName().'.is_active' => SORT_DESC, CmsDeal::tableName().'.id' => SORT_DESC]);
                    },
                    'pluginOptions' => [
                        'ajax' => [
                            'data' => new JsExpression('function(params) { return {q: params.term, cms_company_id: $(".cms_company_id-btn").hasClass("sx-active") ? $("#cmstask-cms_company_id").val() : "", cms_user_id: $(".cms_user_id-btn").hasClass("sx-active") ? $("#cmstask-cms_user_id").val() : ""}; }'),
                        ],
                    ],
                ],
            ],

            'dealResourceKey' => [
                'class' => SelectField::class,
                'label' => 'Ресурс услуги',
                'items' => $this->dealResourceItems($model),
            ],

            'is_internal' => [
                'class'       => BoolField::class,
                'allowNull'   => false,
                'formElement' => BoolField::ELEMENT_CHECKBOX,
                'hint'        => 'Задача по услуге, которую клиенту показывать не нужно',
            ],



            'description' => [
                // CmsTask cleans it with the same preset (RichTextBehavior).
                'class'        => WidgetField::class,
                'widgetClass'  => RichTextInputWidget::class,
                'widgetConfig' => [
                    'preset'    => 'full',
                    'minHeight' => 260,
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

        if (!$hasDeal) {
            unset($result['cms_deal_id'], $result['dealResourceKey'], $result['is_internal']);
        } else {
            if (!$result['dealResourceKey']['items']) {
                unset($result['dealResourceKey']);
            }
            //Another company or client: the deal no longer fits; another deal: its resources are not listed yet.
            $this->view->registerJs(<<<JS
$("body").on("select2:select select2:unselect", "#cmstask-cms_company_id, #cmstask-cms_user_id", function() {
    $("#cmstask-cms_deal_id").val("").trigger("change");
});
$("body").on("change", "#cmstask-cms_deal_id", function() {
    $(".field-cmstask-dealresourcekey").slideUp().find("select").val("");
});
JS
            );
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

    /**
     * Resources of the task's deal for the "Ресурс сделки" select.
     */
    protected function dealResourceItems(CmsTask $model): array
    {
        $items = [];
        if (!\skeeks\cms\models\queries\CmsTaskQuery::hasDealColumn() || !$model->cms_deal_id || !($deal = CmsDeal::findOne((int)$model->cms_deal_id))) {
            return $items;
        }
        /** @var \skeeks\cms\deal\DealAccess $dealAccess */
        $dealAccess = \Yii::$app->get('dealAccess');
        foreach ($dealAccess->resources($deal) as $resource) {
            $provider = $dealAccess->getProvider($resource->type);
            $items[$resource->type.':'.$resource->id] = ($provider ? $provider->label().': ' : '').$resource->title;
        }
        return $items;
    }

    /**
     * Moves a task in time from the task views («Сроки», calendar, Gantt): POST plan_start_at (unix time)
     * and optionally plan_duration (seconds), or mode=auto to give the start back to the planner.
     * A moved task becomes fixed; a closed task and a task in work do not move.
     */
    public function actionReschedule($pk)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $request = \Yii::$app->request;
        if (!$request->isPost) {
            throw new \yii\web\MethodNotAllowedHttpException('POST only.');
        }

        $query = CmsTask::find()->andWhere([CmsTask::tableName().'.id' => (int)$pk]);
        self::initQuery($query);
        $task = $query->one();
        if (!$task) {
            throw new \yii\web\NotFoundHttpException('Задача не найдена.');
        }
        if (!\Yii::$app->user->can('cms/admin-task/manage', ['model' => $task])) {
            return ['success' => false, 'message' => 'Нет прав менять эту задачу.'];
        }
        if (!in_array($task->status, CmsTask::activeStatuses(), true) || $task->status === CmsTask::STATUS_IN_WORK) {
            return ['success' => false, 'message' => $task->status === CmsTask::STATUS_IN_WORK
                ? 'Задача в работе — её время не двигается.' : 'Закрытую задачу нельзя перенести.'];
        }

        if ($request->post('plan_duration') !== null && (int)$request->post('plan_duration') > 0) {
            $task->plan_duration = (int)$request->post('plan_duration');
        }

        $day = (string)$request->post('day', '');
        if ($request->post('plan_start_at') === null && $day === '' && $request->post('mode') === null) {
            // Only the duration (the right edge of a Gantt bar): the start and its mode stay.
            if ((int)$request->post('plan_duration') <= 0) {
                return ['success' => false, 'message' => 'Не указано время начала.'];
            }
        } elseif ($request->post('mode') === 'auto') {
            $task->plan_start_at = null;
        } elseif ($day !== '') {
            // A day (a column of «Сроки»): the first free time of the executor on that day or after it.
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
                return ['success' => false, 'message' => 'Неверный день.'];
            }
            $dayStart = (new \DateTime($day.' 00:00:00', new \DateTimeZone(\Yii::$app->formatter->timeZone)))->getTimestamp();
            $task->plan_start_at = (new \skeeks\cms\services\TaskPlanner())->nearestStart(
                $task->executor, max(900, (int)$task->plan_duration), max($dayStart, time()), (int)$task->id
            );
            $task->is_fixed_start = 1;
        } else {
            $startAt = (int)$request->post('plan_start_at');
            if ($startAt <= 0) {
                return ['success' => false, 'message' => 'Не указано время начала.'];
            }
            $task->plan_start_at = $startAt;
            $task->is_fixed_start = 1;
        }

        if (!$task->save()) {
            return ['success' => false, 'message' => implode(' ', $task->getFirstErrors())];
        }
        $task->refresh();

        return [
            'success'  => true,
            'task'     => [
                'id'             => (int)$task->id,
                'plan_start_at'  => (int)$task->plan_start_at,
                'plan_end_at'    => (int)$task->plan_end_at,
                'plan_duration'  => (int)$task->plan_duration,
                'is_fixed_start' => (int)$task->is_fixed_start,
                'start_text'     => \Yii::$app->formatter->asDate((int)$task->plan_start_at, 'php:j F').' '.\Yii::$app->formatter->asTime((int)$task->plan_start_at, 'php:H:i'),
            ],
            'warnings' => (new \skeeks\cms\services\TaskPlanner())->warnings($task),
        ];
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
        $model->cms_lead_id = $parentTask->cms_lead_id;
        $model->plan_duration = $parentTask->plan_duration ?: $model->plan_duration;
    }

    /**
     * Новая задача из карточки лида: лид должен быть доступен сотруднику,
     * а компания или клиент лида подставляются, если связь ещё не выбрана.
     */
    /**
     * «Сделка» of the task form: a deal chosen anew must be available to the employee
     * (forManager); otherwise the previous one stays. The link to the client is
     * checked by CmsTask::validateLead().
     */
    protected function keepAvailableLead(CmsTask $model): void
    {
        $leadId = (int)$model->cms_lead_id;
        if (!$leadId || (!$model->isNewRecord && $leadId === (int)$model->getOldAttribute('cms_lead_id'))) {
            return;
        }
        if (!CmsLead::find()->forManager()->andWhere([CmsLead::tableName().'.id' => $leadId])->exists()) {
            $model->cms_lead_id = $model->isNewRecord ? null : $model->getOldAttribute('cms_lead_id');
        }
    }

    protected function applyLeadToNewTask(CmsTask $model): void
    {
        $lead = CmsLead::find()->forManager()->andWhere([CmsLead::tableName().'.id' => (int)$model->cms_lead_id])->one();
        if (!$lead) {
            $model->cms_lead_id = null;
            return;
        }

        if (!$model->cms_company_id && !$model->cms_user_id && !$model->cms_project_id) {
            if ($lead->cms_company_id) {
                $model->cms_company_id = (int)$lead->cms_company_id;
            } elseif ($lead->cms_user_id) {
                $model->cms_user_id = (int)$lead->cms_user_id;
            }
        }
    }

    /**
     * Прогноз завершения новой задачи с учетом текущей очереди и рабочего графика исполнителя.
     */
    protected function calculateEstimatedTaskEndAt(CmsUser $user, int $duration, ?int $excludeTaskId = null): ?int
    {
        if ($duration <= 0) {
            return null;
        }

        return (new \skeeks\cms\services\TaskPlanner())->estimate($user, $duration, $excludeTaskId)[1];
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
