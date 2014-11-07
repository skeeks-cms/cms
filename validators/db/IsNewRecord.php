<?php
/**
 * NewRecord
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 07.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\validators\db;

use skeeks\sx\validators\Validator;
use yii\base\Behavior;
use yii\base\Component;
use yii\db\ActiveRecord;

class IsNewRecord
    extends Validator
{
    /**
     * @param Component $component
     * @return \skeeks\sx\validate\Result
     */
    public function validate($component)
    {
        if (!$component instanceof ActiveRecord)
        {
            return $this->_bad("Объект: " . $component->className() . " должен быть наследован от: " . ActiveRecord::className());
        }

        return $component->isNewRecord ? $this->_ok() : $this->_bad("Объект должет быть еще не сохранен");
    }


}