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
namespace skeeks\cms\controllers;


use skeeks\cms\actions\LogoutAction;
use skeeks\cms\base\Controller;
use skeeks\cms\helpers\AjaxRequestResponse;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\forms\LoginForm;
use skeeks\cms\models\forms\LoginFormUsernameOrEmail;
use skeeks\cms\models\forms\PasswordResetRequestFormEmailOrLogin;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * Class AuthController
 * @package skeeks\cms\modules\admin\controllers
 */
class AuthController extends Controller
{
    public function behaviors()
    {
        return
        [
            'access' =>
            [
                'class' => AccessControl::className(),
                'only' => ['logout', 'login'],
                'rules' => [
                    [
                        'actions' => ['login'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'logout' => [
                'class' => LogoutAction::className(),
            ],
        ];
    }

    /**
     * Восстановлеине пароля
     * @return string|Response
     */
    public function actionForget()
    {
        $rr         = new RequestResponse();
        $model      = new PasswordResetRequestFormEmailOrLogin();
        //Не админка
        $model->isAdmin = false;

        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $model->sendEmail())
            {
                $rr->success = true;
                $rr->message = 'Проверьте ваш email, дальнейшие инструкции мы отправили туда';
            } else
            {
                $rr->message = 'Не удалось выполнить запрос на восстановление пароля';
            }

            return (array) $rr;

        } else if (\Yii::$app->request->isPost)
        {
            if ($model->load(\Yii::$app->request->post()) && $model->sendEmail())
            {
                if ($ref = UrlHelper::getCurrent()->getRef())
                {
                    return $this->redirect($ref);
                } else
                {
                    return $this->goBack();
                }
            }
        }

        return $this->render('forget', [
            'model' => $model,
        ]);
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $rr = new RequestResponse();

        $model = new LoginFormUsernameOrEmail();

        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $model->login())
            {
                $rr->success = true;
                $rr->message = 'Авторизация прошла успешно';

                if ($ref = UrlHelper::getCurrent()->getRef())
                {
                    $rr->redirect = $ref;
                } else
                {
                    $rr->redirect = Yii::$app->getUser()->getReturnUrl();;
                }
            } else
            {
                $rr->message = 'Не удалось авторизоваться';
            }

            return (array) $rr;

        } else if (\Yii::$app->request->isPost)
        {
            if ($model->load(\Yii::$app->request->post()) && $model->login())
            {
                if ($ref = UrlHelper::getCurrent()->getRef())
                {
                    return $this->redirect($ref);
                } else
                {
                    return $this->goBack();
                }

            }
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }



    public function actionRegister()
    {
        if (!\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(\Yii::$app->request->post()) && $model->login())
        {
            if ($ref = UrlHelper::getCurrent()->getRef())
            {
                return $this->redirect($ref);
            } else
            {
                return $this->goBack();
            }

        } else {
            return $this->render('register', [
                'model' => $model,
            ]);
        }
    }


    public function actionResetPassword()
    {
        $rr = new RequestResponse();
        $token = \Yii::$app->request->get('token');

        if (!$token)
        {
            return $this->goHome();
        }

        $className  = \Yii::$app->cms->getUserClassName();
        $user       = $className::findByPasswordResetToken($token);

        if ($user)
        {
            $password = \Yii::$app->getSecurity()->generateRandomString(10);

            $user->setPassword($password);
            $user->generatePasswordResetToken();

            if ($user->save()) {

                \Yii::$app->mailer->compose('@skeeks/cms/mail/newPassword', [
                        'user'      => $user,
                        'password'  => $password
                    ])
                    ->setFrom([\Yii::$app->params['supportEmail'] => \Yii::$app->name])
                    ->setTo($user->email)
                    ->setSubject('Новый пароль для ' . \Yii::$app->name)
                    ->send();

                $rr->success = true;
                $rr->message = 'Новый пароль отправлен на ваш e-mail';
            }
        } else
        {
            $rr->message = 'Ошибка, скорее всего данная ссылка уже устарела';
        }

        return $this->render('reset-password', (array) $rr);
    }
}