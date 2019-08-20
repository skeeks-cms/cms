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
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\forms\LoginFormUsernameOrEmail;
use skeeks\cms\models\forms\PasswordResetRequestFormEmailOrLogin;
use skeeks\cms\models\forms\SignupForm;
use skeeks\cms\models\UserAuthClient;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use Yii;
use yii\authclient\BaseOAuth;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;

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
                        'only'  => ['logout'],
                        'rules' => [
                            [
                                'actions' => ['logout'],
                                'allow'   => true,
                                'roles'   => ['@'],
                            ],
                        ],
                    ],

                'verbs' => [
                    'class'   => VerbFilter::className(),
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
        $rr = new RequestResponse();
        $model = new PasswordResetRequestFormEmailOrLogin();
        //Не админка
        $model->isAdmin = false;

        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm()) {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost()) {
            if ($model->load(\Yii::$app->request->post()) && $model->sendEmail()) {
                $rr->success = true;
                $rr->message = 'Проверьте ваш email, дальнейшие инструкции мы отправили туда';
            } else {
                $rr->message = 'Не удалось выполнить запрос на восстановление пароля';
            }

            return (array)$rr;

        } else {
            if (\Yii::$app->request->isPost) {
                if ($model->load(\Yii::$app->request->post()) && $model->sendEmail()) {
                    if ($ref = UrlHelper::getCurrent()->getRef()) {
                        return $this->redirect($ref);
                    } else {
                        return $this->goBack();
                    }
                }
            }
        }

        return $this->render('forget', [
            'model' => $model,
        ]);
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $rr = new RequestResponse();

        $model = new LoginFormUsernameOrEmail();

        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm()) {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost()) {
            if ($model->load(\Yii::$app->request->post()) && $model->login()) {
                $rr->success = true;
                $rr->message = 'Авторизация прошла успешно';

                if ($ref = UrlHelper::getCurrent()->getRef()) {
                    $rr->redirect = $ref;
                } else {
                    $rr->redirect = Yii::$app->getUser()->getReturnUrl();;
                }
            } else {
                $rr->message = 'Не удалось авторизоваться';
            }

            return (array)$rr;

        } else {
            if (\Yii::$app->request->isPost) {
                if ($model->load(\Yii::$app->request->post()) && $model->login()) {
                    if ($ref = UrlHelper::getCurrent()->getRef()) {
                        return $this->redirect($ref);
                    } else {
                        return $this->goBack();
                    }

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
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $rr = new RequestResponse();
        $model = new SignupForm();

        $model->scenario = SignupForm::SCENARION_FULLINFO;

        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm()) {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost()) {
            if ($model->load(\Yii::$app->request->post()) && $registeredUser = $model->signup()) {
                $rr->success = true;
                $rr->message = 'Вы успешно зарегистрированны';

                \Yii::$app->user->login($registeredUser, 0);

                return $this->redirect($registeredUser->getPageUrl());

            } else {
                $rr->message = 'Не удалось зарегистрироваться';
            }

            return (array)$rr;

        } else {
            if (\Yii::$app->request->isPost) {
                if ($model->load(\Yii::$app->request->post()) && $model->sendEmail()) {
                    if ($ref = UrlHelper::getCurrent()->getRef()) {
                        return $this->redirect($ref);
                    } else {
                        return $this->goBack();
                    }
                }
            }
        }

        return $this->render('register', [
            'model' => $model,
        ]);
    }

    /**
     * Восстановлеине пароля
     * @return string|Response
     */
    public function actionRegisterByEmail()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $rr = new RequestResponse();
        $model = new SignupForm();

        $model->scenario = SignupForm::SCENARION_ONLYEMAIL;


        //Запрос на валидацию ajax формы
        if ($rr->isRequestOnValidateAjaxForm()) {
            return $rr->ajaxValidateForm($model);
        }
        //Запрос ajax post
        if ($rr->isRequestAjaxPost()) {

            if ($model->load(\Yii::$app->request->post()) && $model->validate()) {


                $t = \Yii::$app->db->beginTransaction();

                try {

                    $registeredUser = $model->signup();
                    if ($registeredUser) {

                        $t->commit();

                        $rr->success = true;
                        $rr->message = 'Для дальнейших действий, проверьте вашу почту.';

                        return $rr;

                    } else {
                        $rr->success = false;
                        $rr->message = 'Не удалось зарегистрироваться';
                    }

                } catch (\Exception $e) {
                    $t->rollBack();
                    throw $e;
                }
            } else {
                $rr->success = false;
                $rr->message = 'Не удалось зарегистрироваться';
            }

            return (array)$rr;

        }

        return $this->render('register', [
            'model' => $model,
        ]);
    }

    public function actionResetPassword()
    {
        $rr = new RequestResponse();
        $token = \Yii::$app->request->get('token');

        if (!$token) {
            return $this->goHome();
        }

        $className = \Yii::$app->user->identityClass;
        $user = $className::findByPasswordResetToken($token);

        if ($user) {
            $password = \Yii::$app->getSecurity()->generateRandomString(10);

            $user->setPassword($password);
            $user->generatePasswordResetToken();

            if ($user->save()) {

                \Yii::$app->mailer->view->theme->pathMap = ArrayHelper::merge(\Yii::$app->mailer->view->theme->pathMap,
                    [
                        '@app/mail' =>
                            [
                                '@skeeks/cms/mail-templates',
                            ],
                    ]);

                \Yii::$app->mailer->compose('@app/mail/new-password', [
                    'user'     => $user,
                    'password' => $password,
                ])
                    ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName])
                    ->setTo($user->email)
                    ->setSubject('Новый пароль для '.\Yii::$app->cms->appName)
                    ->send();

                $rr->success = true;
                $rr->message = 'Новый пароль отправлен на ваш e-mail';
            }
        } else {
            $rr->message = 'Ошибка, скорее всего данная ссылка уже устарела';
        }

        return $this->render('reset-password', (array)$rr);
    }


    /**
     * @return string|Response
     */
    public function actionApproveEmail()
    {
        $rr = new RequestResponse();
        $token = \Yii::$app->request->get('token');

        if (!$token) {
            return $this->goHome();
        }

        /**
         * @var $cmsUserEmail CmsUserEmail
         */
        $cmsUserEmail = CmsUserEmail::find()->where(['approved_key' => $token])->one();

        if ($cmsUserEmail) {
            $cmsUserEmail->is_approved = 1;
            $cmsUserEmail->approved_key_at = null;
            $cmsUserEmail->approved_key = null;


            if ($cmsUserEmail->save()) {

                if ($cmsUserEmail->cmsUser->email == $cmsUserEmail->value) {
                    $user = $cmsUserEmail->cmsUser;
                    $user->email_is_approved = 1;
                    if (!$user->save()) {
                        print_r($user->errors);die;
                    }
                }

                $rr->success = true;
                $rr->message = 'Поздравляем! Ваш email успешно подтвержден и теперь вы можете авторизоваться на сайте.';

            } else {
                $rr->success = false;
                $rr->message = 'Ошибка, скорее всего данная ссылка уже устарела';
            }


        } else {
            $rr->message = 'Ошибка, скорее всего данная ссылка уже устарела';
        }

        return $this->render('approve-email', (array)$rr);
    }


}