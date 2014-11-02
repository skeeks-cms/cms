<?php
/**
 * Дейсвтие не связано с моделью
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 01.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers\rules;
use skeeks\cms\Exception;
use skeeks\cms\modules\admin\controllers\AdminModelEditorController;
use skeeks\cms\modules\admin\controllers\helpers\ActionRule;

/**
 * Class HasModel
 * @package skeeks\cms\modules\admin\controllers\helpers\rules
 */
class NoModel extends ActionRule
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

        if ($model = $this->controller->getCurrentModel())
        {
            return false;
        }

        return true;
    }
}