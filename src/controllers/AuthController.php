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
use skeeks\cms\validators\PhoneValidator;
use Yii;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Class AuthController
 * @package skeeks\cms\modules\admin\controllers
 */
class AuthController extends Controller
{
    public $defaultAction = 'login';

    public function behaviors()
    {
        return [
            'access' => [
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

            if ($user->save(false)) {

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

        /*if (!$token) {
            return $this->goHome();
        }*/

        $model = new DynamicModel(['code']);
        $model->addRule('code', 'required');

        if ($token) {
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
                            print_r($user->errors);
                            die;
                        }
                    }

                    \Yii::$app->user->login($cmsUserEmail->cmsUser);
                    return $this->redirect(Yii::$app->getUser()->getReturnUrl());

                    $rr->success = true;
                    $rr->message = 'Поздравляем! Ваш e-mail успешно подтвержден и теперь вы можете авторизоваться на сайте.';

                } else {
                    $rr->success = false;
                    $rr->message = 'Проверочный код некорреткный.';
                }


            } else {
                $rr->message = 'Проверочный код некорреткный.';
            }
        } else {
            $rr->message = 'На Ваш электронный адрес отправлено письмо с проверочным кодом.';
        }


        return $this->render('approve-email', ArrayHelper::merge((array)$rr, [
            'model' => $model,
        ]));
    }





    /**
     * Авторизация пользователя через телефон
     *
     * @return array
     */
    public function actionAuthByPhone()
    {
        $rr = new RequestResponse();

        //Запрос ajax post
        if ($rr->isRequestAjaxPost() && \Yii::$app->user->isGuest) {

            try {

                $model = new DynamicModel();
                $model->defineAttribute("phone", \Yii::$app->request->post('phone'));
                $model->addRule("phone", "required");
                $model->addRule("phone", PhoneValidator::class);

                if (!$model->validate()) {
                    throw new Exception("Некорректный номер телефона");
                }

                $rr->success = true;
                $rr->message = '';

                if ($user = CmsUser::find()->andWhere(['phone' => $model->phone])->one()) {
                    //Если пользователь существует
                    //Нужно предложить ему авторизоваться с его паролем

                    $rr->data = [
                        'user'  => true,
                        'phone' => $model->phone,
                        'type'  => "password",
                    ];


                } else {
                    //Если пользователь не существует нужно создать
                    $t = \Yii::$app->db->beginTransaction();
                    try {

                        $class = \Yii::$app->user->identityClass;
                        $user = new $class();
                        $user->phone = $model->phone;
                        if (!$user->save()) {
                            throw new Exception("Ошибка регистрации: " . print_r($user->errors, true));
                        }

                        //Генерация, отправка и сохранение sms кода
                        $this->_generateAndSaveSmsCode($model->phone);

                        $t->commit();

                        //Авторазиция по временному коду
                        $rr->data = [
                            'user'  => true,
                            'phone' => $model->phone,
                            'type'  => "tmp-phone-code",
                            'left-repeat' => $this->getSessionAuthPhoneLeftRepeat()
                        ];


                    } catch (\Exception $exception) {
                        $t->rollBack();
                        throw $exception;
                    }

                }
            } catch (\Exception $e) {
                $rr->message = $e->getMessage();
                $rr->success = false;
            }
        }

        return (array)$rr;
    }


    /**
     * Авторизация пользователя через телефон и пароль
     *
     * @return array
     */
    public function actionAuthByPhonePassword()
    {
        $rr = new RequestResponse();

        //Запрос ajax post
        if ($rr->isRequestAjaxPost() && \Yii::$app->user->isGuest) {

            try {

                $model = new DynamicModel();
                $model->defineAttribute("phone", \Yii::$app->request->post('phone'));
                $model->defineAttribute("password", \Yii::$app->request->post('password'));
                $model->addRule("phone", PhoneValidator::class);
                $model->addRule("phone", "required");
                $model->addRule("password", "required");

                if (!$model->validate()) {
                    throw new Exception("Некорректные данные для авторизации");
                }

                $rr->success = true;
                $rr->message = '';

                /**
                 * @var $user CmsUser
                 */
                if (!$user = CmsUser::find()->andWhere(['phone' => $model->phone])->one()) {
                    throw new Exception("Некорректные данные для входа");
                }

                if (!$user->validatePassword($model->password)) {
                    throw new Exception("Некорректные данные для входа");
                }

                \Yii::$app->user->login($user,  3600 * 24 * 30);

                $rr->success = true;

                if ($ref = UrlHelper::getCurrent()->getRef()) {
                    $rr->redirect = $ref;
                } else {
                    $rr->redirect = \Yii::$app->user->getReturnUrl();;
                }

            } catch (\Exception $e) {
                $rr->message = $e->getMessage();
                $rr->success = false;
            }
        }

        return (array)$rr;
    }


    /**
     * @return RequestResponse
     */
    public function actionAuthByPhoneSmsCode()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            if (\Yii::$app->request->post('phone_code') == $this->getSessionAuthPhoneCode() && $this->getSessionAuthPhone()) {
                $user = CmsUser::find()->andWhere(['phone' => $this->getSessionAuthPhone()])->one();
                \Yii::$app->user->login($user, 3600 * 24 * 30);

                $rr->success = true;
                $rr->message = 'Авторизация прошла успешно';

                if ($ref = UrlHelper::getCurrent()->getRef()) {
                    $rr->redirect = $ref;
                } else {
                    $rr->redirect = \Yii::$app->getUser()->getReturnUrl();;
                }
            } else {
                $rr->success = false;
                $rr->message = 'Код некорректный или устарел';
            }
        }

        return $rr;
    }


    /**
     * @return RequestResponse
     */
    public function actionGeneratePhoneCode()
    {
        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            try {

                if (!\Yii::$app->user->isGuest) {
                    throw new Exception("Некорректный запрос");
                }

                if (!$phone = \Yii::$app->request->post('phone')) {
                    throw new Exception("Некорректный запрос");
                }

                $this->_generateAndSaveSmsCode($phone);
                $rr->data = [
                    'left-repeat' => $this->getSessionAuthPhoneLeftRepeat()
                ];

                $rr->message = "Проверочный код отправлен в SMS";
                $rr->success = true;

            } catch (\Exception $e ) {
                $rr->success = false;
                $rr->message = $e->getMessage();
            }

        }

        return $rr;
    }



    /**
     * Авторизация пользователя через телефон
     *
     * @return array
     */
    public function actionAuthByEmail()
    {
        $rr = new RequestResponse();

        //Запрос ajax post
        if ($rr->isRequestAjaxPost() && \Yii::$app->user->isGuest) {

            try {

                $model = new DynamicModel();
                $model->defineAttribute("email", \Yii::$app->request->post('email'));
                $model->addRule("email", "required");
                $model->addRule("email", "email");

                if (!$model->validate()) {
                    throw new Exception("Некорректный email");
                }

                $rr->success = true;
                $rr->message = '';

                if ($user = CmsUser::find()->andWhere(['email' => $model->email])->one()) {
                    //Если пользователь существует
                    //Нужно предложить ему авторизоваться с его паролем

                    $rr->data = [
                        'user'  => true,
                        'email' => $model->email,
                        'type'  => "password",
                    ];


                } else {
                    //Если пользователь не существует нужно создать
                    $t = \Yii::$app->db->beginTransaction();
                    try {

                        $class = \Yii::$app->user->identityClass;
                        $user = new $class();
                        $user->email = $model->email;
                        if (!$user->save()) {
                            throw new Exception("Ошибка регистрации: " . print_r($user->errors, true));
                        }

                        //Генерация, отправка и сохранение sms кода
                        //$this->_generateAndSaveSmsCode($model->phone);

                        $t->commit();

                        //Авторазиция по временному коду
                        $rr->data = [
                            'user'  => true,
                            'email' => $model->email,
                            'type'  => "tmp-email-code",
                            'left-repeat' => $this->getSessionAuthEmailLeftRepeat()
                        ];


                    } catch (\Exception $exception) {
                        $t->rollBack();
                        throw $exception;
                    }

                }
            } catch (\Exception $e) {
                $rr->message = $e->getMessage();
                $rr->success = false;
            }
        }

        return (array)$rr;
    }

    /**
     * Авторизация пользователя через телефон и пароль
     *
     * @return array
     */
    public function actionAuthByEmailPassword()
    {
        $rr = new RequestResponse();

        //Запрос ajax post
        if ($rr->isRequestAjaxPost() && \Yii::$app->user->isGuest) {

            try {

                $model = new DynamicModel();
                $model->defineAttribute("email", \Yii::$app->request->post('email'));
                $model->defineAttribute("password", \Yii::$app->request->post('password'));
                $model->addRule("email", "email");
                $model->addRule("email", "required");
                $model->addRule("password", "required");

                if (!$model->validate()) {
                    throw new Exception("Некорректные данные для авторизации");
                }

                $rr->success = true;
                $rr->message = '';

                /**
                 * @var $user CmsUser
                 */
                if (!$user = CmsUser::find()->andWhere(['email' => $model->email])->one()) {
                    throw new Exception("Некорректные данные для входа");
                }

                if (!$user->validatePassword($model->password)) {
                    throw new Exception("Некорректные данные для входа");
                }

                \Yii::$app->user->login($user,  3600 * 24 * 30);

                $rr->success = true;

                if ($ref = UrlHelper::getCurrent()->getRef()) {
                    $rr->redirect = $ref;
                } else {
                    $rr->redirect = \Yii::$app->user->getReturnUrl();;
                }

            } catch (\Exception $e) {
                $rr->message = $e->getMessage();
                $rr->success = false;
            }
        }

        return (array)$rr;
    }

    /**
     * Генерация, сохранение в сессию и отправка sms кода
     *
     * @param $phone
     * @throws Exception
     */
    protected function _generateAndSaveSmsCode($phone)
    {
        //Если на этот номер уже отправили пароль
        if ($this->getSessionAuthPhone() == $phone && $this->getSessionAuthPhoneIsActual()) {

        } else {
            $code = rand(1000, 9999);

            $text = "Ваш код подтверждения: {$code}. Наберите его в поле ввода.";
            $cmsSmsMessage = \Yii::$app->cms->smsProvider->send($phone, $text);

            if ($cmsSmsMessage->isError) {
                throw new Exception("Отправка sms не удалась: {$cmsSmsMessage->error_message}");
            }

            \Yii::$app->session->set(self::SESSION_AUTH_SMS_DATA, [
                'phone'      => $phone,
                'created_at' => time(),
                'phone_code' => $code,
            ]);
        }
    }

    const SESSION_AUTH_SMS_DATA = "auth_sms_data";
    const SESSION_AUTH_EMAIL_DATA = "auth_email_data";

    /**
     * @return array
     */
    public function getSessionSmsData()
    {
        return (array)\Yii::$app->session->get(self::SESSION_AUTH_SMS_DATA);
    }
    /**
     * @return array
     */
    public function getSessionEmailData()
    {
        return (array)\Yii::$app->session->get(self::SESSION_AUTH_EMAIL_DATA);
    }

    public function getSessionAuthPhone()
    {
        return (string)ArrayHelper::getValue($this->getSessionSmsData(), "phone");
    }

    public function getSessionAuthPhoneCode()
    {
        return (string)ArrayHelper::getValue($this->getSessionSmsData(), "phone_code");
    }

    public function getSessionAuthPhoneCreatedAt()
    {
        return (int)ArrayHelper::getValue($this->getSessionSmsData(), "created_at");
    }

    public function getSessionAuthPhoneDuration()
    {
        return (int)(time() - $this->getSessionAuthPhoneCreatedAt());
    }

    public function getSessionAuthPhoneLeftRepeat()
    {
        return 60*20 - $this->getSessionAuthPhoneDuration();
    }

    /**
     * @return bool
     */
    public function getSessionAuthPhoneIsActual()
    {
        return (bool)($this->getSessionAuthPhoneLeftRepeat() > 0);
    }




    public function getSessionAuthEmail()
    {
        return (string)ArrayHelper::getValue($this->getSessionEmailData(), "phone");
    }

    public function getSessionAuthEmailCode()
    {
        return (string)ArrayHelper::getValue($this->getSessionEmailData(), "phone_code");
    }

    public function getSessionAuthEmailCreatedAt()
    {
        return (int)ArrayHelper::getValue($this->getSessionEmailData(), "created_at");
    }

    public function getSessionAuthEmailDuration()
    {
        return (int)(time() - $this->getSessionAuthEmailCreatedAt());
    }

    public function getSessionAuthEmailLeftRepeat()
    {
        return 60*20 - $this->getSessionAuthEmailDuration();
    }

    /**
     * @return bool
     */
    public function getSessionAuthEmailIsActual()
    {
        return (bool)($this->getSessionAuthEmailLeftRepeat() > 0);
    }

}