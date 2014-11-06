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

    /**
     * @return string
     */
    public function run()
    {
        return "<div class='dropdown'>
            <button type=\"button\" class='btn btn-xs' data-toggle=\"dropdown\">
               <span class=\"caret\"></span>
            </button>" .
            parent::run()
        . "</div>";
    }
}