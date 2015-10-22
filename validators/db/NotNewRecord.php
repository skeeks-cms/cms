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

class NotNewRecord
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
            return $this->_bad(\Yii::t('app',"Object: {class} must be inherited from: {parent}",['class' => $component->className(), 'parent' => ActiveRecord::className()]));
        }

        return !$component->isNewRecord ? $this->_ok() : $this->_bad(\Yii::t('app',"The object must already be saved"));
    }


}