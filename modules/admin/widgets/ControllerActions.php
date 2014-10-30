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

use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class ControllerActions
 * @package skeeks\cms\modules\admin\widgets
 */
class ControllerActions
    extends Widget
{
    /**
     * @var string id текущего действия
     */
    public $currentAction   = null;


    public $ulOptions = [
        "class" => "nav nav-pills sx-nav"
    ];

    /**
     * @var AdminController объект контроллера
     */
    public $controller      = null;

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
        $actions = $this->controller->getActions();
        if (!$actions)
        {
            return "";
        }

        $result = $this->renderListLi();
        return Html::tag("ul", implode($result), $this->ulOptions);
    }

    /**
     * @return array
     */
    public function renderListLi()
    {
        $actions = $this->controller->getActions();
        $result = [];

        foreach ($actions as $code => $data)
        {
            $label          = ArrayHelper::getValue($data, "label");

            $linkOptions["data-method"]         = ArrayHelper::getValue($data, "data-method");
            $linkOptions["data-confirm"]        = ArrayHelper::getValue($data, "data-method");

            $result[] = Html::tag("li",
                Html::a($label, [$code, UrlRule::ADMIN_PARAM_NAME => UrlRule::ADMIN_PARAM_VALUE], $linkOptions),
                ["class" => $this->currentAction == $code ? "active" : ""]
            );
        }

        return $result;
    }
}