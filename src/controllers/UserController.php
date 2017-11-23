<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.06.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\user\UserAction;
use skeeks\cms\base\Controller;
use skeeks\cms\components\Cms;
use skeeks\cms\filters\CmsAccessControl;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;
use yii\helpers\ArrayHelper;
use yii\rest\UpdateAction;
use yii\web\NotFoundHttpException;

/**
 * @property CmsUser $user
 * @property bool $isOwner
 *
 * Class UserController
 * @package skeeks\cms\controllers
 */
class UserController extends Controller
{
    const REQUEST_PARAM_USERNAME = "username";

    /**
     * @var CmsUser
     */
    public $_user = false;

    public function init()
    {
        if (\Yii::$app->request->get(static::REQUEST_PARAM_USERNAME) && !$this->user) {
            throw new NotFoundHttpException("User not found or inactive");
        } else {
            if (\Yii::$app->request->get(static::REQUEST_PARAM_USERNAME) && \Yii::$app->cmsToolbar) {
                $controller = \Yii::$app->createController('cms/admin-user')[0];
                $adminControllerRoute = [
                    '/cms/admin-user/update',
                    $controller->requestPkParamName => $this->user->{$controller->modelPkAttribute}
                ];

                $urlEditModel = \skeeks\cms\backend\helpers\BackendUrlHelper::createByParams($adminControllerRoute)
                    ->enableEmptyLayout()
                    ->url;

                \Yii::$app->cmsToolbar->editUrl = $urlEditModel;
            }
        }


    }


    /**
     * @return array
     */
    public function behaviors()
    {
        return
            [
                //Closed all by default
                'access' =>
                    [
                        'class' => CmsAccessControl::className(),
                        'rules' =>
                            [
                                [
                                    'allow' => true,
                                    'matchCallback' => function($rule, $action) {
                                        return $this->isOwner;
                                    }
                                ]
                            ]
                    ],
            ];
    }

    /**
     * @return bool
     */
    public function getIsOwner()
    {
        return (bool)($this->user && $this->user->id == \Yii::$app->user->id);
    }

    /**
     * @return array|bool|null|CmsUser|\yii\db\ActiveRecord
     * @throws \yii\db\Exception
     */
    public function getUser()
    {
        if ($this->_user !== false) {
            return $this->_user;
        }

        if (!$username = \Yii::$app->request->get(static::REQUEST_PARAM_USERNAME)) {
            $this->_user = null;
            return false;
        }

        $userClass = \Yii::$app->user->identityClass;
        $this->_user = $userClass::find()->where([
            "username" => $username,
            'active' => Cms::BOOL_Y
        ])->one();

        return $this->_user;
    }


    /**
     * @return string
     */
    public function actionView()
    {
        return $this->render($this->action->id);
    }

    /**
     * @return string
     */
    public function actionEdit()
    {
        return $this->render($this->action->id);
    }


    /**
     * @param $username
     * @return string
     */
    public function actionChangePassword()
    {
        $modelForm = new PasswordChangeForm([
            'user' => $this->user
        ]);

        $rr = new RequestResponse();

        if ($rr->isRequestOnValidateAjaxForm()) {
            return $rr->ajaxValidateForm($modelForm);
        }

        if ($rr->isRequestAjaxPost()) {
            if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword()) {
                $rr->success = true;
                $rr->message = 'Пароль успешно изменен';
            } else {
                $rr->message = 'Не удалось изменить пароль';
            }

            return $rr;
        }

        return $this->render($this->action->id);
    }


    /**
     * @param $username
     * @return string
     */
    public function actionEditInfo()
    {
        $model = $this->user;

        $rr = new RequestResponse();

        if ($rr->isRequestOnValidateAjaxForm()) {
            return $rr->ajaxValidateForm($model);
        }

        if ($rr->isRequestAjaxPost()) {
            if ($model->load(\Yii::$app->request->post()) && $model->save()) {
                $rr->success = true;
                $rr->message = 'Данные успешно сохранены';
            } else {
                $rr->message = 'Не получилось сохранить данные';
            }

            return $rr;
        }

        return $this->render($this->action->id);
    }

}
