<?php
/**
 * AdminProfileController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 06.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelController;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\Search;
use skeeks\cms\models\UserGroup;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminOneModelEditAction;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\rbac\CmsManager;
use Yii;
use skeeks\cms\models\User;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class AdminProfileController
 * @package skeeks\cms\controllers
 */
class AdminProfileController extends BackendModelController
{
    /**
     * @return string
     */
    public function getPermissionName()
    {
        return CmsManager::PERMISSION_ADMIN_ACCESS;
    }

    public function init()
    {
        $this->name = "Личный кабинет";
        $this->modelShowAttribute = "username";
        $this->modelClassName = User::className();
        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(),
            [
                /*'file-manager' =>
                [
                    "class"         => BackendModelAction::class,
                    "name"          => "Личные файлы",
                    "icon"          => "glyphicon glyphicon-folder-open",
                    "callback"      => [$this, 'actionFileManager'],
                ],*/

                'update' =>
                    [
                        'class' => BackendModelUpdateAction::class,
                        "callback" => [$this, 'update'],
                        "isVisible" => false,
                        "accessCallback" => false
                    ],
            ]);


        ArrayHelper::remove($actions, 'delete');
        ArrayHelper::remove($actions, 'create');
        ArrayHelper::remove($actions, 'index');

        return $actions;
    }


    public function update($adminAction)
    {
        /**
         * @var $model CmsUser
         */
        $model = $this->model;
        $relatedModel = $model->relatedPropertiesModel;
        $passwordChange = new PasswordChangeForm([
            'user' => $model
        ]);
        $passwordChange->scenario = PasswordChangeForm::SCENARION_NOT_REQUIRED;

        $rr = new RequestResponse();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            $passwordChange->load(\Yii::$app->request->post());

            return \yii\widgets\ActiveForm::validateMultiple([
                $model,
                $relatedModel,
                $passwordChange
            ]);
        }

        if ($rr->isRequestPjaxPost()) {
            $model->load(\Yii::$app->request->post());
            $relatedModel->load(\Yii::$app->request->post());
            $passwordChange->load(\Yii::$app->request->post());

            try {


                if ($model->load(\Yii::$app->request->post()) && $model->save()) {


                    if ($relatedModel->load(\Yii::$app->request->post())) {
                        if (!$relatedModel->save()) {
                            throw new Exception("Не удалось сохранить дополнительные свойства: " . print_r($relatedModel->errors, true));
                        }
                    }

                    if ($passwordChange->load(\Yii::$app->request->post()) && $passwordChange->new_password) {
                        if (!$passwordChange->changePassword()) {
                            throw new Exception("Пароль не изменен");
                        }

                        \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Пароль успешно обновлен'));
                    }


                    if (\Yii::$app->request->post('submit-btn') == 'apply') {

                    } else {
                        return $this->redirect(
                            $this->url
                        );
                    }

                    $model->refresh();

                    \Yii::$app->getSession()->setFlash('success', \Yii::t('skeeks/cms', 'Данные обновлены'));

                } else {
                    throw new Exception("Не удалось сохранить дополнительные свойства: " . print_r($model->errors, true));
                }

            } catch (\Exception $e) {
                \Yii::$app->getSession()->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('_form', [
            'model' => $model,
            'relatedModel' => $relatedModel,
            'passwordChange' => $passwordChange,
        ]);
    }


    public function beforeAction($action)
    {
        $this->model = \Yii::$app->user->identity;
        return parent::beforeAction($action);
    }

    /**
     * @return mixed|\yii\web\Response
     */
    public function actionIndex()
    {
        return $this->redirect(UrlHelper::construct("cms/admin-profile/update")->enableAdmin()->toString());
    }

    /**
     * Updates an existing Game model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    /*public function actionFileManager()
    {
        $model = $this->model;


        return $this->render('@skeeks/cms/views/admin-user/file-manager', [
            'model' => $model
        ]);

    }*/


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

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax) {
            $modelForm->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return \skeeks\cms\modules\admin\widgets\ActiveForm::validate($modelForm);
        }


        if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword()) {
            \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
            return $this->redirect(['change-password', 'id' => $model->id]);
        } else {
            if (\Yii::$app->request->isPost) {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось изменить пароль');
            }

            return $this->render('@skeeks/cms/views/admin-user/change-password.php', [
                'model' => $modelForm
            ]);

            /*return $this->render('_form-change-password', [
                'model' => $modelForm,
            ]);*/
        }
    }


}
