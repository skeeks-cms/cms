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
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Class AuthController
 * @package skeeks\cms\modules\admin\controllers
 */
class AuthController extends AdminController
{
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

    public function actionLogin()
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
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }
}