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
            throw new InvalidConfigException("Некорректно сконфигурирован виджет, необходимо передать объект контроллера для которого сроится виджет");
        }

        if (!$this->controller instanceof AdminController)
        {
            throw new InvalidConfigException("У данного контроллера нельзя построить действия");
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

        $result = $this->renderListLi($actions);

        Asset::register($this->getView());
        return Html::tag("ul", implode($result), $this->ulOptions);
    }

    /**
     * @param array $actions
     * @param ActionManager $actionManager
     * @return array
     */
    public function renderListLi($actions = [])
    {
        $result = [];

        $actions = $this->controller->actions;

        foreach ($actions as $id => $action)
        {
            $linkOptions = [];

            $url = $action->url;
            if ($this->isOpenNewWindow)
            {
                $url->setSystemParam(\skeeks\cms\modules\admin\Module::SYSTEM_QUERY_EMPTY_LAYOUT, 'true');
            }


            $actionData = array_merge($this->clientOptions, [
                "url"               => (string) $url,
                "isOpenNewWindow"   => $this->isOpenNewWindow,
                "confirm"           => $action->confirm,
                //"method"            => $action->method,
                //"request"           => $action->request,
            ]);

            $icon = '';
            if ($action->icon)
            {
                $icon = Html::tag('span', '', ['class' => $action->icon]);
            }

            $actionDataJson = Json::encode($actionData);
            $result[] = Html::tag("li",
                Html::a($icon . '  ' . $action->name, $action->getUrl(), $linkOptions),
                [
                    "class" => $this->activeActionId == $action->id ? "active" : "",
                    "onclick" => "new sx.classes.app.controllerAction({$actionDataJson}).go(); return false;"
                ]
            );
        }

        return $result;
    }
}