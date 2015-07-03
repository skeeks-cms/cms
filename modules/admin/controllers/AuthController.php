<?php
/**
 * AuthController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 05.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;


use skeeks\cms\actions\LogoutAction;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\forms\BlockedUserForm;
use skeeks\cms\models\User;
use skeeks\cms\models\forms\LoginForm;
use skeeks\cms\models\forms\LoginFormUsernameOrEmail;
use skeeks\cms\models\forms\PasswordResetRequestForm;
use skeeks\cms\models\forms\PasswordResetRequestFormEmailOrLogin;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ActiveForm;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * Class AuthController
 * @package skeeks\cms\modules\admin\controllers
 */
class AuthController extends AdminController
{
    /**
     * @var boolean whether to enable CSRF validation for the actions in this controller.
     * CSRF validation is enabled only when both this property and [[Request::enableCsrfValidation]] are true.
     */
    //public $enableCsrfValidation = false;

    public function behaviors()
    {
        return
        [
            'access' =>
            [
                'class' => AdminAccessControl::className(),
                'only' => [
                    'logout', 'lock'
                    //, 'login', 'auth', 'reset-password'
                ],
                'rules' => [
                    /*[
                        'actions' => ['login', 'auth', 'reset-password'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],*/
                    [
                        'actions' => ['logout', 'lock'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                    'lock' => ['post'],
                ],
            ],
        ];
    }

    public $defaultAction = 'auth';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        //var_dump(\Yii::$app->request->getCookies()->getValue(\Yii::$app->request->csrfParam));
       // var_dump(\Yii::$app->request->getBodyParam(\Yii::$app->request->csrfParam));
       // die;
        return [
            'logout' => [
                'class' => LogoutAction::className(),
            ],
        ];
    }

    public function actionLock()
    {
        $this->view->title = 'Режим блокировки';
        \Yii::$app->user->identity->lockAdmin();

        if ($ref = UrlHelper::getCurrent()->getRef())
        {
            return $this->redirect($ref);
        } else
        {
            return $this->redirect(Yii::$app->getUser()->getReturnUrl());
        }
    }

    public function actionResetPassword()
    {
        $this->view->title = 'Восстановление пароля';
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        if (!\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $token = \Yii::$app->request->get('token');

        if(!$token)
        {
            return $this->goHome();
        }

        $user = User::findByPasswordResetToken($token);

        if ($user)
        {
            $password = \Yii::$app->getSecurity()->generateRandomString(10);

            $user->setPassword($password);
            $user->generatePasswordResetToken();

            if ($user->save(false))
            {

                \Yii::$app->mailer->setViewPath(\Yii::$app->cms->moduleCms()->basePath . '/mail');

                \Yii::$app->mailer->compose('newPassword', ['user' => $user, 'password' => $password])
                    ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName])
                    ->setTo($user->email)
                    ->setSubject('Новый пароль для ' . \Yii::$app->cms->appName)
                    ->send();

                $message = 'Новый пароль отправлен на ваш e-mail';
            }
        } else
        {
            $message = 'Ссылка устарела, попробуйте запросить восстановление пароля еще раз.';
        }


        return $this->render('reset-password', [
            'message' => $message,
        ]);
    }

    public function actionBlocked()
    {
        $this->view->title = 'Режим блокировки';

        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        if ($ref = UrlHelper::getCurrent()->getRef())
        {
            $goUrl = $ref;
        }

        if (!$goUrl)
        {
            $goUrl = \Yii::$app->getHomeUrl();
        }

        if (\Yii::$app->user->isGuest)
        {
            return $goUrl ? $this->redirect($goUrl) : $this->goHome();
        }

        $model             = new BlockedUserForm();

        $rr = new RequestResponse();
        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($model);
        }

        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $model->login())
            {
                $rr->success = true;
                $rr->message = "";
                $rr->redirect = $goUrl;
            } else
            {
                $rr->success = false;
                $rr->message = "Не получилось авторизоваться";
            }

            return $rr;
        }

        return $this->render('blocked',
        [
            'model' => $model
        ]);
    }

    public function actionAuth()
    {
        $this->view->title = 'Авторизация';
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        $goUrl = "";
        $loginModel             = new LoginFormUsernameOrEmail();

        $successReset = null;
        $resetMessage = '';
        $passwordResetModel     = new PasswordResetRequestFormEmailOrLogin();

        if ($ref = UrlHelper::getCurrent()->getRef())
        {
            $goUrl = $ref;
        }

        $rr = new RequestResponse();

        if (!\Yii::$app->user->isGuest)
        {
            return $goUrl ? $this->redirect($goUrl) : $this->goHome();
        }

        //Авторизация
        if (\Yii::$app->request->post('do') == 'login')
        {

            if ($rr->isRequestOnValidateAjaxForm())
            {
                return $rr->ajaxValidateForm($loginModel);
            }

            if ($rr->isRequestAjaxPost())
            {
                if ($loginModel->load(\Yii::$app->request->post()) && $loginModel->login())
                {
                    if (!$goUrl)
                    {
                        $goUrl = Yii::$app->getUser()->getReturnUrl($defaultUrl);
                    }

                    $rr->redirect = $goUrl;

                    $rr->success = true;
                    $rr->message = "";
                    $rr->message = "";
                    return (array) $rr;
                } else
                {
                    $rr->success = false;
                    $rr->message = "Неудачная попытка авторизации";
                    return (array) $rr;
                }
            }
        }


        //Запрос на сброс пароля
        if (\Yii::$app->request->post('do') == 'password-reset')
        {
            if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
            {
                $passwordResetModel->load(\Yii::$app->request->post());
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($passwordResetModel);
            }

            if (\Yii::$app->request->isPost)
            {
                if ($passwordResetModel->load(\Yii::$app->request->post()) && $passwordResetModel->sendEmail())
                {
                    $resetMessage = 'Проверьте ваш email';
                    $successReset = true;
                } else
                {
                    $successReset = false;
                    $resetMessage = 'Не удалось отправить email';
                }
            }
        }



        return $this->render('auth', [
            'loginModel'            => $loginModel,
            'passwordResetModel'    => $passwordResetModel,
            'goUrl'                 => $goUrl,
            'success'               => $success,
            'successReset'               => $successReset,
            'resetMessage'               => $resetMessage
        ]);
    }

}