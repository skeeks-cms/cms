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
            throw new InvalidParamException( \Yii::t('app','This action is designed to work with the controller: ') . AdminController::className());
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
            return $this->runCallback();
        }

        return parent::run();
    }

    public function runCallback()
    {
        if ($this->callback)
        {
            if (!is_callable($this->callback))
            {
                throw new InvalidConfigException('"' . get_class($this) . '::callback" '.\Yii::t('app','should be a valid callback.'));
            }

            return call_user_func($this->callback, $this);
        }
    }

    /**
     * Renders a view
     *
     * @param string $viewName view name
     * @return string result of the rendering
     */
    protected function render($viewName)
    {
        $this->viewParams = array_merge($this->viewParams, [
            'action' => $this
        ]);

        return $this->controller->render($viewName, (array) $this->viewParams);
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