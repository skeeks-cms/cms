<?php
/**
 * Проверка наличия поведения у компонента
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 07.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\validators;

use skeeks\sx\validators\Validator;
use yii\base\Behavior;
use yii\base\Component;
use yii\base\Object;

class HasBehavior
    extends Validator
{
    /**
     * @var null|string|Behavior
     */
    protected $_behavior = null;

    /**
     * @param string|Behavior $behavior
     */
    public function __construct($behavior)
    {
        if ($behavior instanceof Behavior)
        {
            $this->_behavior = (string) $behavior->className();
        } else if (is_string($behavior))
        {
            $this->_behavior = (string) $behavior;
        }
    }

    /**
     * @param Component $component
     * @return \skeeks\sx\validate\Result
     */
    public function validate($component)
    {
        if (!$component instanceof Component)
        {
            if ($component instanceof Object)
            {
                return $this->_bad(\Yii::t('app',"Object: {class} must be inherited from: {parent}",['class' => $component->className(), 'parent' => ActiveRecord::className()]));
            } else
            {
                return $this->_bad(\Yii::t('app',"The object must be inherited from").": " . Component::className());
            }

        }

        if (!$this->_behavior)
        {
            return $this->_ok();
        }

        $hasBehaviors = $component->getBehaviors();

        foreach ($hasBehaviors as $behavior)
        {
            if ($behavior instanceof $this->_behavior)
            {
                return $this->_ok();
            }
        }

        return $this->_bad(\Yii::t('app',"At the component: {class} requires a behavior",['class' => $component->className()]).": " . $this->_behavior);
    }


}