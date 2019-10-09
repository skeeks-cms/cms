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

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\queryfilters\filters\modes\FilterModeEq;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\rbac\RbacModule;
use skeeks\cms\widgets\ActiveForm;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\SelectField;
use Yii;
use yii\base\Event;
use yii\db\ActiveQuery;
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
        $this->modelShowAttribute = "displayName";
        $this->modelClassName = CmsUser::class;

        $this->generateAccessActions = false;
        /*$this->permissionName = 'cms/admin-cms-site';*/

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            "index" => [
                'accessCallback' => true,
                "filters" => [
                    "visibleFilters" => [
                        'q',
                        'active',
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

                            'active' => [
                                'field'             => [
                                    'class'      => BoolField::class,
                                    'trueValue'  => "Y",
                                    'falseValue' => "N",
                                ],
                                "isAllowChangeMode" => false,
                                "defaultMode"       => FilterModeEq::ID,
                            ],

                            'role' => [
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
                                        $query->innerJoin('auth_assignment', 'auth_assignment.user_id = cms_user.id');
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
                                                '>=', 'last_activity_at', time() - \Yii::$app->cms->userOnlineTime
                                            ]);
                                        } elseif ($e->field->value == 2) {
                                            $query->andFilterWhere([
                                                '<', 'last_activity_at', time() - \Yii::$app->cms->userOnlineTime
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

                    'on init'        => function (Event $event) {

                        if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROOT_ACCESS)) {
                            //TODO: доработать запрос
                            $query = $event->sender->dataProvider->query;
                            $query->innerJoin('auth_assignment', 'auth_assignment.user_id = cms_user.id');
                            $query->andFilterWhere([
                                "!=", 'auth_assignment.item_name', CmsManager::ROLE_ROOT,
                            ]);
                            $query->groupBy([CmsUser::tableName() . ".id"]);
                        }

                    },

                    'defaultOrder'   => [
                        'logged_at'  => SORT_DESC,
                        'created_at' => SORT_DESC,
                    ],
                    'dialogCallbackData' => function($model) {
                        return \yii\helpers\ArrayHelper::merge($model->toArray(), [
                            'image' => $model->image ? $model->image->src : "",
                            'displayName' => $model->displayName
                        ]);
                    },
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'id',
                        //'image_id',
                        //'displayName',
                        'created_at',
                        'logged_at',
                        //'role',
                        'active',
                    ],
                    'columns'        => [
                        'id' => [
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
                                if ($cmsUser->phone) {
                                    $data[] = $cmsUser->phone;
                                }

                                $rolesData = [];
                                if ($roles = \Yii::$app->authManager->getRolesByUser($cmsUser->id)) {
                                    foreach ($roles as $role) {
                                        $rolesData[] = Html::tag('label', $role->description, [
                                            'title' => $role->name,
                                            'class' => "u-label u-label-default g-rounded-20 g-mr-5 " . ($role->name == 'root' ? 'u-label-danger' : ''),
                                            'style' => "font-size: 11px;",
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
                                                    <img src='". ($cmsUser->image ? $cmsUser->avatarSrc : Image::getCapSrc()) ."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                                </div>
                                                <div style='margin-left: 5px;'>" . $info . "</div></div>";

                                            ;
                            },
                        ],
                        'created_at'  => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'logged_at'   => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'last_activity_at'   => [
                            'class' => DateTimeColumnData::class,
                        ],
                        'image_id'    => [
                            'class' => ImageColumn2::class,
                        ],
                        'active'      => [
                            'class' => BooleanColumn::class,
                        ],
                        'role'        => [
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
                "callback" => [$this, 'create'],
                "accessCallback" => function() {
                    return \Yii::$app->user->can("cms/admin-user/create");
                },
            ],

            'update' => [
                "callback" => [$this, 'update'],
                "accessCallback" => function() {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }

                    return \Yii::$app->user->can("cms/admin-user/update", ["model" => $this->model]);
                },
            ],

            'delete' => [
                "accessCallback" => function() {
                    if (!$this->_checkIsRoot($this->model)) {
                        return false;
                    }
                    return \Yii::$app->user->can("cms/admin-user/delete", ["model" => $this->model]);
                },

            ],


            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
                "eachAccessCallback" => function($model) {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced", ['model' => $model]);
                },
                "accessCallback" => function() {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced");
                },
            ],

            "deactivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
                "eachAccessCallback" => function($model) {
                    return \Yii::$app->user->can("cms/admin-user/update-advanced", ['model' => $model]);
                },
                "accessCallback" => function() {

                    return \Yii::$app->user->can("cms/admin-user/update-advanced");
                },
            ],

            "delete-multi" => [
                "eachAccessCallback" => function($model) {
                    return \Yii::$app->user->can("cms/admin-user/delete", ['model' => $model]);
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
    protected function _checkIsRoot($model) {
        if (!$model) {
            return false;
        }
        if (!$model->roles) {
            return false;
        }
        if (in_array(CmsManager::ROLE_ROOT, array_keys($model->roles))) {
            if (!\Yii::$app->user->can(CmsManager::PERMISSION_ROOT_ACCESS)) {
                return false;
            }
        }

        return true;
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


    public function update($adminAction)
    {
        /**
         * @var $model CmsUser
         */
        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;
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
            $passwordChange->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save()) {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Saved'));

                if ($passwordChange->new_password) {
                    if (!$passwordChange->changePassword()) {
                        \Yii::$app->getSession()->setFlash('error', "Пароль не изменен");
                    }
                }

                if (\Yii::$app->request->post('submit-btn') == 'apply') {

                } else {
                    return $this->redirect(
                        $this->url
                    );
                }

                $model->refresh();

            }
        }

        return $this->render('_form', [
            'model'          => $model,
            'relatedModel'   => $relatedModel,
            'passwordChange' => $passwordChange,
        ]);
    }


    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionChangePassword()
    {
        $model = $this->model;

        $modelForm = new PasswordChangeForm([
            'user' => $model,
        ]);

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            return $rr->ajaxValidateForm($modelForm);
        }


        if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword()) {
            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
        } else {
            if (\Yii::$app->request->isPost) {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось изменить пароль');
            }
        }

        return $this->render($this->action->id, [
            'model' => $modelForm,
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
     * @param  integer $id
     * @param  string  $action
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
     * @param  integer $id
     * @param  string  $target
     * @param  string  $term
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
}
