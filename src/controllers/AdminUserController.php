<?php
/**
 * AdminUserController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use common\models\User;
use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\actions\BackendGridModelRelatedAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\base\DynamicModel;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsContractorMap;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUser;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\shop\models\ShopOrder;
use skeeks\cms\widgets\ActiveForm;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\dadataClient\models\PartyModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\WidgetField;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\rbac\Item;
use yii\web\Response;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminUserController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление пользователями";
        $this->modelShowAttribute = "shortDisplayNameWithAlias";
        $this->modelClassName = CmsUser::class;

        $this->generateAccessActions = false;
        /*$this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            return true;
        };*/
        /*$this->permissionName = 'cms/admin-user';*/

        $this->modelHeader = function () {
            /**
             * @var $model CmsContentElement
             */
            $model = $this->model;
            return $this->renderPartial("@skeeks/cms/views/admin-user/_model_header", [
                'model' => $model,
            ]);
        };

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            "index" => [
                "filters" => [
                    "visibleFilters" => [
                        'q',
                        'is_active',
                        'role',
                        'isOnline',
                    ],

                    "filtersModel" => [
                        'rules'            => [
                            ['q', 'safe'],
                            ['isOnline', 'safe'],
                            ['role', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'role',
                            'isOnline',
                        ],

                        'fields' => [

                            'is_active' => [
                                'field'             => [
                                    'class' => BoolField::class,
                                ],
                                "isAllowChangeMode" => false,
                                "defaultMode"       => FilterModeEq::ID,
                            ],

                            'role'     => [
                                'class'    => SelectField::class,
                                'multiple' => true,
                                'label'    => \Yii::t('skeeks/cms', 'Roles'),
                                'items'    => \yii\helpers\ArrayHelper::map(\Yii::$app->authManager->getRoles(), 'name', 'description'),
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;
                                    if ($e->field->value) {
                                        $query->innerJoin(['auth_assignment_role' => 'auth_assignment'], 'auth_assignment_role.cms_user_id = cms_user.id');
                                        $query->andFilterWhere([
                                            'auth_assignment_role.item_name' => $e->field->value,
                                        ]);
                                    }

                                },
                            ],
                            'isOnline' => [
                                'class'    => SelectField::class,
                                'multiple' => false,
                                'label'    => 'Онлайн/Оффлайн',
                                'items'    => [
                                    1 => \Yii::t('skeeks/cms', 'Online'),
                                    2 => \Yii::t('skeeks/cms', 'Offline'),
                                ],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;
                                    if ($e->field->value) {

                                        if ($e->field->value == 1) {
                                            $query->andFilterWhere([
                                                '>=',
                                                'last_activity_at',
                                                time() - \Yii::$app->cms->userOnlineTime,
                                            ]);
                                        } elseif ($e->field->value == 2) {
                                            $query->andFilterWhere([
                                                '<',
                                                'last_activity_at',
                                                time() - \Yii::$app->cms->userOnlineTime,
                                            ]);
                                        }


                                    }

                                },
                            ],

                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск (ФИО, Email, Телефон)',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query ActiveQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    $query->joinWith("cmsUserEmails as cmsUserEmails");
                                    $query->joinWith("cmsUserPhones as cmsUserPhones");

                                    if ($e->field->value) {
                                        $query->andWhere([
                                            'or',
                                            ['like', CmsUser::tableName().'.first_name', $e->field->value],
                                            ['like', CmsUser::tableName().'.last_name', $e->field->value],
                                            ['like', CmsUser::tableName().'.patronymic', $e->field->value],

                                            ['like', 'cmsUserEmails.value', $e->field->value],
                                            ['like', 'cmsUserPhones.value', $e->field->value],
                                        ]);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                'grid' => [

                    'on init' => function (Event $event) {

                        $query = $event->sender->dataProvider->query;

                        if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROOT_ACCESS)) {

                            $query->innerJoin('auth_assignment', 'auth_assignment.cms_user_id = cms_user.id');
                            $query->andFilterWhere([
                                "!=",
                                'auth_assignment.item_name',
                                CmsManager::ROLE_ROOT,
                            ]);
                            $query->groupBy([CmsUser::tableName().".id"]);
                        }

                        $query->cmsSite();
                    },

                    'defaultOrder'       => [
                        'id' => SORT_DESC,
                        //'created_at' => SORT_DESC,
                    ],
                    'dialogCallbackData' => function ($model) {
                        return \yii\helpers\ArrayHelper::merge($model->toArray(), [
                            'image'       => $model->image ? $model->image->src : "",
                            'displayName' => $model->displayName,
                        ]);
                    },
                    'visibleColumns'     => [
                        'checkbox',
                        'actions',
                        //'id',
                        'custom',
                        'phone',
                        'email',
                        //'image_id',
                        //'displayName',
                        //'created_at',
                        //'logged_at',
                        //'role',
                        'is_active',
                    ],
                    'columns'            => [
                        'custom'                 => [
                            //'label'  => 'Данные пользователя',
                            'format'    => 'raw',
                            'attribute' => 'id',
                            'label'     => 'Аккаунт',
                            'value'     => function (CmsUser $cmsUser) {
                                //$data[] = $cmsUser->asText;
                                $data[] = Html::a($cmsUser->shortDisplayNameWithAlias, "#", [
                                    'style' => 'font-size: 15px;
                                                display: block;',
                                ]);
                                /*if ($cmsUser->phone) {
                                    $data[] = "<div style='color: gray;'>" . $cmsUser->phone . "</div>";
                                }
                                if ($cmsUser->email) {
                                    $data[] = "<div style='color: gray;'>" . $cmsUser->email . "</div>";
                                }*/

                                $rolesData = [];
                                if ($roles = \Yii::$app->authManager->getRolesByUser($cmsUser->id)) {
                                    foreach ($roles as $role) {
                                        $rolesData[] = Html::tag('label', $role->description, [
                                            'title' => $role->name,
                                            'class' => "".($role->name == 'root' ? 'u-label-danger' : ''),
                                            'style' => "font-size: 10px;
    padding: 2px;
    padding-bottom: 4px;
    padding-left: 4px;
    padding-right: 4px;
    background: silver;
    color: white;
        margin-bottom: 0;
        margin-right: 5px;
        border-radius: 20px;
            line-height: 1;
    text-align: center;
    white-space: nowrap;
    margin-top: 5px;
    margin-bottom: 0;
    color: #fff;",
                                        ]);
                                    }
                                }

                                if ($rolesData) {
                                    $data[] = implode("", $rolesData);
                                }


                                $info = implode("", $data);

                                return "<div class='row no-gutters sx-trigger-action' style='cursor: pointer;'>
                                                <div class='sx-trigger-action my-auto' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none;
    border-bottom: 0;
    width: 54px;
    border-radius: 50%;
    border: 2px solid #ededed;
    height: 54px;
    
    display: flex;
    overflow: hidden;'>
                                                    <img src='".($cmsUser->image ? $cmsUser->avatarSrc : Image::getCapSrc())."' style='    max-width: 50px;
    max-height: 50px;
    border-radius: 50%;
    margin: auto;' />
                                                </a>
                                                </div>
                                                <div style='margin-left: 10px; line-height: 1.1;' class='my-auto'>".$info."</div></div>";;
                            },
                        ],
                        'created_at'             => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'logged_at'              => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'last_activity_at'       => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'last_admin_activity_at' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'image_id'               => [
                            'class' => ImageColumn2::class,
                        ],
                        'is_active'              => [
                            'class' => BooleanColumn::class,
                        ],
                        'role'                   => [
                            'value'  => function ($cmsUser) {
                                $result = [];

                                if ($roles = \Yii::$app->authManager->getRolesByUser($cmsUser->id)) {
                                    foreach ($roles as $role) {
                                        $result[] = $role->description." ({$role->name})";
                                    }
                                }

                                return implode(', ', $result);
                            },
                            'format' => 'html',
                            'label'  => \Yii::t('skeeks/cms', 'Roles'),
                        ],
                        'phone'                  => [
                            'label'         => "Телефон",
                            'headerOptions' => [
                                'style' => 'width: 120px;',
                            ],
                            'value'         => function ($cmsUser) {
                                return $cmsUser->phone ? $cmsUser->phone : "";
                            },
                        ],
                        'email'                  => [
                            'label'         => "Email",
                            'headerOptions' => [
                                'style' => 'width: 100px;',
                            ],
                            'value'         => function ($cmsUser) {
                                return $cmsUser->email ? $cmsUser->email : "";
                            },
                        ],

                        'countOrders' => [
                            'attribute'     => "countOrders",
                            'label'         => "Количество заказов",
                            'headerOptions' => [
                                'style' => 'width: 100px;',
                            ],

                            'beforeCreateCallback' => function (GridView $grid) {
                                /**
                                 * @var $query ActiveQuery
                                 */
                                $query = $grid->dataProvider->query;

                                $subQuery = ShopOrder::find()->select([new Expression("count(1)")])->where([
                                    'cms_user_id' => new Expression(CmsUser::tableName().".id"),
                                ]);

                                $query->addSelect([
                                    'countOrders' => $subQuery,
                                ]);


                                $grid->sortAttributes["countOrders"] = [
                                    'asc'  => ['countOrders' => SORT_ASC],
                                    'desc' => ['countOrders' => SORT_DESC],
                                ];
                            },

                            'value' => function ($model) {
                                return $model->raw_row['countOrders'];
                            },
                        ],
                    ],
                ],
            ],

            'add-contractor' => [
                'class'     => BackendModelAction::class,
                'isVisible' => false,
                'callback'  => [$this, 'addContractor'],
            ],
            'send-sms'       => [
                'class'     => BackendModelAction::class,
                'isVisible' => false,
                'callback'  => [$this, 'sendSms'],
            ],

            'create' => [
                //"callback"       => [$this, 'create'],
                'size'           => BackendAction::SIZE_SMALL,
                'generateAccess' => true,
                'fields'         => [$this, 'createFields'],
                'buttons'        => ["save"],
            ],


            'view' => [
                'class'          => BackendModelAction::class,
                'name'           => 'Профиль',
                'icon'           => 'fa fa-user',
                "callback"       => [$this, 'view'],
                'permissionName' => 'cms/admin-user/update',
                "accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return true;
                },
            ],

            'stat' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Статистика',
                'icon'     => 'far fa-chart-bar',
                'priority' => 500,

                'permissionName' => 'cms/admin-user/update-advanced',

                "accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    if ($this->model) {
                        $elementExists = CmsContentElement::find()->andWhere(['created_by' => $this->model->id])->exists();
                        $treeExists = CmsTree::find()->andWhere(['created_by' => $this->model->id])->exists();

                        if (!$treeExists && !$elementExists) {
                            return false;
                        }
                    }


                    return true;
                },
            ],


            "orders" => [
                'class'           => BackendGridModelRelatedAction::class,
                'accessCallback'  => true,
                'name'            => "Заказы",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/shop/admin-order",
                'relation'        => ['cms_user_id' => 'id'],
                'priority'        => 600,
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    /*ArrayHelper::removeValue($visibleColumns, 'cms_site_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;*/

                },

                "accessCallback" => function () {
                    if (!isset(\Yii::$app->shop)) {
                        return false;
                    }

                    if ($this->model) {
                        return ShopOrder::find()->cmsSite()->andWhere(['cms_user_id' => $this->model->id])->exists();
                    }

                    return true;
                },
            ],


            'payments' => [
                'class'    => BackendGridModelRelatedAction::class,
                'name'     => 'Платежи',
                'priority' => 600,
                //'callback' => [$this, 'payments'],
                'icon'     => 'fas fa-credit-card',


                'controllerRoute' => "/shop/admin-payment",
                'relation'        => ['cms_user_id' => 'id'],
                'priority'        => 400,
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $action->relatedIndexAction->filters = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_user_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },


            ],

            "checks" => [
                'class'          => BackendGridModelRelatedAction::class,
                'accessCallback' => true,
                'name'           => "Чеки",
                'icon'           => 'fa fa-list',

                'controllerRoute' => "/shop/admin-shop-check",
                'relation'        => ['cms_user_id' => 'id'],
                'priority'        => 600,
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $action->relatedIndexAction->filters = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_user_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },
            ],

            'bills' => [
                'class'    => BackendGridModelRelatedAction::class,
                'name'     => 'Счета',
                'priority' => 600,
                //'callback' => [$this, 'bills'],
                'icon'     => 'fas fa-credit-card',

                'controllerRoute' => "/shop/admin-bill",
                'relation'        => ['cms_user_id' => 'id'],
                'priority'        => 600,
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->backendShowings = false;
                    $action->relatedIndexAction->filters = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_user_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },

            ],


            "bonus" => [
                'class'           => BackendGridModelRelatedAction::class,
                'accessCallback'  => true,
                'name'            => "Бонусы",
                'icon'            => 'fa fa-list',
                'controllerRoute' => "/shop/admin-bonus-transaction",
                'relation'        => ['cms_user_id' => 'id'],
                'priority'        => 700,
                'on gridInit'     => function ($e) {
                    /**
                     * @var $action BackendGridModelRelatedAction
                     */
                    $action = $e->sender;
                    $action->relatedIndexAction->filters = false;
                    $action->relatedIndexAction->backendShowings = false;
                    $visibleColumns = $action->relatedIndexAction->grid['visibleColumns'];

                    ArrayHelper::removeValue($visibleColumns, 'cms_user_id');
                    $action->relatedIndexAction->grid['visibleColumns'] = $visibleColumns;

                },

                /*"accessCallback" => function () {
                    if ($this->model) {
                        return ShopBonusTransaction::find()->cmsSite()->andWhere(['cms_user_id' => $this->model->id])->exists();
                    }

                    return true;
                },*/
            ],

            'add-site-permission' => [
                'class'     => BackendModelAction::class,
                'isVisible' => false,
                'name'      => 'Профиль',
                "callback"  => [$this, 'addSite'],

                'permissionName' => 'cms/admin-user/update-advanced',

                "accessCallback" => function () {

                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return true;
                },
            ],

            'save-site-permissions' => [
                'class'          => BackendModelAction::class,
                'isVisible'      => false,
                'name'           => 'Профиль',
                "callback"       => [$this, 'saveSitePermissions'],
                'permissionName' => 'cms/admin-user/update-advanced',
                "accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return true;
                },
            ],

            'update' => [
                'fields'         => [$this, 'updateFields'],
                'buttons'        => ["save"],
                'isVisible'      => false,
                'generateAccess' => true,
                "accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return true;
                },
            ],

            'update-eav' => [
                'class'     => BackendModelUpdateAction::class,
                "callback"  => [$this, 'updateEav'],
                'isVisible' => false,
            ],

            'delete' => [
                'generateAccess' => true,
                "accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return true;
                },

            ],


            "activate-multi" => [
                'class'              => BackendModelMultiActivateAction::class,
                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced");
                },
            ],

            "deactivate-multi" => [
                'class'              => BackendModelMultiDeactivateAction::class,
                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced");
                },
            ],

            "delete-multi" => [
                "eachAccessCallback" => function ($model) {
                    return \Yii::$app->user->can("cms/admin-user/delete", ['model' => $model]);
                },
                "accessCallback"     => function () {
                    return \Yii::$app->user->can("cms/admin-user/delete");
                },
            ],
        ]);


        return $actions;
    }


    /**
     * Проверка можно ли редактировать рута
     *
     * @param $model
     * @return bool
     */
    protected function _checkIsRoot($model)
    {
        if (!$model) {
            return false;
        }
        if (!$model->roles) {
            return true;
        }

        if (in_array(CmsManager::ROLE_ROOT, array_keys($model->roles))) {
            if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROOT_ACCESS)) {
                return false;
            }
        }

        return true;
    }

    public function view()
    {
        return $this->render($this->action->id);
    }


    public function createFields()
    {


        \Yii::$app->view->registerJs(<<<JS
function updateFields() {
    $(".sx-0-block").hide();
    $(".sx-1-block").hide();
    
    var contType = $("#cmsuser-is_company").val();
    if (contType == '1') {
        $(".sx-1-block").show();
    }
    if (contType == '0') {
        $(".sx-0-block").show();
    }
}

$("#cmsuser-is_company").on("change", function() {
    updateFields();
});

updateFields();
JS
        );

        return [
            'is_company' => [
                'class'     => SelectField::class,
                'allowNull' => false,
                'items'     => [
                    '0' => "Человек",
                    '1' => "Компания",
                ],
            ],

            'image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],

            [
                'class'   => HtmlBlock::class,
                'content' => '<div class="sx-1-block">',
            ],

            'company_name',
            'alias',

            [
                'class'   => HtmlBlock::class,
                'content' => '</div><div class="sx-0-block">',
            ],

            'gender' => [
                'class'     => SelectField::class,
                'allowNull' => false,
                'items'     => [
                    'men'   => \Yii::t('skeeks/cms', 'Male'),
                    'women' => \Yii::t('skeeks/cms', 'Female'),
                ],
            ],
            'last_name',
            'first_name',
            'patronymic',
            'alias',

            [
                'class'   => HtmlBlock::class,
                'content' => '</div>',
            ],


            'email',
            'phone'  => [
                'elementOptions'  => [
                    'placeholder' => '+7 903 722-28-73',
                ],
                'on beforeRender' => function (Event $e) {
                    /**
                     * @var $field Field
                     */
                    $field = $e->sender;
                    \skeeks\cms\admin\assets\JqueryMaskInputAsset::register(\Yii::$app->view);
                    $id = \yii\helpers\Html::getInputId($field->model, $field->attribute);
                    \Yii::$app->view->registerJs(<<<JS
                        $("#{$id}").mask("+7 999 999-99-99");
JS
                    );
                },
            ],
        ];
    }


    public function updateFields()
    {

        if ($this->model->is_company) {
            $result = [
                'is_active' => [
                    'class'     => BoolField::class,
                    'allowNull' => false,
                ],
                'image_id'  => [
                    'class'        => WidgetField::class,
                    'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                    'widgetConfig' => [
                        'accept'   => 'image/*',
                        'multiple' => false,
                    ],
                ],

                'company_name',
                'alias',
            ];
        } else {
            $result = [
                'is_active' => [
                    'class'     => BoolField::class,
                    'allowNull' => false,
                ],
                'image_id'  => [
                    'class'        => WidgetField::class,
                    'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                    'widgetConfig' => [
                        'accept'   => 'image/*',
                        'multiple' => false,
                    ],
                ],
                /*'username',*/
                'gender'    => [
                    'class'     => SelectField::class,
                    'allowNull' => false,
                    'items'     => [
                        'men'   => \Yii::t('skeeks/cms', 'Male'),
                        'women' => \Yii::t('skeeks/cms', 'Female'),
                    ],
                ],
                'last_name',
                'first_name',
                'patronymic',

                'alias',
            ];
        }


        if ((\Yii::$app->user->can("cms/admin-user/update-advanced", ['model' => $this->model]))
            || \Yii::$app->user->can(\skeeks\cms\rbac\CmsManager::PERMISSION_ROOT_ACCESS)) {

            $roles = \Yii::$app->authManager->getAvailableRoles();
            \yii\helpers\ArrayHelper::remove($roles, \skeeks\cms\rbac\CmsManager::ROLE_GUEST);

            $result['roleNames'] = [
                'class'     => SelectField::class,
                'allowNull' => false,
                'multiple'  => true,
                'items'     => \yii\helpers\ArrayHelper::map($roles, 'name', 'description'),
            ];

        }

        return $result;

    }


    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionChangePassword()
    {
        /**
         * @var $model CmsUser
         */
        $model = $this->model;

        $dm = new DynamicModel(['password']);
        //$dm->addRule(['password'], 'string', ['min' => 8]);
        $dm->addRule(['password'], 'required');
        $dm->addRule(['password'], function ($attribute) use ($dm) {

            $password = $dm->{$attribute};
            $number = preg_match('@[0-9]@', $password);
            $uppercase = preg_match('@[A-Z]@', $password);
            $lowercase = preg_match('@[a-z]@', $password);
            //$specialChars = preg_match('@[^\w]@', $password);

            /*if (!$number) {
                $dm->addError($attribute, "Пароль должен содержать как минимум одну цифру");
                return false;
            }
            if (!$uppercase) {
                $dm->addError($attribute, "Пароль должен хоть одну заглавную английскую букву");
                return false;
            }
            if (!$lowercase) {
                $dm->addError($attribute, "Пароль должен хоть одну строчную английскую букву");
                return false;
            }*/
            /*if (!$specialChars) {
                $dm->addError($attribute, "Пароль должен содержать хоть один специальный символ");
                return false;
            }*/
        });
        $dm->setAttributeLebel('password', 'Новый пароль');

        $is_saved = false;
        try {
            if (\Yii::$app->request->post()) {
                if ($dm->load(\Yii::$app->request->post()) && $dm->validate()) {
                    $model->setPassword($dm->password);
                    if (!$model->save(false)) {
                        throw new Exception("Пароль не изменен");
                    }

                    $is_saved = true;

                } else {
                    throw new Exception("Пароль не изменен");
                }
            }
        } catch (\Exception $exception) {
            $dm->addError('', $exception->getMessage());
        }


        return $this->render($this->action->id, [
            'dm'       => $dm,
            'is_saved' => $is_saved,
        ]);
    }

    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function updateEav()
    {
        /**
         * @var $model CmsUser
         */
        $model = $this->model;

        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;

        $is_saved = false;
        $redirect = null;

        $rr = new RequestResponse();

        try {


            if ($post = \Yii::$app->request->post()) {
                $model->load(\Yii::$app->request->post());
                $relatedModel->load(\Yii::$app->request->post());

            }

            if ($rr->isRequestAjaxPost()) {

                if (!\Yii::$app->request->post(RequestResponse::DYNAMIC_RELOAD_NOT_SUBMIT)) {

                    $model->load(\Yii::$app->request->post());
                    $relatedModel->load(\Yii::$app->request->post());

                    $model->save();
                    if ($relatedModel->save()) {

                        $is_saved = true;

                        if (\Yii::$app->request->post('submit-btn') == 'save') {
                        } else {
                            $redirect = $this->url;
                        }

                        $rr->message = "Сохранено";
                        $rr->success = true;
                        //$model->refresh();
                        $relatedModel = $model->relatedPropertiesModel;
                    } else {
                        print_r($relatedModel->errors);
                        die;
                    }
                }

                return $rr;

            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            die;
        }


        return $this->render("update-eav", [
            'model'        => $model,
            'relatedModel' => $relatedModel,
            'is_saved'     => $is_saved,
            'submitBtn'    => \Yii::$app->request->post('submit-btn'),
            'redirect'     => $redirect,
        ]);
    }

    /**
     * @return string
     */
    public function actionPermission()
    {
        $model = $this->model;
        $authManager = Yii::$app->authManager;
        $avaliable = [];
        $assigned = [];
        foreach ($authManager->getRolesByUser($model->primaryKey) as $role) {
            $type = $role->type;
            $assigned[$type == Item::TYPE_ROLE ? 'Roles' : 'Permissions'][$role->name] = $role->name;
        }
        foreach ($authManager->getRoles() as $role) {
            if (!isset($assigned['Roles'][$role->name])) {
                $avaliable['Roles'][$role->name] = $role->name;
            }
        }
        foreach ($authManager->getPermissions() as $role) {
            if ($role->name[0] !== '/' && !isset($assigned['Permissions'][$role->name])) {
                $avaliable['Permissions'][$role->name] = $role->name;
            }
        }

        return $this->render('permission', [
            'model'         => $model,
            'avaliable'     => $avaliable,
            'assigned'      => $assigned,
            'idField'       => 'id',
            'usernameField' => 'username',
        ]);
    }


    /**
     * Assign or revoke assignment to user
     * @param integer $id
     * @param string  $action
     * @return mixed
     */
    public function actionAssign($id, $action)
    {
        $post = Yii::$app->request->post();
        $roles = $post['roles'];
        $manager = Yii::$app->authManager;
        $error = [];
        if ($action == 'assign') {
            foreach ($roles as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ?: $manager->getPermission($name);
                    $manager->assign($item, $id);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        } else {
            foreach ($roles as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ?: $manager->getPermission($name);
                    $manager->revoke($item, $id);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [
            $this->actionRoleSearch($id, 'avaliable', $post['search_av']),
            $this->actionRoleSearch($id, 'assigned', $post['search_asgn']),
            $error,
        ];
    }

    /**
     * Search roles of user
     * @param integer $id
     * @param string  $target
     * @param string  $term
     * @return string
     */
    public function actionRoleSearch($id, $target, $term = '')
    {
        $authManager = Yii::$app->authManager;
        $avaliable = [];
        $assigned = [];
        foreach ($authManager->getRolesByUser($id) as $role) {
            $type = $role->type;
            $assigned[$type == Item::TYPE_ROLE ? 'Roles' : 'Permissions'][$role->name] = $role->name;
        }
        foreach ($authManager->getRoles() as $role) {
            if (!isset($assigned['Roles'][$role->name])) {
                $avaliable['Roles'][$role->name] = $role->name;
            }
        }
        foreach ($authManager->getPermissions() as $role) {
            if ($role->name[0] !== '/' && !isset($assigned['Permissions'][$role->name])) {
                $avaliable['Permissions'][$role->name] = $role->name;
            }
        }

        $result = [];
        $var = ${$target};
        if (!empty($term)) {
            foreach (['Roles', 'Permissions'] as $type) {
                if (isset($var[$type])) {
                    foreach ($var[$type] as $role) {
                        if (strpos($role, $term) !== false) {
                            $result[$type][$role] = $role;
                        }
                    }
                }
            }
        } else {
            $result = $var;
        }

        return Html::renderSelectOptions('', $result);
    }


    public function addSite()
    {
        /**
         * @var $model User
         */
        $model = $this->model;

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            $site_id = \Yii::$app->request->post("cms_site_id");
            $cmsSite = CmsSite::find()->where(['id' => $site_id])->one();

            $manager = new CmsManager(['cmsSite' => $cmsSite]);
            foreach ((array)\Yii::$app->cms->registerRoles as $roleCode) {
                if (!$manager->getAssignment($roleCode, $model->id)) {
                    $manager->assign($manager->getRole($roleCode), $model->id);
                }
            }

            $rr->success = true;

        }

        return $rr;
    }


    public function saveSitePermissions()
    {
        /**
         * @var $model User
         */
        $model = $this->model;

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            $site_id = \Yii::$app->request->post("cms_site_id");
            $permissions = (array)\Yii::$app->request->post("permissions");
            $cmsSite = CmsSite::find()->where(['id' => $site_id])->one();

            $manager = new CmsManager(['cmsSite' => $cmsSite]);
            $roles = $manager->getRolesByUser($model->id);

            if ($roles) {
                foreach ($roles as $roleExist) {
                    if (!in_array($roleExist->name, (array)$permissions)) {
                        $manager->revoke($roleExist, $model->id);
                    }
                }
            }

            foreach ((array)$permissions as $roleName) {
                if ($role = $manager->getRole($roleName)) {
                    try {
                        //todo: добавить проверку
                        $manager->assign($role, $model->id);
                    } catch (\Exception $e) {
                        \Yii::error("Ошибка назначения роли: ".$e->getMessage(), self::class);
                        //throw $e;
                    }
                } else {
                    \Yii::warning("Роль {$roleName} не зарегистрированна в системе", self::class);
                }
            }

            $rr->success = true;

        }

        return $rr;
    }


    public function addContractor()
    {
        $rr = new RequestResponse();

        try {
            /**
             * @var $user CmsUser
             */
            $user = $this->model;
            if ($rr->isRequestAjaxPost()) {
                if (!$inn = trim(\Yii::$app->request->post("inn"))) {
                    throw new Exception("Инн не указан");
                }

                $q = CmsContractor::find()->typeIndividualAndLegal()->inn($inn);
                //print_r($q->createCommand()->rawSql);die;
                $contractor = $q->one();
                if ($contractor) {

                    if ($user->getCmsContractorMaps()->andWhere(['cms_contractor_id' => $contractor->id])->exists()) {
                        throw new Exception("Эта компания уже добавлена");
                    }

                    $map = new CmsContractorMap();
                    $map->cms_user_id = $user->id;
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

                        $map = new CmsContractorMap();
                        $map->cms_user_id = $user->id;
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

    public function sendSms()
    {
        $rr = new RequestResponse();

        try {
            /**
             * @var $user CmsUser
             */
            $user = $this->model;
            if ($rr->isRequestAjaxPost()) {
                if (!$phone = trim(\Yii::$app->request->post("phone"))) {
                    throw new Exception("Не указан телефон");
                }
                if (!$message = trim(\Yii::$app->request->post("message"))) {
                    throw new Exception("Не указано сообщение");
                }

                if (!\Yii::$app->cms->smsProvider) {
                    throw new Exception("Не настроена SMS отправка на сайте");
                }

                $cmsSmsMessage = \Yii::$app->cms->smsProvider->send($phone, $message);
                if ($cmsSmsMessage->isError) {
                    throw new Exception($cmsSmsMessage->error_message);
                } else {
                    $rr->message = "Сообщение отправлено!";
                    $rr->success = true;
                }

            }

        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }
}
