<?php
/**
 * Проверка наличия нескольких поведений у компонента
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 07.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\validators;

use skeeks\sx\validate\Validate;
use skeeks\sx\validators\Validator;
use yii\base\Component;

class HasBehaviorsAnd
    extends Validator
{
    /**
     * @var array
     */
    protected $_behaviors = null;

    /**
     * @param array|string $behaviors
     */
    public function __construct($behaviors)
    {
        if (is_string($behaviors))
        {
            $behaviors = [$behaviors];
        }

        $this->_behaviors = (array) $behaviors;
    }

    /**
     * @param Component $component
     * @return \skeeks\sx\validate\Result
     */
    public function validate($component)
    {
        foreach ($this->_behaviors as $behaviorName)
        {
            $validate = Validate::validate(new HasBehavior($behaviorName), $component);
            if ($validate->isInvalid())
            {
                return $this->_bad($validate->getErrorMessage());
            }
        }

        return $this->_ok();
    }
}