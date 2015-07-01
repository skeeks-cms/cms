<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (—кик—)
 * @date 30.05.2015
 */
namespace skeeks\cms\actions\user;

use skeeks\cms\controllers\ProfileController;
use skeeks\cms\controllers\UserController;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\traits\ActionTrait;
use yii\base\InvalidParamException;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * @property UserController    $controller
 *
 * Class AdminViewAction
 * @package skeeks\cms\modules\admin\actions
 */
class UserAction extends ViewAction
{
    use ActionTrait;

    /**
     * @var string Ќе используем prifix по умолчанию.
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
        //≈сли название не задано, покажем что нибудь.
        if (!$this->name)
        {
            $this->name = Inflector::humanize($this->id);
        }

        if (!$this->controller instanceof UserController)
        {
            throw new InvalidParamException('Ёто действие рассчитано дл€ работы с контроллером: ' . UserController::className());
        }

        $this->defaultView = $this->id;

        if ($this->viewName)
        {
            $this->defaultView = $this->viewName;
        }

        parent::init();
    }


    /**
     * @param $username
     * @return mixed|string
     * @throws InvalidConfigException
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($username)
    {
        $this->initUser($username);

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
     * @param $username
     * @return $this
     * @throws \yii\db\Exception
     */
    public function initUser($username)
    {
        if ($this->controller->user === null)
        {
            $this->controller->user = \Yii::$app->cms->findUser()->where(["username" => $username])->one();
        }

        return $this;
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
            'action' => $this,
            'model' => $this->controller->user
        ]);

        return $this->controller->render($viewName, (array) $this->viewParams);
    }

}