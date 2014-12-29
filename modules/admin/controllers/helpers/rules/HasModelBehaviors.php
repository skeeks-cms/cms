<?php
/**
 * Дейсвтие сработает только когда у модели которая работает с контроллерам имеются нужне behaviors
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 02.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers\helpers\rules;
use skeeks\cms\base\db\ActiveRecord;
use skeeks\cms\validators\HasBehavior;
use skeeks\cms\validators\HasBehaviorsAnd;
use skeeks\cms\validators\HasBehaviorsOr;
use skeeks\sx\validate\Validate;

/**
 * Class Action
 * @package skeeks\cms\modules\admin\descriptors
 */
class HasModelBehaviors extends HasModel
{
    /**
     * @var array|string
     */
    public $behaviors   = null;
    /**
     * @var bool использовать условие ИЛИ (проверка наличия хотябы одного поведения)
     */
    public $useOr       = false;

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

        $model = $this->controller->getModel();
        $behaviors = $this->behaviors;

        if ($this->useOr)
        {
            return Validate::validate(new HasBehaviorsOr($behaviors), $model)->isValid();
        } else
        {
            return Validate::validate(new HasBehaviorsAnd($behaviors), $model)->isValid();
        }
    }
}