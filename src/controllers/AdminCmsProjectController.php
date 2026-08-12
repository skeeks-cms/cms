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
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\ContextMenuControllerActionsWidget;
use skeeks\cms\backend\widgets\ControllerActionsWidget;
use skeeks\cms\grid\BooleanColumn;
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
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\queries\CmsCompanyQuery;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\admin\CmsProjectViewWidget;
use skeeks\cms\widgets\admin\CmsWorkerViewWidget;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\yii2\dadataClient\models\PartyModel;
use skeeks\yii2\form\fields\BoolField;
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
use yii\helpers\Json;
use yii\web\ForbiddenHttpException;
use yii\web\JsExpression;
use YooKassa\Model\SafeDeal;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsProjectController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Проекты");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsProject::class;

        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;

        $this->modelHeader = function () {
            $model = $this->model;
            return $this->renderPartial("@skeeks/cms/views/admin-cms-project/_model_header", [
                'model' => $model,
            ]);
        };

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
                        'company_binding',
                    ],
                    "filtersModel" => [
                        'rules'            => [
                            ['q', 'safe'],
                            ['company_binding', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'company_binding',
                        ],

                        'fields' => [
                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск...',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query CmsCompanyQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->search($e->field->value);
                                    }
                                },
                            ],
                            'company_binding' => [
                                'class' => SelectField::class,
                                'label' => 'Связь с компанией',
                                'items' => [
                                    'with_company'    => 'С компанией',
                                    'without_company' => 'Без компании',
                                ],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /** @var ActiveQuery $query */
                                    $query = $e->dataProvider->query;
                                    $attribute = CmsProject::tableName().'.cms_company_id';

                                    if ($e->field->value === 'with_company') {
                                        $query->andWhere([
                                            'and',
                                            ['is not', $attribute, null],
                                            ['<>', $attribute, 0],
                                        ]);
                                    } elseif ($e->field->value === 'without_company') {
                                        $query->andWhere([
                                            'or',
                                            [$attribute => null],
                                            [$attribute => 0],
                                        ]);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                'grid'    => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query CmsCompanyQuery
                         */
                        $query = $e->sender->dataProvider->query;
                        $query
                            ->forManager()
                            ->with(['cmsCompany']);
                    },

                    'defaultOrder' => [
                        'id' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'company',
                        'managers',
                        'users',
                        //'code',
                        'is_active',
                    ],

                    'columns' => [
                        'custom'   => [
                            'format'    => 'raw',
                            'attribute' => 'name',
                            'value'     => function (CmsProject $model) {
                                return CmsProjectViewWidget::widget(['project' => $model]);
                            },
                        ],
                        'company' => [
                            'format' => 'raw',
                            'label'  => 'Компания',
                            'value'  => function (CmsProject $model) {
                                if (!$model->cmsCompany) {
                                    return Html::tag(
                                        'span',
                                        Html::tag('i', '', [
                                            'class' => 'fas fa-unlink',
                                            'aria-hidden' => 'true',
                                        ]).' Без компании',
                                        [
                                            'class' => 'badge badge-warning',
                                            'title' => 'Проект не связан с компанией',
                                            'style' => 'font-size: 12px; font-weight: 500; padding: 6px 9px;',
                                        ]
                                    );
                                }

                                $companyUrl = ['/cms/admin-cms-company/view', 'pk' => $model->cmsCompany->id];
                                $actionData = Json::encode([
                                    'isOpenNewWindow' => true,
                                    'url' => (string) BackendUrlHelper::createByParams($companyUrl)
                                        ->enableEmptyLayout()
                                        ->enableNoActions()
                                        ->url,
                                ]);

                                $companyLogoUrl = $model->cmsCompany->cmsImage
                                    ? \Yii::$app->imaging->thumbnailUrlOnRequest(
                                        $model->cmsCompany->cmsImage->src,
                                        new \skeeks\cms\components\imaging\filters\Thumbnail([
                                            'h' => 50,
                                            'w' => 50,
                                            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_INSET,
                                        ])
                                    )
                                    : Image::getCapSrc();

                                $companyLogo = Html::tag(
                                    'span',
                                    Html::img($companyLogoUrl, [
                                        'alt' => '',
                                        'style' => 'max-width: 40px; max-height: 40px; border-radius: 50%; width: 100%; height: 100%; margin: auto;',
                                    ]),
                                    [
                                        'style' => 'display: flex; flex: 0 0 40px; overflow: hidden; width: 40px; height: 40px; border: 2px solid #ededed; border-radius: 50%;',
                                    ]
                                );

                                return Html::a(
                                    $companyLogo.Html::tag(
                                        'span',
                                        Html::encode($model->cmsCompany->asText)
                                    ),
                                    $companyUrl,
                                    [
                                        'data-pjax' => 0,
                                        'title' => 'Открыть компанию',
                                        'style' => 'display: inline-flex; align-items: center; gap: 8px; border-bottom: 0; font-weight: 500;',
                                        'onclick' => new JsExpression(
                                            "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;"
                                        ),
                                    ]
                                );
                            },
                        ],
                        'managers' => [
                            'format' => 'raw',
                            'label'  => 'Сотрудники',
                            'value'  => function (CmsProject $model) {
                                $data = [];
                                if ($model->managers) {
                                    foreach ($model->managers as $manager) {
                                        $data[] = CmsWorkerViewWidget::widget([
                                            'user'    => $manager,
                                            'isSmall' => true,
                                        ]);
                                    }
                                }

                                return implode('', $data);
                            },
                        ],
                        'users'    => [
                            'format' => 'raw',
                            'label'  => 'Клиенты',
                            'value'  => function (CmsProject $model) {

                                $data = [];
                                /*if ($model->managers) {
                                    foreach ($model->managers as $manager)
                                    {
                                        $data[] = CmsWorkerViewWidget::widget(['user' => $manager]);
                                    }
                                }*/


                                $info = implode(", ", ArrayHelper::map($model->users, "id", "shortDisplayName"));

                                return $info;
                            },
                        ],

                        'is_active'         => [
                            'class'      => BooleanColumn::class,
                            'trueValue'  => 1,
                            'falseValue' => 0,
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
                /*'permissionName' => 'cms/admin-user/update',*/
                /*"accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return true;
                },*/
            ],


            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],


            'tasks' => [
                'class'    => BackendGridModelRelatedAction::class,
                'name'     => 'Задачи',
                //'priority' => 90,
                /*'callback' => [$this, 'shift'],*/
                'icon'     => 'fas fa-list',

                'controllerRoute' => "/cms/admin-cms-task",
                'relation'        => ['cms_project_id' => 'id'],
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

            "log" => [
                'class'    => BackendModelLogAction::class,
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
         * @var $model CmsProject
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());

        $companyInputId = Html::getInputId($model, 'cms_company_id');
        $clientInputId = Html::getInputId($model, 'users');

        // Preserve the legacy single client when the project is first edited via the relation field.
        if (!$model->isNewRecord && !\Yii::$app->request->isPost && $model->cms_user_id) {
            $clientIds = array_map('intval', ArrayHelper::getColumn($model->users, 'id'));
            if (!in_array((int) $model->cms_user_id, $clientIds, true)) {
                $clientIds[] = (int) $model->cms_user_id;
                $model->users = $clientIds;
            }
        }

        $postedModel = \Yii::$app->request->post($model->formName());
        if (is_array($postedModel) && array_key_exists('users', $postedModel)) {
            // New edits are stored only in the many-to-many relation.
            $model->cms_user_id = null;
        }

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

            'is_active' => [
                'class' => BoolField::class,
            ],

            'is_private' => [
                'class' => BoolField::class,
            ],

            'is_work_time_visible_for_clients' => [
                'class' => BoolField::class,
            ],


        ];


        $result = [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основное',
                'fields' => $mainFieldSet,
            ],
            
            'client' => [
                'class'  => FieldSet::class,
                'name'   => 'Компания или клиент (заполнить хотя бы одно)',
                'fields' => [


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
                            'pluginEvents' => [
                                'select2:select' => new JsExpression("function() { $('#{$clientInputId}').val(null).trigger('change'); }"),
                                'select2:clear'  => new JsExpression("function() { $('#{$clientInputId}').val(null).trigger('change'); }"),
                            ],
                        ],
                    ],
                    'users' => [
                        'class'        => WidgetField::class,
                        'widgetClass'  => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'modelClass'  => CmsUser::class,
                            'multiple'    => true,
                            'searchQuery' => function ($word = '') use ($model) {
                                $query = CmsUser::find()->forManager();
                                $cmsCompanyId = (int) \Yii::$app->request->get('cms_company_id', $model->cms_company_id);
                                if ($cmsCompanyId) {
                                    $query->andWhere([
                                        CmsUser::tableName().'.id' => CmsCompany2user::find()
                                            ->select('cms_user_id')
                                            ->andWhere(['cms_company_id' => $cmsCompanyId]),
                                    ]);
                                }
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                            'pluginOptions' => [
                                'ajax' => [
                                    'data' => new JsExpression("function(params) { return {q: params.term, cms_company_id: $('#{$companyInputId}').val() || ''}; }"),
                                ],
                            ],
                        ],
                    ],


                ],
            ],
        ];


        return $result;
    }

}
