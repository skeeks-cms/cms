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
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\AjaxRequestResponse;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\forms\LoginForm;
use skeeks\cms\models\forms\LoginFormUsernameOrEmail;
use skeeks\cms\models\forms\PasswordResetRequestFormEmailOrLogin;
use skeeks\cms\models\forms\SignupForm;
use skeeks\cms\models\User;
use skeeks\cms\models\user\UserEmail;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use skeeks\cms\models\UserAuthClient;
use yii\authclient\BaseOAuth;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\Response;
use yii\widgets\ActiveForm;
use \Yii;

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
                'class' => \yii\filters\AccessControl::className(),
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

            'client' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }


    /**
     * @param BaseOAuth $client
     * @throws \yii\db\Exception
     */
    public function onAuthSuccess($client)
    {
        \Yii::info('start auth client: ' . $client->getId(), 'authClient');

        $attributes = $client->getUserAttributes();


        /* @var $userAuthClient UserAuthClient */
        $userAuthClient = UserAuthClient::find()->where([
            'provider'              => $client->getId(),
            'provider_identifier'   => ArrayHelper::getValue($attributes, 'id'),
        ])->one();


        if (\Yii::$app->user->isGuest)
        {
            if ($userAuthClient)
            { // login

                $userAuthClient->provider_data = $attributes;
                $userAuthClient->save();

                $user = $userAuthClient->user;
                \Yii::$app->user->login($user);

            } else
            { // signup
                if (isset($attributes['email']) && User::find()->where(['email' => $attributes['email']])->exists())
                {
                    $error = Yii::t('app', "User with the same email as in {client} account already exists but isn't linked to it. Login using email first to link it.", ['client' => $client->getTitle()]);

                    Yii::$app->getSession()->setFlash('error', [
                        $error
                    ]);

                    \Yii::error($error, 'authClient');
                } else
                {

                    /**
                     * @var User $user
                     */
                    $userClassName          = \Yii::$app->cms->getUserClassName();

                    //Если сеть прислала email пользователя, и этот email уже есть упользователя нашего сайта, привязываем его к этому юзеру, если нет, создаем нового.
                    if ($userEmail = ArrayHelper::getValue($attributes, 'email'))
                    {
                        /**
                         * @var UserEmail $userEmailModel
                         */
                        $userEmailModel = UserEmail::find()->where(['value' => $userEmail])->andWhere(['approved' => Cms::BOOL_Y])->one();
                        if ($userEmailModel)
                        {
                            $user = $userEmailModel->user;
                        }
                    }

                    if (!$user)
                    {
                        $user                   = new $userClassName();

                        if ($userEmail)
                        {
                            $user->email = $userEmail;
                        }


                        if ($userLogin = ArrayHelper::getValue($attributes, 'login'))
                        {
                            $user->username = $userLogin;
                        } else
                        {
                            $user->generateUsername();
                        }

                        $password = \Yii::$app->security->generateRandomString(6);

                        $user->setPassword($password);
                        $user->generateAuthKey();
                        $user->generatePasswordResetToken();

                        if (!$user->save())
                        {
                            \Yii::error("Не удалось создать пользователя: " . serialize($user->getErrors()), 'authClient');
                            return false;
                        }
                    }


                    //$transaction = $user->getDb()->beginTransaction();

                    $auth = new UserAuthClient([
                        'user_id' => $user->id,
                        'provider' => $client->getId(),
                        'provider_identifier' => (string)$attributes['id'],
                        'provider_data' => $attributes,
                    ]);
                    if ($auth->save())
                    {
                        //$transaction->commit();
                        Yii::$app->user->login($user);
                    } else
                    {
                        \Yii::error("Не удалось создать социальный профиль: " . serialize($auth->getErrors()), 'authClient');
                    }
                }
            }
        } else
        { // user already logged in
            if (!$userAuthClient)
            { // add auth provider

                $userAuthClient = new UserAuthClient([
                    'user_id' => Yii::$app->user->id,
                    'provider' => $client->getId(),
                    'provider_identifier' => $attributes['id'],
                    'provider_data' => $attributes,
                ]);

                $userAuthClient->save();
            } else
            {
                $userAuthClient->provider_data = $attributes;
                $userAuthClient->save();
            }
        }

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




    /**
     * Восстановлеине пароля
     * @return string|Response
     */
    public function actionRegister()
    {
        if (!\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $rr         = new RequestResponse();
        $model      = new SignupForm();

        $model->scenario = SignupForm::SCENARION_FULLINFO;

        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $registeredUser = $model->signup())
            {
                $rr->success = true;
                $rr->message = 'Вы успешно зарегистрированны';

                \Yii::$app->user->login($registeredUser, 0);

                return $this->redirect($registeredUser->getPageUrl());

            } else
            {
                $rr->message = 'Не удалось зарегистрироваться';
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

        return $this->render('register', [
            'model' => $model,
        ]);
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
                    ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName])
                    ->setTo($user->email)
                    ->setSubject('Новый пароль для ' . \Yii::$app->cms->appName)
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