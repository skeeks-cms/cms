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
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;
use \skeeks\cms\App;
use yii\helpers\ArrayHelper;
use yii\rest\UpdateAction;
use yii\web\NotFoundHttpException;

/**
 * Class UserController
 * @package skeeks\cms\controllers
 */
class UserController extends Controller
{
    const EVENT_INIT                   = 'event.userController.init';

    /**
     * @var null|AdminAction[]
     */
    protected $_actions    = null;

    /**
     * После инициализации, контроллера, любой компонент, может добавить свои дейсвия, они будут добавлены к текущим дейсвоиям контроллера.
     * @see init()
     * @see actions()
     * @var array
     */
    public $eventActions = [];


    /**
     * @var User
     */
    public $user = null;


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }


    public function init()
    {
        parent::init();
    }



    /**
     * @return array
     */
    public function actions()
    {
        return ArrayHelper::merge($this->eventActions, [
            "view" =>
            [
                'class'         => UserAction::className(),
                "name"          => "Профиль",
                "icon"          => "glyphicon glyphicon-trash",
            ],

            "edit" =>
            [
                'class'         => UserAction::className(),
                "name"          => "Настройки",
                "icon"          => "fa fa-cog",
            ]
        ]);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionProfile()
    {
        return $this->redirect(\Yii::$app->user->identity->getPageUrl());
    }

    /**
     * Массив объектов действий доступных для текущего контроллера
     * Используется при построении меню.
     * @see ControllerActions
     * @return AdminAction[]
     */
    public function getActions()
    {
        if ($this->_actions !== null)
        {
            return $this->_actions;
        }

        $actions = $this->actions();

        if ($actions)
        {
            foreach ($actions as $id => $data)
            {
                $action                 = $this->createAction($id);

                if ($action->isVisible())
                {
                    $this->_actions[$id]    = $action;
                }
            }
        } else
        {
            $this->_actions = [];
        }

        //Сортировка по приоритетам
        if ($this->_actions)
        {
            ArrayHelper::multisort($this->_actions, 'priority');

        }

        return $this->_actions;
    }


    /**
     * @param $username
     * @throws \yii\db\Exception
     */
    public function initUser($username)
    {
        $this->user = \Yii::$app->cms->findUser()->where([
            "username"  => $username,
            'active'    => Cms::BOOL_Y
        ])->one();

        if (!$this->user)
        {
            throw new NotFoundHttpException;
        }
    }

    /**
     * @return array
     */
    public function getViewParams()
    {
        return [
            'action'        => $this->action,
            'controller'    => $this,
            'model'         => $this->user
        ];
    }


    /**
     * @param $username
     * @return string
     */
    public function actionChangePassword($username)
    {
        $this->initUser($username);

        $modelForm = new PasswordChangeForm([
            'user' => $this->user
        ]);

        $rr = new RequestResponse();

        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($modelForm);
        }

        if ($rr->isRequestAjaxPost())
        {
            if ($modelForm->load(\Yii::$app->request->post()) && $modelForm->changePassword())
            {
                $rr->success = true;
                $rr->message = 'Пароль успешно изменен';
            } else
            {
                $rr->message = 'Не удалось изменить пароль';
            }

            return $rr;
        }

        return $this->render($this->action->id, $this->getViewParams());
    }


    /**
     * @param $username
     * @return string
     */
    public function actionEditInfo($username)
    {
        $this->initUser($username);
        $model = $this->user;

        $rr = new RequestResponse();

        if ($rr->isRequestOnValidateAjaxForm())
        {
            return $rr->ajaxValidateForm($model);
        }

        if ($rr->isRequestAjaxPost())
        {
            if ($model->load(\Yii::$app->request->post()) && $model->save())
            {
                $rr->success = true;
                $rr->message = 'Данные успешно сохранены';
            } else
            {
                $rr->message = 'Не получилось сохранить данные';
            }

            return $rr;
        }

        return $this->render($this->action->id, $this->getViewParams());
    }

}
