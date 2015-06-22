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
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\forms\PasswordChangeForm;
use skeeks\cms\models\User;
use Yii;
use skeeks\cms\models\searchs\User as UserSearch;
use \skeeks\cms\App;
use yii\helpers\ArrayHelper;
use yii\rest\UpdateAction;

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
     * @return array
     * @throws \skeeks\cms\components\Exception
     */
    protected function _user($username)
    {
        $model      = null;
        $personal   = false;
        //Если пользователь авторизован
        if (\Yii::$app->cms->getAuthUser())
        {
            //Если это личный профиль
            if (\Yii::$app->cms->getAuthUser()->username == $username)
            {
                $model = \Yii::$app->cms->getAuthUser();
                $personal = true;
            }
        }

        if (!$model)
        {
            $model = \Yii::$app->cms->findUser()->where(["username" => $username])->one(); //(["username" => $username]);
        }

        return [
            'model'         => $model,
            'personal'      => $personal,
        ];
    }


    /**
     * @param $username
     * @return string
     */
    public function actionChangePassword($username)
    {
        $data = $this->_user($username);

        $modelForm = new PasswordChangeForm([
            'user' => $data['model']
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

        return $this->render($this->action->id, $data);
    }


    /**
     * @param $username
     * @return string
     */
    public function actionEditInfo($username)
    {
        $data = $this->_user($username);
        $model = $data['model'];

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

        return $this->render($this->action->id, $data);
    }

}
