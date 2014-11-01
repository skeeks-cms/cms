<?php
/**
 * Action
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers;
use skeeks\cms\modules\admin\controllers\AdminController;
use yii\base\Component;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
abstract class ActionRule extends Component
{
    /**
     * @var AdminController
     */
    public $controller = null;

    public function init()
    {
        parent::init();
    }

    /**
     * @return bool
     */
    public function isAllow()
    {
        if (!$this->controller instanceof AdminController)
        {
            return false;
        }

        return true;
    }
}