<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.09.2015
 */
namespace skeeks\cms\actions\user;

use skeeks\cms\controllers\ProfileController;
use skeeks\cms\controllers\UserController;
use skeeks\cms\filters\CmsAccessControl;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\traits\ActionTrait;
use yii\base\Action;
use yii\base\InvalidParamException;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ViewAction;
use Yii;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * @property UserController    $controller
 *
 * Class AdminViewAction
 * @package skeeks\cms\modules\admin\actions
 */
class UserAction extends Action
{
    use ActionTrait;

    /**
     * @var string the name of the GET parameter that contains the requested view name.
     */
    public $viewParam = '';
    /**
     * @var string the name of the default view when [[\yii\web\ViewAction::$viewParam]] GET parameter is not provided
     * by user. Defaults to 'index'. This should be in the format of 'path/to/view', similar to that given in the
     * GET parameter.
     * @see \yii\web\ViewAction::$viewPrefix
     */
    public $defaultView = '';
    /**
     * @var string a string to be prefixed to the user-specified view name to form a complete view name.
     * For example, if a user requests for `tutorial/chap1`, the corresponding view name will
     * be `pages/tutorial/chap1`, assuming the prefix is `pages`.
     * The actual view file is determined by [[\yii\base\View::findViewFile()]].
     * @see \yii\base\View::findViewFile()
     */
    public $viewPrefix = '';
    /**
     * @var mixed the name of the layout to be applied to the requested view.
     * This will be assigned to [[\yii\base\Controller::$layout]] before the view is rendered.
     * Defaults to null, meaning the controller's layout will be used.
     * If false, no layout will be applied.
     */
    public $layout;




    /**
     * @var array
     */
    public $viewParams  = [];

    /**
     * @var
     */
    public $viewName    = null;


    /**
     * @var null
     */
    public $isPublic    = false;


    /**
     * @var
     */
    public $callback;



    public function init()
    {
        if (!$this->name)
        {
            $this->name = Inflector::humanize($this->id);
        }

        if (!$this->controller instanceof UserController)
        {
            throw new InvalidParamException(\Yii::t('app','This action is designed to work with: {controller}',['controller' => UserController::className()]));
        }

        $this->defaultView = $this->id;

        if ($this->viewName)
        {
            $this->defaultView = $this->viewName;
        }

        parent::init();
    }


    /**
     * Renders a view
     *
     * @param string $viewName view name
     * @return string result of the rendering
     */
    protected function render($viewName)
    {
        return $this->controller->render($viewName, $this->controller->getViewParams());
    }




    /**
     * Runs the action.
     * This method displays the view requested by the user.
     * @throws NotFoundHttpException if the view file cannot be found
     */
    public function run($username)
    {
        $this->controller->initUser($username);

        if (!$this->isPublic && $this->controller->user->id != \Yii::$app->user->id)
        {
            throw new ForbiddenHttpException;
        }

        $viewName = $this->resolveViewName();

        $controllerLayout = null;
        if ($this->layout !== null) {
            $controllerLayout = $this->controller->layout;
            $this->controller->layout = $this->layout;
        }

        try {
            $output = $this->render($viewName);

            if ($controllerLayout) {
                $this->controller->layout = $controllerLayout;
            }

        } catch (InvalidParamException $e) {

            if ($controllerLayout) {
                $this->controller->layout = $controllerLayout;
            }

            if (YII_DEBUG) {
                throw new NotFoundHttpException($e->getMessage());
            } else {
                throw new NotFoundHttpException(
                    \Yii::t('yii', 'The requested view "{name}" was not found.', ['name' => $viewName])
                );
            }
        }

        return $output;
    }



    /**
     * Resolves the view name currently being requested.
     *
     * @return string the resolved view name
     * @throws NotFoundHttpException if the specified view name is invalid
     */
    protected function resolveViewName()
    {
        $viewName = \Yii::$app->request->get($this->viewParam, $this->defaultView);

        if (!is_string($viewName) || !preg_match('~^\w(?:(?!\/\.{0,2}\/)[\w\/\-\.])*$~', $viewName)) {
            if (YII_DEBUG) {
                throw new NotFoundHttpException(\Yii::t('app',"The requested view \"{viewName}\" must start with a word character, must not contain /../ or /./, can contain only word characters, forward slashes, dots and dashes.",['viewname' => $viewName]));
            } else {
                throw new NotFoundHttpException(\Yii::t('yii', 'The requested view "{name}" was not found.', ['name' => $viewName]));
            }
        }

        return empty($this->viewPrefix) ? $viewName : $this->viewPrefix . '/' . $viewName;
    }

}