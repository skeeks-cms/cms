<?php
/**
 * ControllerActions
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\helpers\Action;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\controllers\helpers\ActionModel;
use skeeks\cms\modules\admin\widgets\controllerActions\Asset;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 */
class ControllerActions
    extends Widget
{
    /**
     * @var string id активного дейсвтичя
     */
    public $activeActionId          = null;

    public $isOpenNewWindow         = false;

    public $ulOptions               =
    [
        "class" => "nav nav-pills sx-nav"
    ];

    /**
     * @var AdminController объект контроллера
     */
    public $controller              = null;

    public $clientOptions           = [];

    public function init()
    {
        parent::init();
        $this->_ensure();
    }

    /**
     * Парочка проверок, для целостности
     * @throws InvalidConfigException
     */
    protected function _ensure()
    {
        if (!$this->controller)
        {
            throw new InvalidConfigException(\Yii::t('app',"Incorrectly configured widget, you must pass an controller object to which is built widget"));
        }

        if (!$this->controller instanceof AdminController)
        {
            throw new InvalidConfigException(\Yii::t('app',"For this controller can not build action"));
        }
    }


    /**
     * TODO: учитывать приоритет
     * @return string
     */
    public function run()
    {
        if (!$actions = $this->controller->actions())
        {
            return "";
        }

        $result = $this->renderListLi();

        Asset::register($this->getView());
        return Html::tag("ul", implode($result), $this->ulOptions);
    }

    /**
     * @return array
     */
    public function renderListLi()
    {
        $result = [];

        $actions = $this->controller->actions;

        if (!$actions)
        {
            return [];
        }

        foreach ($actions as $id => $action)
        {
            if (!$action->visible)
            {
                continue;
            }

            $tagA = $this->renderActionTagA($action);

            $actionDataJson = Json::encode($this->getActionData($action));
            $result[] = Html::tag("li", $tagA,
                [
                    "class" => $this->activeActionId == $action->id ? "active" : "",
                    "onclick" => "new sx.classes.app.controllerAction({$actionDataJson}).go(); return false;"
                ]
            );
        }

        return $result;
    }

    /**
     * @param AdminAction $action
     * @return array
     */
    public function getActionData($action)
    {
        $actionData = array_merge($this->clientOptions, [
            "url"               => (string) $this->getActionUrl($action),
            "isOpenNewWindow"   => $this->isOpenNewWindow,
            "confirm"           => $action->confirm,
            "method"            => $action->method,
            "request"           => $action->request,
        ]);

        return $actionData;
    }

    /**
     * @param AdminAction $action
     */
    public function renderActionTagA($action, $tagOptions = [])
    {
        if (!$action->visible)
        {
            return "";
        }

        $icon = '';
        if ($action->icon)
        {
            $icon = Html::tag('span', '', ['class' => $action->icon]);
        }

        return Html::a($icon . '  ' . $action->name, $this->getActionUrl($action), $tagOptions);
    }

    /**
     * @param AdminAction $action
     * @return string
     */
    public function getSpanIcon($action)
    {
        $icon = '';
        if ($action->icon)
        {
            $icon = Html::tag('span', '', ['class' => $action->icon]);
        }

        return $icon;
    }
    /**
     * @param AdminAction $action
     * @return UrlHelper
     */
    public function getActionUrl($action)
    {
        $url = $action->url;
        if ($this->isOpenNewWindow)
        {
            $url->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true');
        }

        return $url;
    }
}