<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use yii\base\InvalidParamException;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * @property UrlHelper          $url
 * @property AdminController    $controller
 *
 * Class AdminViewAction
 * @package skeeks\cms\modules\admin\actions
 */
class AdminAction extends ViewAction
{
    use AdminActionTrait;

    /**
     * @var string Не используем prifix по умолчанию.
     */
    public $viewPrefix = '';

    /**
     * @var array параметры которые будут переданы в шаблон
     */
    public $viewParams  = [];

    /**
     * @var
     */
    public $viewName    = null;


    /**
     * @var
     */
    public $callback;

    public function init()
    {
        //Если название не задано, покажем что нибудь.
        if (!$this->name)
        {
            $this->name = Inflector::humanize($this->id);
        }

        if (!$this->controller instanceof AdminController)
        {
            throw new InvalidParamException('Это действие рассчитано для работы с контроллером: ' . AdminController::className());
        }

        $this->defaultView = $this->id;

        if ($this->viewName)
        {
            $this->defaultView = $this->viewName;
        }

        parent::init();
    }


    /**
     * @return mixed|string
     * @throws InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run()
    {
        if ($this->callback)
        {
            if (!is_callable($this->callback))
            {
                throw new InvalidConfigException('"' . get_class($this) . '::callback" should be a valid callback.');
            }

            return call_user_func($this->callback, $this);
        }

        return parent::run();
    }

    /**
     * Renders a view
     *
     * @param string $viewName view name
     * @return string result of the rendering
     */
    protected function render($viewName)
    {
        return $this->controller->render($viewName, (array) $this->viewParams);
    }

    /**
     * @return bool
     */
    protected function beforeRun()
    {
        if (parent::beforeRun())
        {
            $this->_initBreadcrumbsData();
            $this->_initActionsData();
            $this->_initMetadata();

            return true;
        } else
        {
            return false;
        }
    }

    /**
     * Формируем данные для хлебных крошек.
     * Эти данные в layout - е будут передаваться в нужный виджет.
     *
     * @return $this
     */
    protected function _initBreadcrumbsData()
    {
        if ($this->controller->name)
        {
            $this->controller->view->params['breadcrumbs'][] = [
                'label' => $this->controller->name,
                'url' =>
                [
                    $this->controller->defaultAction,
                    UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE
                ]
            ];
        }

        if (count($this->controller->actions) > 1)
        {
            $this->controller->view->params['breadcrumbs'][] = $this->name;
        }

        return $this;
    }


    /**
     * Рендер действий текущего контроллера
     * Сразу запускаем нужный виджет и формируем готовый html
     *
     * @return $this
     */
    protected function _initActionsData()
    {
        if (count($this->controller->actions) > 1)
        {
            $this->controller->view->params["actions"] = ControllerActions::begin([
                "activeActionId"        => $this->id,
                "controller"            => $this->controller,
            ])->run();
        }

        return $this;
    }

    /**
     * Инициализация данных для заголовков страницы
     *
     * @return $this
     */
    protected function _initMetadata()
    {
        $this->controller->view->title = $this->name . " / " . $this->controller->name;
        return $this;
    }


    /**
     * @return UrlHelper
     */
    public function getUrl()
    {
        if ($this->controller->module instanceof Application)
        {
            $route = $this->controller->id . '/' . $this->id;
        } else
        {
            $route = $this->controller->module->id . '/' . $this->controller->id . '/' . $this->id;
        }

        $url = UrlHelper::constructCurrent()->setRoute($route)->enableAdmin()->setCurrentRef();

        return $url;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }
}