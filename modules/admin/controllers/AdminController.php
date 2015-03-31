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
use skeeks\cms\modules\admin\filters\AccessRule;
use skeeks\cms\modules\admin\widgets\ControllerActions;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\validators\HasBehavior;
use skeeks\sx\validate\Validate;
use yii\base\ActionEvent;
use yii\base\InlineAction;
use yii\base\Model;
use yii\behaviors\BlameableBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
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
                'class'         => AccessControl::className(),
                'ruleConfig'    => ['class' => AccessRule::className()],

                'rules' =>
                [
                    [
                        'allow' => true,
                        'roles' => [CmsManager::PERMISSION_ADMIN_ACCESS],
                    ],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();

        $this->_ensure();
        $this->layout = \Yii::$app->cms->moduleAdmin()->layout;
        $this->on(self::EVENT_BEFORE_ACTION, [$this, "_beforeAction"]);
    }


    /*public function output($output)
    {
        return parent::render('@skeeks/cms/modules/admin/views/base-actions/_base-admin-actions', [
            'content' => $output,
        ]);
    }

    /**
     * @param string $view
     * @param array $params
     * @return string
     *
    public function render($view, $params = [])
    {
        return parent::render('@skeeks/cms/modules/admin/views/base-actions/_base-admin-actions', [
            'viewF' => $view,
            'paramsFile' => $params,
            'contextFile' => $this,
        ]);
    }*/


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
        if (!\Yii::$app->request->isAjax)
        {

            $this->_renderMetadata($e);
        }

        $this->_renderActions($e);
        $this->_renderBreadcrumbs($e);

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


    /**
     * @return \yii\web\Response
     */
    public function redirectRefresh()
    {
        return $this->redirect(UrlHelper::constructCurrent()->setRoute($this->action->id)->normalizeCurrentRoute()->enableAdmin()->toString());
    }



    /**
     * @param Model $model
     * @return $this
     */
    protected function _setLangAndSite(Model $model)
    {
        try
        {
            if ($site = \Yii::$app->cms->moduleAdmin()->getCurrentSite())
            {
                $model->setCurrentSite($site);
            } else
            {
                $model->setCurrentSite(null);
            }

            if ($lang = \Yii::$app->cms->moduleAdmin()->getCurrentLang())
            {
                $model->setCurrentLang($lang);
            } else
            {
                $model->setCurrentLang(null);
            }
        } catch (\Exception $e)
        {}

        return $this;
    }
}