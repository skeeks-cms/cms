<?php
/**
 * HasModel
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers\rules;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\ActionRule;

/**
 * Class HasModel
 * @package skeeks\cms\modules\admin\controllers\helpers\rules
 */
class HasModel extends ActionRule
{
    /**
     * @var AdminModelEditorController
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

        if (!parent::isAllow())
        {
            return false;
        }

        if (!$this->controller instanceof AdminModelEditorController)
        {
            return false;
        }

        return true;
    }
}