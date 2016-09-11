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

use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\searchs\CmsUserSearch;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorCreateAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminMultiModelEditAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\modules\admin\traits\AdminModelEditorStandartControllerTrait;
use skeeks\cms\widgets\ActiveForm;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\base\ActionEvent;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\rbac\Item;
use yii\web\Response;

/**
 * Class AdminUserController
 * @package skeeks\cms\controllers
 */
class AdminUserController extends AdminModelEditorController
{
    use AdminModelEditorStandartControllerTrait;

    public function init()
    {
        $this->name                     = "Управление пользователями";
        $this->modelShowAttribute       = "username";
        $this->modelClassName           = User::className();

        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [

            "index" =>
            [
                'modelSearchClassName' => CmsUserSearch::className()
            ],

            'create' =>
            [
                'class'         => AdminModelEditorCreateAction::className(),
                "callback"      => [$this, 'create'],
            ],

            'update' =>
            [
                'class'         => AdminOneModelEditAction::className(),
                "callback"      => [$this, 'update'],
            ],

            /*'change-password' =>
            [
                "class"     => AdminOneModelEditAction::className(),
                "name"      => "Изменение пароля",
                "icon"      => "glyphicon glyphicon-cog",
                "callback"  => [$this, "actionChangePassword"],
            ],*/

            /*'permission' =>
            [
                "class"     => AdminOneModelEditAction::className(),
                "name"      => "Привилегии",
                "icon"      => "glyphicon glyphicon-exclamation-sign",
                "callback"  => [$this, "actionPermission"],
            ],*/


            "activate-multi" =>
            [
                'class' => AdminMultiModelEditAction::className(),
                "name" => "Активировать",
                //"icon"              => "glyphicon glyphicon-trash",
                "eachCallback" => [$this, 'eachMultiActivate'],
            ],

            "inActivate-multi" =>
            [
                'class' => AdminMultiModelEditAction::className(),
                "name" => "Деактивировать",
                //"icon"              => "glyphicon glyphicon-trash",
                "eachCallback" => [$this, 'eachMultiInActivate'],
            ]
        ]);


        return $actions;
    }


    public function create(AdminAction $adminAction)
    {
        $modelClassName = $this->modelClassName;
        $model          = new $modelClassName();
        $model->loadDefaultValues();

        $relatedModel = $model->relatedPropertiesModel;
        $relatedModel->loadDefaultValues();

        $passwordChange = new PasswordChangeForm([
            'user' => $model
        ]);

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            $passwordChange->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model, $relatedModel, $passwordChange
            ]);
        }


        if ($rr->isRequestPjaxPost())
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save())
            {
                if ($passwordChange->new_password)
                {
                    if (!$passwordChange->changePassword())
                    {
                        \Yii::$app->getSession()->setFlash('error', "Пароль не изменен");
                    }
                }

                \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms','Saved'));

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {
                    return $this->redirect(
                        UrlHelper::constructCurrent()->setCurrentRef()->enableAdmin()->setRoute($this->modelDefaultAction)->normalizeCurrentRoute()
                            ->addData([$this->requestPkParamName => $model->{$this->modelPkAttribute}])
                            ->toString()
                    );
                } else
                {
                    return $this->redirect(
                        $this->indexUrl
                    );
                }

            } else
            {
                \Yii::$app->getSession()->setFlash('error', \Yii::t('skeeks/cms','Could not save'));
            }
        }

        return $this->render('_form', [
            'model'           => $model,
            'relatedModel'    => $relatedModel,
            'passwordChange'  => $passwordChange,
        ]);
    }


    public function update(AdminAction $adminAction)
    {
        /**
         * @var $model CmsUser
         */
        $model          = $this->model;
        $relatedModel   = $model->relatedPropertiesModel;
        $passwordChange = new PasswordChangeForm([
            'user' => $model
        ]);

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            $passwordChange->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model, $relatedModel, $passwordChange
            ]);
        }

        if ($rr->isRequestPjaxPost())
        {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            $passwordChange->load(\Yii::$app->request->post());

            if ($model->save() && $relatedModel->save())
            {
                \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms','Saved'));

                if ($passwordChange->new_password)
                {
                    if (!$passwordChange->changePassword())
                    {
                        \Yii::$app->getSession()->setFlash('error', "Пароль не изменен");
                    }
                }

                if (\Yii::$app->request->post('submit-btn') == 'apply')
                {

                } else
                {
                    return $this->redirect(
                        $this->indexUrl
                    );
                }

                $model->refresh();

            } else
            {
                $errors = [];

                if ($model->getErrors())
                {
                    foreach ($model->getErrors() as $error)
                    {
                        $errors[] = implode(', ', $error);
                    }
                }

                \Yii::$app->getSession()->setFlash('error', \Yii::t('skeeks/cms','Could not save') . ". " . implode($errors));
            }
        }

        return $this->render('_form', [
            'model'           => $model,
            'relatedModel'    => $relatedModel,
            'passwordChange'  => $passwordChange,
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
            'user' => $model
        ]);

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($modelForm);
        }


        if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword())
        {
            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
        } else
        {
            if (\Yii::$app->request->isPost)
            {
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
                'model' => $model,
                'avaliable' => $avaliable,
                'assigned' => $assigned,
                'idField' => 'id',
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
                    $item = $item ? : $manager->getPermission($name);
                    $manager->assign($item, $id);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        } else {
            foreach ($roles as $name) {
                try {
                    $item = $manager->getRole($name);
                    $item = $item ? : $manager->getPermission($name);
                    $manager->revoke($item, $id);
                } catch (\Exception $exc) {
                    $error[] = $exc->getMessage();
                }
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;

        return [$this->actionRoleSearch($id, 'avaliable', $post['search_av']),
            $this->actionRoleSearch($id, 'assigned', $post['search_asgn']),
            $error];
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
