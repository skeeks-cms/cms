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
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\forms\LoginForm;
use skeeks\cms\models\forms\LoginFormUsernameOrEmail;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
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
            self::BEHAVIOR_ACTION_MANAGER =>
            [
                'class'     => ActionManager::className(),
                'actions'   =>
                    [
                        "login" =>
                        [
                            "label" => "Авторизация"
                        ],
                    ],
            ],

            'access' =>
            [
                'class' => AccessControl::className(),
                'only' => ['logout', 'login', 'auth'],
                'rules' => [
                    [
                        'actions' => ['login', 'auth'],
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

    public function actionAuth()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        if (!\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $goUrl = "";
        $success = false;

        $loginModel = new LoginFormUsernameOrEmail();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $loginModel->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($loginModel);
        }

        if (\Yii::$app->request->isPost)
        {
            if ($loginModel->load(\Yii::$app->request->post()) && $loginModel->login())
            {
                $success = true;

                if ($ref = UrlHelper::getCurrent()->getRef())
                {
                    $goUrl = $ref;
                } else
                {
                    $goUrl = Yii::$app->getUser()->getReturnUrl($defaultUrl);
                }

                if (\Yii::$app->request->isAjax)
                {

                } else
                {
                    return $this->redirect($goUrl);
                }
            }
        }


        return $this->render('auth', [
            'loginModel' => $loginModel,
            'goUrl' => $goUrl,
            'success' => $success
        ]);
    }




    public function actionLogin()
    {
        $this->layout = '@skeeks/cms/modules/admin/views/layouts/unauthorized.php';

        if (!\Yii::$app->user->isGuest)
        {
            return $this->goHome();
        }

        $goUrl = "";
        $success = false;

        $model = new LoginForm();

        if (\Yii::$app->request->isAjax && !\Yii::$app->request->isPjax)
        {
            $model->load(\Yii::$app->request->post());
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if (\Yii::$app->request->isPost)
        {
            if ($model->load(\Yii::$app->request->post()) && $model->login())
            {
                $success = true;

                if ($ref = UrlHelper::getCurrent()->getRef())
                {
                    $goUrl = $ref;
                } else
                {
                    $goUrl = Yii::$app->getUser()->getReturnUrl($defaultUrl);
                }

                if (\Yii::$app->request->isAjax)
                {

                } else
                {
                    return $this->redirect($goUrl);
                }
            }
        }


        return $this->render('login', [
            'model' => $model,
            'goUrl' => $goUrl,
            'success' => $success
        ]);
    }
}