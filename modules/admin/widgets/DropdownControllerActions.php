<?php
/**
 * DropdownControllerActions
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 07.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\modules\admin\widgets;

use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\cms\modules\admin\controllers\AdminController;
use skeeks\cms\modules\admin\controllers\helpers\Action;
use skeeks\cms\modules\admin\controllers\helpers\ActionManager;
use skeeks\cms\modules\admin\controllers\helpers\ActionModel;
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
class DropdownControllerActions
    extends ControllerActions
{
    public $ulOptions =
    [
        "class" => "dropdown-menu"
    ];

    public $containerClass = 'dropdown';

    public $renderFirstAction = true;

    /**
     * @return string
     */
    public function run()
    {
        $actions = $this->controller->actions;

        if ($actions && is_array($actions) && count($actions) >= 1)
        {
            $firstAction = array_shift($actions);
        }

        $style = '';
        $firstActionString = '';

        if ($firstAction && $this->renderFirstAction)
        {
            $actionDataJson = Json::encode($this->getActionData($firstAction));

            $tagOptions = [
                "onclick"   => "new sx.classes.app.controllerAction({$actionDataJson}).go(); return false;",
                "class"     => "btn btn-xs btn-default",
                "title"     => $firstAction->name
            ];

            $firstActionString = Html::a($this->getSpanIcon($firstAction), $this->getActionUrl($firstAction), $tagOptions);
            $style = 'min-width: 43px;';
        }


        return "<div class='{$this->containerClass}' title='Возможные действия'>
                    <div class=\"btn-group\" role=\"group\" style='{$style}'>
                        {$firstActionString}
                        <button type=\"button\" class='btn btn-xs btn-default' data-toggle=\"dropdown\">
                           <span class=\"caret\"></span>
                        </button>" .
                    parent::run()

                . "
                    </div>
                </div>
                ";
    }
}