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

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\ActiveForm;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\SelectField;
use yii\base\Event;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\UnsetArrayValue;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminSiteUserController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = "Управление пользователями сайта";
        $this->modelShowAttribute = "displayName";
        $this->modelClassName = CmsUser::class;

        $this->generateAccessActions = false;
        $this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
            if (CmsSite::find()->active()->count() == 1) {
                return false;
            }
            
            return \Yii::$app->user->can($this->uniqueId);
        };
        /*$this->permissionName = 'cms/admin-cms-site';*/

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            //"update" => new UnsetArrayValue(),
            "delete" => new UnsetArrayValue(),
            "index"  => [
                'backendShowings' => false,
                'accessCallback'  => true,
                "filters"         => [
                    "visibleFilters" => [
                        'q',
                        'role',
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

                            'active' => [
                                'field'             => [
                                    'class'      => BoolField::class,
                                    'trueValue'  => "Y",
                                    'falseValue' => "N",
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
                                        $query->innerJoin('auth_assignment', 'auth_assignment.cms_user_id = cms_user.id');
                                        $query->andFilterWhere([
                                            'auth_assignment.item_name' => $e->field->value,
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

                                    if ($e->field->value) {
                                        $query->andWhere([
                                            'or',
                                            ['like', CmsUser::tableName().'.first_name', $e->field->value],
                                            ['like', CmsUser::tableName().'.last_name', $e->field->value],
                                            ['like', CmsUser::tableName().'.patronymic', $e->field->value],
                                            ['like', CmsUser::tableName().'.email', $e->field->value],
                                            ['like', CmsUser::tableName().'.phone', $e->field->value],
                                        ]);
                                    }
                                },
                            ],
                        ],
                    ],
                ],

                'grid' => [

                    'on init' => function (Event $event) {

                        if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROOT_ACCESS)) {
                            //TODO: доработать запрос
                            $query = $event->sender->dataProvider->query;
                            $query->innerJoin('auth_assignment', 'auth_assignment.cms_user_id = cms_user.id');
                            $query->andFilterWhere([
                                "!=",
                                'auth_assignment.item_name',
                                CmsManager::ROLE_ROOT,
                            ]);
                            $query->groupBy([CmsUser::tableName().".id"]);
                        }

                    },

                    'defaultOrder'       => [
                        'logged_at'  => SORT_DESC,
                        'created_at' => SORT_DESC,
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
                        'id',
                    ],
                    'columns'            => [
                        'id'               => [
                            //'label'  => 'Данные пользователя',
                            'format' => 'raw',
                            'value'  => function (CmsUser $cmsUser) {
                                //$data[] = $cmsUser->asText;
                                $data[] = Html::a($cmsUser->asText, "#");
                                if ($cmsUser->phone) {
                                    $data[] = $cmsUser->phone;
                                }
                                if ($cmsUser->email) {
                                    $data[] = $cmsUser->email;
                                }

                                $rolesData = [];
                                if ($roles = \Yii::$app->authManager->getRolesByUser($cmsUser->id)) {
                                    foreach ($roles as $role) {
                                        $rolesData[] = Html::tag('label', $role->description, [
                                            'title' => $role->name,
                                            'class' => "u-label u-label-default g-rounded-20 g-mr-5 ".($role->name == 'root' ? 'u-label-danger' : ''),
                                            'style' => "font-size: 9px;",
                                        ]);
                                    }
                                }

                                if ($rolesData) {
                                    $data[] = implode("", $rolesData);
                                }


                                $info = implode("<br />", $data);

                                return "<div class='row no-gutters sx-trigger-action' style='cursor: pointer;'>
                                                <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                    <img src='".($cmsUser->image ? $cmsUser->avatarSrc : Image::getCapSrc())."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                                </div>
                                                <div style='margin-left: 5px;'>".$info."</div></div>";;
                            },
                        ],
                        'created_at'       => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'logged_at'        => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'last_activity_at' => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'image_id'         => [
                            'class' => ImageColumn2::class,
                        ],
                        'active'           => [
                            'class' => BooleanColumn::class,
                        ],
                        'role'             => [
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
                    ],
                ],
            ],

            'create' => [
                "callback"       => [$this, 'create'],
                "name"          => "Добавить",
                "accessCallback" => function () {
                    return \Yii::$app->user->can("cms/admin-user/create");
                },
            ],

            'view' => [
                'class'          => BackendModelAction::class,
                'name'           => 'Профиль',
                'icon'           => 'fa fa-user',
                "callback"       => [$this, 'view'],
                "accessCallback" => function () {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return \Yii::$app->user->can("cms/admin-user/update", ["model" => $this->model]);
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

    public function create($adminAction)
    {
        $modelClassName = $this->modelClassName;
        $model = new $modelClassName();
        $model->loadDefaultValues();

        $relatedModel = $model->relatedPropertiesModel;
        $relatedModel->loadDefaultValues();

        $passwordChange = new PasswordChangeForm([
            'user' => $model,
        ]);

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            $passwordChange->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
                $passwordChange,
            ]);
        }


        if ($rr->isRequestPjaxPost()) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save()) {
                if ($passwordChange->new_password) {
                    if (!$passwordChange->changePassword()) {
                        \Yii::$app->getSession()->setFlash('error', "Пароль не изменен");
                    }
                }

                \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                if (\Yii::$app->request->post('submit-btn') == 'apply') {
                    return $this->redirect(
                        UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute($this->modelDefaultAction)->normalizeCurrentRoute()
                            ->addData([$this->requestPkParamName => $model->{$this->modelPkAttribute}])
                            ->toString()
                    );
                } else {
                    return $this->redirect(
                        $this->url
                    );
                }
            }
        }

        return $this->render('_form', [
            'model'          => $model,
            'relatedModel'   => $relatedModel,
            'passwordChange' => $passwordChange,
        ]);
    }
}
