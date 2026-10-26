<?php
/**
 * Client support built on the standard SkeekS backend model layer.
 */

namespace skeeks\cms\controllers;

use skeeks\cms\support\SupportTaskExecutorResolver;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\behaviors\RichTextBehavior;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\CmsScheduleHelper;
use skeeks\cms\deal\DealAccess;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\queries\CmsTaskQuery;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsProject2user;
use skeeks\cms\models\CmsTask;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\admin\CmsTaskStatusWidget;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\UnsetArrayValue;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UpaSupportController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Поддержка';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsTask::class;
        $this->modelDefaultAction = 'view';
        $this->modelHeader = function () {
            return $this->renderPartial('@skeeks/cms/views/admin-cms-task/_model_header', [
                'model' => $this->model,
                'showRelations' => false,
            ]);
        };

        $this->permissionName = Cms::UPA_PERMISSION;
        $this->permissionNames = [
            Cms::UPA_PERMISSION => 'Доступ к персональной части',
        ];
        $this->generateAccessActions = false;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'name' => 'Задачи и поддержка',
                'configKey' => 'upa-client-v2/support',
                'presentationMode' => 'auto',
                'pageHeader' => [
                    'title' => 'Задачи и поддержка',
                    'description' => 'Следите за ходом задач, задавайте вопросы и общайтесь с нашей командой.',
                ],
                'emptyState' => [
                    'title' => 'Задач пока нет',
                    'description' => 'Опишите вопрос или новую задачу — она сразу появится у нашей команды.',
                    'icon' => 'far fa-comment-dots',
                    'action' => [
                        'backendAction' => 'create',
                        'label' => 'Создать задачу',
                    ],
                ],
                'noResultsState' => [
                    'title' => 'Задачи не найдены',
                    'description' => 'Измените поисковый запрос или выбранные фильтры.',
                    'icon' => 'fas fa-search',
                ],
                'filters' => [
                    'visibleFilters' => [
                        'q',
                        'scope',
                        'cms_project_id',
                        'status',
                    ],
                    'filtersModel' => [
                        'rules' => [
                            [['q', 'scope'], 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'scope',
                        ],
                        'fields' => [
                            'q' => [
                                'label' => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Название, описание, комментарий или номер задачи',
                                ],
                                'on apply' => function (QueryFiltersEvent $event) {
                                    $value = trim((string)$event->field->value);
                                    if ($value === '') {
                                        return;
                                    }

                                    /** @var ActiveQuery $query */
                                    $query = $event->dataProvider->query;
                                    $logsQuery = CmsLog::find()
                                        ->select('model_id')
                                        ->andWhere([
                                            'model_code' => CmsTask::class,
                                        ])
                                        ->andWhere(['like', 'comment', $value]);

                                    $conditions = [
                                        'or',
                                        [CmsTask::tableName().'.id' => $logsQuery],
                                        ['like', CmsTask::tableName().'.name', $value],
                                        ['like', CmsTask::tableName().'.description', $value],
                                    ];

                                    if (ctype_digit($value)) {
                                        $conditions[] = [CmsTask::tableName().'.id' => (int)$value];
                                    }

                                    $query->andWhere($conditions);
                                },
                            ],
                            'scope' => [
                                'class' => SelectField::class,
                                'label' => 'Показывать',
                                'items' => [
                                    'all' => 'Все доступные задачи',
                                    'created' => 'Созданные мной',
                                    'projects' => 'По моим проектам',
                                ],
                                'on apply' => function (QueryFiltersEvent $event) {
                                    /** @var ActiveQuery $query */
                                    $query = $event->dataProvider->query;

                                    if ($event->field->value === 'created') {
                                        $query->andWhere([
                                            CmsTask::tableName().'.created_by' => \Yii::$app->user->id,
                                        ]);
                                    } elseif ($event->field->value === 'projects') {
                                        $query->andWhere([
                                            CmsTask::tableName().'.cms_project_id' => $this->clientProjectIdsQuery(),
                                        ]);
                                    }
                                },
                            ],
                            'cms_project_id' => [
                                'field' => [
                                    'widgetConfig' => [
                                        'searchQuery' => function ($word = '') {
                                            $query = $this->clientProjectsQuery();
                                            if ($word) {
                                                $query->search($word);
                                            }
                                            return $query;
                                        },
                                    ],
                                ],
                            ],
                            'status' => [
                                'defaultMode' => FilterModeEq::ID,
                                'isAllowChangeMode' => false,
                                'field' => [
                                    'class' => SelectField::class,
                                    'items' => CmsTask::statuses(),
                                    'multiple' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                'grid' => [
                    'presentation' => 'client',
                    'on init' => function (Event $event) {
                        /** @var ActiveQuery $query */
                        $query = $event->sender->dataProvider->query;
                        $this->applyClientTaskAccess($query);

                        $taskTable = CmsTask::tableName();
                        $work = CmsTask::STATUS_IN_WORK;
                        $new = CmsTask::STATUS_NEW;
                        $accepted = CmsTask::STATUS_ACCEPTED;
                        $pause = CmsTask::STATUS_ON_PAUSE;
                        $onCheck = CmsTask::STATUS_ON_CHECK;
                        $ready = CmsTask::STATUS_READY;
                        $canceled = CmsTask::STATUS_CANCELED;

                        $query->addSelect([
                            'client_status_sort' => new Expression("
                                CASE
                                    WHEN {$taskTable}.status = '{$new}' THEN 1
                                    WHEN {$taskTable}.status IN ('{$work}', '{$accepted}', '{$pause}', '{$onCheck}') THEN 2
                                    WHEN {$taskTable}.status = '{$ready}' THEN 4
                                    WHEN {$taskTable}.status = '{$canceled}' THEN 5
                                    ELSE 3
                                END
                            "),
                        ]);
                        $query->groupBy([$taskTable.'.id']);
                        $query->with(['cmsCompany', 'cmsProject']);
                    },
                    'defaultPageSize' => 50,
                    'defaultOrder' => [
                        'client_status_sort' => SORT_ASC,
                        'created_at' => SORT_DESC,
                    ],
                    'sortAttributes' => [
                        'client_status_sort' => [
                            'asc' => ['client_status_sort' => SORT_ASC],
                            'desc' => ['client_status_sort' => SORT_DESC],
                            'name' => 'Приоритет',
                        ],
                    ],
                    'visibleColumns' => [
                        'task',
                        'status',
                        'relation',
                        'created_at',
                    ],
                    'columns' => [
                        'task' => [
                            'label' => 'Задача',
                            'format' => 'raw',
                            'headerOptions' => [
                                'style' => 'min-width: 360px;',
                            ],
                            'value' => static function (CmsTask $model) {
                                $url = (string)BackendUrlHelper::createByParams([
                                    '/cms/upa-support/view',
                                    'pk' => $model->id,
                                ])
                                    ->enableEmptyLayout()
                                    ->enableNoActions()
                                    ->url;
                                $actionData = Json::encode([
                                    'isOpenNewWindow' => true,
                                    'url' => $url,
                                ]);
                                $estimate = $model->cmsProject && $model->cmsProject->is_work_time_visible_for_clients
                                    ? CmsScheduleHelper::durationAsText((int)$model->plan_duration)
                                    : '';
                                $secondary = $estimate ? 'Оценка: '.$estimate : 'Открыть задачу';
                                $content = Html::tag(
                                    'span',
                                    '<i class="far fa-comment-alt" aria-hidden="true"></i>',
                                    ['class' => 'sx-collection-cell__media']
                                ).Html::tag(
                                    'span',
                                    Html::tag('strong', Html::encode($model->name), [
                                        'class' => 'sx-collection-cell__primary',
                                    ]).Html::tag('small', Html::encode($secondary), [
                                        'class' => 'sx-collection-cell__secondary',
                                    ]),
                                    ['class' => 'sx-collection-cell sx-collection-cell--stack']
                                );

                                return Html::a($content, $url, [
                                    'class' => 'sx-collection-cell sx-collection-cell--entity',
                                    'data-pjax' => '0',
                                    'onclick' => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
                                ]);
                            },
                        ],
                        'status' => [
                            'label' => 'Статус',
                            'format' => 'raw',
                            'value' => static function (CmsTask $model) {
                                return CmsTaskStatusWidget::widget([
                                    'task' => $model,
                                    'showScheduleDetails' => false,
                                ]);
                            },
                        ],
                        'relation' => [
                            'label' => 'Связь',
                            'format' => 'raw',
                            'value' => static function (CmsTask $model) {
                                $parts = [];
                                if ($model->hasAttribute('cms_deal_id') && $model->cms_deal_id && $model->cmsDeal) {
                                    $parts[] = Html::tag(
                                        'span',
                                        Html::tag('strong', 'Услуга: ').Html::encode($model->cmsDeal->name),
                                        ['class' => 'sx-collection-cell__relations']
                                    );
                                }
                                if ($model->cmsCompany) {
                                    $parts[] = Html::tag(
                                        'span',
                                        Html::tag('strong', 'Компания: ').Html::encode($model->cmsCompany->name),
                                        ['class' => 'sx-collection-cell__relations']
                                    );
                                }
                                if ($model->cmsProject) {
                                    $parts[] = Html::tag(
                                        'span',
                                        Html::tag('strong', 'Проект: ').Html::encode($model->cmsProject->name),
                                        ['class' => 'sx-collection-cell__relations']
                                    );
                                }

                                return $parts
                                    ? Html::tag('span', implode('', $parts), [
                                        'class' => 'sx-collection-cell sx-collection-cell--stack',
                                    ])
                                    : Html::tag('span', '—', ['class' => 'sx-collection-cell__secondary']);
                            },
                        ],
                        'created_at' => [
                            'label' => 'Создана',
                            'attribute' => 'created_at',
                            'format' => 'raw',
                            'value' => static function (CmsTask $model) {
                                return Html::tag(
                                    'time',
                                    Html::tag('strong', \Yii::$app->formatter->asDate($model->created_at)).
                                    Html::tag('small', \Yii::$app->formatter->asTime($model->created_at, 'short')),
                                    ['class' => 'sx-collection-cell__date']
                                );
                            },
                        ],
                    ],
                ],
            ],
            'create' => [
                'name' => 'Новая задача',
                'fields' => [$this, 'createFields'],
                'on beforeValidate' => function (Event $event) {
                    $this->prepareClientTask($event->sender->model);
                },
            ],
            'view' => [
                'class' => BackendModelAction::class,
                'name' => 'Карточка задачи',
                'icon' => 'fa fa-eye',
                'callback' => [$this, 'view'],
                'priority' => 10,
            ],
            'update' => new UnsetArrayValue(),
            'delete' => new UnsetArrayValue(),
            'delete-multi' => new UnsetArrayValue(),
        ]);
    }

    public function createFields($action): array
    {
        /** @var CmsTask $model */
        $model = $action->model;
        $model->scenario = CmsTask::SCENARIO_CLIENT_SUPPORT;
        $model->cms_user_id = \Yii::$app->user->id;
        $model->plan_duration = $model->plan_duration ?: 60 * 15;

        $companies = CmsCompany::find()
            ->forClient()
            ->orderBy([CmsCompany::tableName().'.name' => SORT_ASC])
            ->limit(2)
            ->all();
        if (!$model->cms_company_id && count($companies) === 1) {
            $model->cms_company_id = (int)$companies[0]->id;
        }

        $hasProjects = $this->clientProjectsQuery()->exists();
        $projectsQuery = $this->clientProjectsQuery();
        if ($model->cms_company_id) {
            $projectsQuery->andWhere([
                CmsProject::tableName().'.cms_company_id' => (int)$model->cms_company_id,
            ]);
        }

        $projects = $projectsQuery
            ->orderBy([CmsProject::tableName().'.name' => SORT_ASC])
            ->limit(2)
            ->all();
        if (!$model->cms_project_id && count($projects) === 1) {
            $model->cms_project_id = (int)$projects[0]->id;
        }

        $deals = $this->clientTaskDealsQuery()->limit(2)->all();
        if (CmsTaskQuery::hasDealColumn() && !$model->cms_deal_id && count($deals) === 1) {
            $model->cms_deal_id = (int)$deals[0]->id;
        }

        $model->executor_id = $this->resolveExecutorId($model);

        $fields = [
            'name',
            'description' => [
                'class' => TextareaField::class,
                'elementOptions' => [
                    'rows' => 8,
                    'placeholder' => 'Опишите задачу, проблему или пожелание',
                ],
            ],
            'cms_deal_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'label' => 'Услуга',
                'widgetConfig' => [
                    'modelClass' => CmsDeal::class,
                    'searchQuery' => function ($word = '') {
                        $query = $this->clientTaskDealsQuery();
                        if ($word) {
                            $query->andWhere(['like', CmsDeal::tableName().'.name', $word]);
                        }
                        return $query;
                    },
                ],
            ],
            'cms_company_id' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxSelectModel::class,
                'widgetConfig' => [
                    'modelClass' => CmsCompany::class,
                    'searchQuery' => function ($word = '') {
                        $query = CmsCompany::find()->forClient();
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
                    'modelClass' => CmsProject::class,
                    'searchQuery' => function ($word = '') use ($model) {
                        $query = $this->clientProjectsQuery();
                        if ($model->cms_company_id) {
                            $query->andWhere([
                                CmsProject::tableName().'.cms_company_id' => (int)$model->cms_company_id,
                            ]);
                        }
                        if ($word) {
                            $query->search($word);
                        }
                        return $query;
                    },
                ],
            ],
            'fileIds' => [
                'class' => WidgetField::class,
                'widgetClass' => AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'multiple' => true,
                ],
            ],
        ];

        if (!$deals) {
            unset($fields['cms_deal_id']);
        } else {
            //The service decides the company.
            unset($fields['cms_company_id']);
        }

        if (!$companies) {
            unset($fields['cms_company_id']);
        }

        if (!$hasProjects) {
            unset($fields['cms_project_id']);
        }

        return $fields;
    }

    public function getModel()
    {
        if ($this->_model === null && $pk = \Yii::$app->request->get($this->requestPkParamName)) {
            $this->_model = $this->clientTasksQuery()
                ->andWhere([CmsTask::tableName().'.'.$this->modelPkAttribute => $pk])
                ->one();

            if (!$this->_model) {
                throw new NotFoundHttpException('Задача не найдена.');
            }
        }

        return $this->_model;
    }

    public function view()
    {
        return $this->render('@skeeks/cms/views/upa-support/view', [
            'model' => $this->model,
            'comment' => $this->createCommentModel($this->model),
            'canComment' => $this->canComment($this->model),
        ]);
    }

    /**
     * A task of a deal is commented with "Задачи: комментировать"; own tasks and
     * tasks of projects the client takes part in — as before.
     */
    protected function canComment(CmsTask $model): bool
    {
        return $this->clientTasksQuery(DealAccess::LEVEL_ACT)
            ->andWhere([CmsTask::tableName().'.id' => (int)$model->id])
            ->exists();
    }

    public function actionAddComment($pk)
    {
        try {
            $model = $this->clientTasksQuery()
                ->andWhere([CmsTask::tableName().'.id' => (int)$pk])
                ->one();

            if (!$model) {
                throw new NotFoundHttpException('Задача не найдена.');
            }

            if (!$this->canComment($model)) {
                throw new Exception('В этой услуге вам открыт только просмотр задач.');
            }

            $comment = $this->createCommentModel($model);
            if (!$comment->load(\Yii::$app->request->post())) {
                throw new Exception('Не удалось добавить комментарий.');
            }

            $comment->is_pinned = 0;
            if (!$comment->save()) {
                $errors = $comment->getFirstErrors();
                throw new Exception($errors ? array_shift($errors) : 'Не удалось добавить комментарий.');
            }

            if (!\Yii::$app->request->isAjax) {
                \Yii::$app->session->setFlash('success', 'Комментарий добавлен.');
                return $this->redirect(['view', 'pk' => $model->id]);
            }

            \Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => true,
                'message' => 'Комментарий добавлен.',
            ];
        } catch (\Exception $exception) {
            if (!\Yii::$app->request->isAjax) {
                \Yii::$app->session->setFlash('error', $exception->getMessage());
                return $this->redirect(['index']);
            }

            \Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function applyClientTaskAccess(ActiveQuery $query, int $level = DealAccess::LEVEL_VIEW): ActiveQuery
    {
        $taskTable = CmsTask::tableName();

        //"Сделка как услуга": tasks of the client's deals, own tasks, tasks of projects the client takes part in.
        if ($query instanceof CmsTaskQuery && CmsTaskQuery::hasDealColumn()) {
            return $query->forClient(\Yii::$app->user->identity, $level);
        }

        return $query->andWhere([
            'or',
            [$taskTable.'.created_by' => \Yii::$app->user->id],
            [$taskTable.'.cms_project_id' => $this->clientProjectIdsQuery()],
        ]);
    }

    protected function clientTasksQuery(int $level = DealAccess::LEVEL_VIEW): ActiveQuery
    {
        return $this->applyClientTaskAccess(CmsTask::find(), $level);
    }

    /**
     * Deals where the client may create tasks ("Задачи": ставить задачи).
     */
    protected function clientTaskDealsQuery(): ActiveQuery
    {
        if (!CmsTaskQuery::hasDealColumn()) {
            return CmsDeal::find()->andWhere('1=0');
        }
        return CmsDeal::find()->forClient(\Yii::$app->user->identity, DealAccess::AREA_TASKS, DealAccess::LEVEL_FULL);
    }

    protected function clientProjectsQuery(): ActiveQuery
    {
        return CmsProject::find()->andWhere([
            CmsProject::tableName().'.id' => $this->clientProjectIdsQuery(),
        ]);
    }

    protected function clientProjectIdsQuery(): ActiveQuery
    {
        return CmsProject2user::find()
            ->select('cms_project_id')
            ->andWhere(['cms_user_id' => \Yii::$app->user->id]);
    }

    protected function createCommentModel(CmsTask $model): CmsLog
    {
        $comment = new CmsLog([
            'log_type' => CmsLog::LOG_TYPE_COMMENT,
            'model_code' => CmsTask::class,
            'model_id' => $model->id,
            'model_as_text' => $model->asText,
            'cms_company_id' => $model->cms_company_id ?: ($model->cmsProject ? $model->cmsProject->cms_company_id : null),
            'cms_user_id' => \Yii::$app->user->id,
        ]);
        // The comment form uses RichTextInputWidget with the "basic" preset.
        $comment->attachBehavior('richText', ['class' => RichTextBehavior::class, 'attributes' => ['comment' => 'basic']]);

        return $comment;
    }

    protected function prepareClientTask(CmsTask $model): void
    {
        $model->scenario = CmsTask::SCENARIO_CLIENT_SUPPORT;
        $model->cms_user_id = \Yii::$app->user->id;
        $model->status = CmsTask::STATUS_NEW;
        $model->plan_start_at = null;

        //A task in a service: only in a deal where the client may create tasks; the deal gives the company.
        if (CmsTaskQuery::hasDealColumn()) {
            $model->is_internal = 0;
            $model->deal_resource_type = null;
            $model->deal_resource_id = null;
            $deal = $model->cms_deal_id
                ? $this->clientTaskDealsQuery()->andWhere([CmsDeal::tableName().'.id' => (int)$model->cms_deal_id])->one()
                : null;
            $model->cms_deal_id = $deal ? (int)$deal->id : null;
            if ($deal && $deal->cms_company_id) {
                $model->cms_company_id = (int)$deal->cms_company_id;
                if ($model->cms_project_id) {
                    $project = $this->clientProjectsQuery()->andWhere([CmsProject::tableName().'.id' => (int)$model->cms_project_id])->one();
                    if (!$project || (int)$project->cms_company_id !== (int)$deal->cms_company_id) {
                        $model->cms_project_id = null;
                    }
                }
                $model->executor_id = $this->resolveExecutorId($model);
                return;
            }
        }

        $selectedCompanyId = $model->cms_company_id ? (int)$model->cms_company_id : null;

        if ($model->cms_project_id) {
            $project = $this->clientProjectsQuery()
                ->andWhere([CmsProject::tableName().'.id' => (int)$model->cms_project_id])
                ->one();

            if (!$project) {
                $model->cms_project_id = null;
            } elseif ($project->cms_company_id) {
                if ($selectedCompanyId && (int)$project->cms_company_id !== $selectedCompanyId) {
                    $model->cms_project_id = null;
                } else {
                    $model->cms_company_id = $project->cms_company_id;
                }
            }
        }

        if ($model->cms_company_id) {
            $company = CmsCompany::find()
                ->forClient()
                ->andWhere([CmsCompany::tableName().'.id' => (int)$model->cms_company_id])
                ->one();

            if (!$company) {
                $model->cms_company_id = null;
            }
        }

        $model->executor_id = $this->resolveExecutorId($model);
    }

    protected function resolveExecutorId(CmsTask $model): ?int
    {
        return (new SupportTaskExecutorResolver())->resolve($model, (int)\Yii::$app->user->id);
    }
}
