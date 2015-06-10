<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.05.2015
 */
namespace skeeks\cms\modules\admin\actions\modelEditor;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Search;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\filters\AdminAccessControl;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\authclient\AuthAction;
use yii\behaviors\BlameableBehavior;
use yii\helpers\Inflector;
use yii\web\Application;
use yii\web\ViewAction;
use \skeeks\cms\modules\admin\controllers\AdminController;

/**
 * @property AdminModelEditorController    $controller
 *
 * Class AdminModelsGridAction
 * @package skeeks\cms\modules\admin\actions
 */
class AdminOneModelEditAction extends AdminModelEditorAction
{

    public function init()
    {
        parent::init();

        //Для работы с любой моделью нужно как минимум иметь привилегию CmsManager::PERMISSION_ALLOW_MODEL_UPDATE
        $this->controller->attachBehavior('accessCreate',
        [
            'class'         => AdminAccessControl::className(),
            'only'          => [$this->id],
            'rules'         =>
            [
                [
                    'allow'         => true,
                    'matchCallback' => [$this, 'checkUpdateAccess']
                ],
            ],
        ]);
    }

    /**
     * Инициализация данных для заголовков страницы
     *
     * @return $this
     */
    protected function _initMetadata()
    {
        $this->controller->view->title = $this->controller->model->{$this->controller->modelShowAttribute} . " / " . $this->name . " / " . $this->controller->name;
        return $this;
    }

    /**
     * Формируем данные для хлебных крошек.
     * Эти данные в layout - е будут передаваться в нужный виджет.
     *
     * @return $this
     */
    protected function _initBreadcrumbsData()
    {
        $baseRoute = $this->controller->module instanceof Application ? $this->controller->id : ("/" . $this->controller->module->id . "/" . $this->controller->id);

        if ($this->controller->name)
        {
            $this->controller->view->params['breadcrumbs'][] = [
                'label' => $this->controller->name,
                'url' => $this->controller->getIndexUrl()
            ];
        }

        if ($this->controller->model)
        {
            $this->controller->view->params['breadcrumbs'][] = [
                'label' => $this->controller->model->{$this->controller->modelShowAttribute},
                'url' => UrlHelper::constructCurrent()->setRoute($baseRoute . '/' . $this->controller->modelDefaultAction)->set(
                    $this->controller->requestPkParamName, $this->controller->model->{$this->controller->modelPkAttribute}
                )->enableAdmin()->normalizeCurrentRoute()->toString()
            ];
        }


        if (count($this->controller->actions) > 1)
        {
            $this->controller->view->params['breadcrumbs'][] = $this->name;
        }

        return $this;
    }


    /**
     * @return UrlHelper
     */
    public function getUrl()
    {
        $url = parent::getUrl();
        $url->set($this->controller->requestPkParamName, $this->controller->model->{$this->controller->modelPkAttribute});
        return $url;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        if (!parent::isVisible())
        {
            return false;
        }

        return $this->checkUpdateAccess();
    }

    public function checkUpdateAccess()
    {
        if (Validate::validate(new HasBehavior(BlameableBehavior::className()), $this->controller->model)->isValid())
        {
            //Если такая привилегия заведена, нужно ее проверять.
            if ($permission = \Yii::$app->authManager->getPermission(CmsManager::PERMISSION_ALLOW_MODEL_UPDATE))
            {
                if (!\Yii::$app->user->can($permission->name, [
                    'model' => $this->controller->model
                ]))
                {
                    return false;
                }
            }
        } else
        {
            //Если такая привилегия заведена, нужно ее проверять.
            if ($permission = \Yii::$app->authManager->getPermission(CmsManager::PERMISSION_ALLOW_MODEL_UPDATE))
            {
                if (!\Yii::$app->user->can($permission->name))
                {
                    return false;
                }
            }
        }

        return true;
    }
}