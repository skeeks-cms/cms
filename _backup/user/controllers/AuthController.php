<?php
/**
 * AuthController
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\user\controllers;

use skeeks\cms\modules\user\models\User;
use skeeks\cms\modules\user\models\UserAuthclient;
use Yii;
use skeeks\cms\modules\user\models\LoginForm;
use skeeks\cms\modules\user\models\PasswordResetRequestForm;
use skeeks\cms\modules\user\models\ResetPasswordForm;
use skeeks\cms\modules\user\models\SignupForm;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Class AuthController
 * @package skeeks\cms\modules\user\controllers
 */
class AuthController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
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
            /*'error' => [
                'class' => 'yii\web\ErrorAction',
            ],*/
            /*'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],*/
            'authclient' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'successAuthclientCallback'],
            ],
        ];
    }

    /**
     * TODO: допилить, разделить
     * @param \yii\authclient\BaseClient $client
     * @return bool
     */
    public function successAuthclientCallback($client)
    {
        $attributes = $client->getUserAttributes();

        //TODO: добавить обновление данных
        if (!Yii::$app->getUser()->isGuest)
        {
            $userAuthClient = UserAuthclient::findOne([
                "user_id"               => Yii::$app->user->getId(),
                "provider"              => $client->getId(),
                "provider_identifier"   => $attributes["id"],
            ]);

            if (!$userAuthClient)
            {
                $userAuthClient = new UserAuthclient([
                    "user_id"               => Yii::$app->user->getId(),
                    "provider"              => $client->getId(),
                    "provider_identifier"   => $attributes["id"],
                    "provider_data"         => serialize($attributes)
                ]);

                $userAuthClient->save();
            }
        } else
        {
            $userAuthClient = UserAuthclient::findOne([
                "provider"              => $client->getId(),
                "provider_identifier"   => $attributes["id"],
            ]);

            if ($userAuthClient)
            {
                $user = User::findIdentity($userAuthClient->getUserId());
                if ($user)
                {
                    return Yii::$app->user->login($user, 0);
                }
            }
        }
    }


    /**
     * Авторизация обычная
     * @return string|\yii\web\Response
     */
    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Выход с сайта
     * @return \yii\web\Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * Регистрация
     * @return string|\yii\web\Response
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->getSession()->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->getSession()->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->getSession()->setFlash('success', 'New password was saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
}
