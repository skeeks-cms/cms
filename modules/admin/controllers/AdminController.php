<?php
/**
 * Это самый базовый контроллер админки, все контроллеры, сторонних модулей и приложения, должны быть дочерними от этого контроллера.
 *
 * 1) Закрываем путь всем неавторизованным пользователям
 * 2) Определяем layout админки
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;

use skeeks\cms\App;
use skeeks\cms\base\Controller;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\filters\AccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\base\ActionEvent;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\ForbiddenHttpException;

/**
 * Class AdminController
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class AdminController extends Controller
{
    public $beforeRender = null;

    const BEHAVIOR_ACTION_MANAGER = "actionManager";
    /**
     * @var string понятное название контроллера, будет добавлено в хлебные крошки и title страницы
     */
    protected $_label   = null;

    public function behaviors()
    {
        return
        [
            self::BEHAVIOR_ACTION_MANAGER =>
            [
                'class'     => ActionManager::className(),
                'actions'   => [],
            ],

            'access' =>
            [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['cms.admin-access'],
                    ]
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();

        $this->_ensure();
        $this->layout = App::moduleAdmin()->layout;


        //Смотрим зарегистрирована ли привилегия этого контроллера, если да то проверим ее
        $controllerPermissionName = App::moduleAdmin()->getPermissionCode($this->getUniqueId());
        if ($permission = \Yii::$app->authManager->getPermission($controllerPermissionName))
        {
            if (!\Yii::$app->user->can($permission->name))
            {
                if (\Yii::$app->user->getIsGuest())
                {
                    \Yii::$app->getResponse()->redirect(
                        UrlHelper::construct("admin/auth/login")->setCurrentRef()->enableAdmin()->createUrl()
                    );
                } else
                {
                    throw new ForbiddenHttpException(\Yii::t('yii', 'You are not allowed to perform this action.'));
                }

            }
        }

        $this->on(self::EVENT_BEFORE_ACTION, [$this, "_beforeAction"]);
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }
    /**
     * Проверка целостности данных для работы контроллера
     */
    protected function _ensure()
    {}


    /**
     * @return ActionManager
     */
    public function actionManager()
    {
        return $this->getBehavior(self::BEHAVIOR_ACTION_MANAGER);
    }


    /**
     *
     * Вытащить объект текущего действия из события
     *
     * @param ActionEvent $e
     * @return bool|helpers\Action
     */
    protected function _getActionFromEvent(ActionEvent $e)
    {
        $currentAction = false;

        if ($this->actionManager()->hasAction($e->action->id))
        {
            $currentAction = $this->actionManager()->getAction($e->action->id);
        }

        return $currentAction;
    }


    /**
     * @param ActionEvent $e
     */
    protected function _beforeAction(ActionEvent $e)
    {
        $this->_actionAccess($e);

        $this->_renderActions($e);
        $this->_renderBreadcrumbs($e);
        $this->_renderMetadata($e);
    }

    protected function _actionAccess(ActionEvent $e)
    {

            //Смотрим зарегистрирована ли привилегия этого контроллера, если да то проверим ее
        $acttionPermissionName = App::moduleAdmin()->getPermissionCode($this->getUniqueId() . '/' . $e->action->id);

        if ($permission = \Yii::$app->authManager->getPermission($acttionPermissionName))
        {
            if (!\Yii::$app->user->can($permission->name))
            {
                throw new ForbiddenHttpException(\Yii::t('yii', 'You are not allowed to perform this action.'));
            }
        }

        return $this;
    }

    /**
     *
     * Рендер действий текущего контроллера
     * Сразу запускаем нужный виджет и формируем готовый html
     * @param ActionEvent $e
     *
     * @return $this
     */
    protected function _renderActions(ActionEvent $e)
    {
        $this->getView()->params["actions"] = ControllerActions::begin([
            "currentActionCode"     => $e->action->id,
            "controller"            => $this,
        ])->run();

        return $this;
    }


    /**
     * Формируем данные для хлебных крошек.
     * Эти данные в layout - е будут передаваться в нужный виджет.
     * @param ActionEvent $e
     *
     * @return $this
     */
    protected function _renderBreadcrumbs(ActionEvent $e)
    {
        $actionTitle = Inflector::humanize($e->action->id);

        if ($currentAction = $this->_getActionFromEvent($e))
        {
            $actionTitle = $currentAction->label;
        }

        if ($this->_label)
        {
            $this->getView()->params['breadcrumbs'][] = ['label' => $this->_label, 'url' => [
                'index',
                UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
            ]];
        }

        if ($this->defaultAction != $e->action->id)
        {
            $this->getView()->params['breadcrumbs'][] = $actionTitle;
        }

        return $this;
    }

    /**
     *
     * Строим метаданные на странице
     *
     * @param ActionEvent $e
     * @return $this
     */
    protected function _renderMetadata(ActionEvent $e)
    {
        $actionTitle = Inflector::humanize($e->action->id);
        if ($currentAction = $this->_getActionFromEvent($e))
        {
            $actionTitle = $currentAction->label;
        }

        if ($this->defaultAction != $e->action->id)
        {
            $this->getView()->title = $actionTitle . " / " . $this->_label;
        } else
        {
            $this->getView()->title = $this->_label;
        }

        return $this;
    }





}