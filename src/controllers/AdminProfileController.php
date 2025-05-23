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

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelController;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\forms\PasswordChangeFormV2;
use skeeks\cms\models\User;
use skeeks\cms\models\UserGroup;
use skeeks\cms\modules\admin\controllers\helpers\rules\HasModel;
use skeeks\cms\rbac\CmsManager;
use skeeks\sx\helpers\ResponseHelper;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Class AdminProfileController
 * @package skeeks\cms\controllers
 */
class AdminProfileController extends BackendController
{
    public $defaultAction = "update";
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
        parent::init();
    }

    public function actions()
    {
        $actions = ArrayHelper::merge(parent::actions(), [
            "update" => [
                'class'     => BackendAction::class,
                'name'      => ['skeeks/cms', 'Personal data'],
                "callback"  => [$this, 'actionUpdate'],
                "icon" => 'hs-admin-user',
                "isVisible" => true,
            ],

            'password' =>
            [
                'class'           => BackendAction::class,
                'name'            => 'Смена пароля',
                "icon"            => "hs-admin-settings",
                "permissionNames" => [],
                "callback"        => [$this, 'actionPassword'],
                "priority"        => 10,
                "isVisible"       => true,
            ],
        ]);


        return $actions;
    }

    public function actionPassword()
    {
        $rr = new ResponseHelper();
        $model = \Yii::$app->user->identity;
        $formModel = new PasswordChangeFormV2();
        $formModel->user = $model;
        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($formModel);
        }*/

        if ($rr->isRequestAjaxPost) {
            try {
                if ($formModel->load(\Yii::$app->request->post()) && $formModel->changePassword()) {
                    $rr->message = '✓ Пароль изменен';
                    $rr->success = true;
                } else {
                    $rr->success = false;
                    $rr->data = [
                        'validation' => ArrayHelper::merge(
                            ActiveForm::validate($formModel), []
                        ),
                    ];
                }
            } catch (\Exception $exception) {
                $rr->message = 'Пароль не изменен!' . $exception->getMessage();
                $rr->success = false;
            }


            return $rr;
        }

        return $this->render($this->action->id, ['model' => $formModel]);
    }


    public function actionUpdate()
    {
        $rr = new ResponseHelper();
        $user = \Yii::$app->user->identity;

        /*if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($user);
        }*/

        if ($rr->isRequestAjaxPost) {
            if ($user->load(\Yii::$app->request->post()) && $user->save()) {
                $rr->message = '✓ Сохранено';
                $rr->success = true;
            } else {
                $rr->success = false;
                $rr->data = [
                    'validation' => ArrayHelper::merge(
                        ActiveForm::validate($user),
                    ),
                ];
            }

            return $rr;
        }

        return $this->render($this->action->id, [
            'model' => $user,
        ]);
    }


}
