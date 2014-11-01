<?php
/**
 * ModelHasBehaviors
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers\rules;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class HasModelBehaviors extends HasModel
{
    /**
     * @var array|string
     */
    public $behaviors = null;

    public function isAllow()
    {
        if (!parent::isAllow())
        {
            return false;
        }

        if (!$this->behaviors)
        {
            return true;
        }


        return true;
    }
}