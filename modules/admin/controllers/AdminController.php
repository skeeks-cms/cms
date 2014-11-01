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
use skeeks\cms\Controller;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\descriptors\Action;
use skeeks\cms\modules\admin\descriptors\ActionManager;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use yii\base\ActionEvent;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\filters\AccessControl;

/**
 * Class AdminController
 * @package skeeks\cms\modules\admin\controllers
 */
abstract class AdminController extends Controller
{
    /**
     * @var string
     */
    protected $_defaultAction = "index";
    /**
     * @var string понятное название контроллера, будет добавлено в хлебные крошки и title страницы
     */
    protected $_label = null;



    /**
     * Описанные действия будут отображаться в виде дополнительного меню
     * @var ActionManager
     */
    protected $_actionManager = null;


    public function behaviors()
    {
        return
        [
            'access' =>
            [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        $this->_actionManager = new ActionManager();
        $this->_ensure();
        $this->layout = App::moduleAdmin()->layout;
        $this->on(self::EVENT_BEFORE_ACTION, [$this, "_beforeAction"]);
    }

    /**
     * Проверка целостности данных для работы контроллера
     */
    protected function _ensure()
    {}


    /**
     * @param string $code
     * @param array $data
     * @return $this
     */
    protected function _addAction($code, array $data = [])
    {
        $this->_actionManager->createAction($code, $data);
        return $this;
    }

    /**
     * @return ActionManager
     */
    public function actionManager()
    {
        return $this->_actionManager;
    }


    /**
     * @param ActionEvent $e
     */
    protected function _beforeAction(ActionEvent $e)
    {
        $this->_renderActions($e);
        $this->_renderBreadcrumbs($e);
        $this->_renderMetadata($e);
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
            "currentAction" => $e->action->id,
            "controller"    => $this,
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

        if (isset($this->_actions[$e->action->id]))
        {
            $data = $this->_actions[$e->action->id];
            $actionTitle = ArrayHelper::getValue($data, "label");
        }

        if ($this->_label)
        {
            $this->getView()->params['breadcrumbs'][] = ['label' => $this->_label, 'url' => [
                'index',
                UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
            ]];
        }

        if ($this->_defaultAction != $e->action->id)
        {
            $this->getView()->params['breadcrumbs'][] = $actionTitle;
        }

        return $this;
    }

    /**
     * @param ActionEvent $e
     * @return $this
     */
    protected function _renderMetadata(ActionEvent $e)
    {
        $actionTitle = Inflector::humanize($e->action->id);

        if (isset($this->_actions[$e->action->id]))
        {
            $data = $this->_actions[$e->action->id];
            $actionTitle = ArrayHelper::getValue($data, "label", "Label");
        }

        if ($this->_defaultAction != $e->action->id)
        {
            $this->getView()->title = $actionTitle . " / " . $this->_label;
        } else
        {
            $this->getView()->title = $this->_label;
        }


        return $this;
    }
}