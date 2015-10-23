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

use skeeks\sx\validate\Validate;
use skeeks\sx\validators\Validator;
use yii\base\Behavior;
use yii\base\Component;
use yii\db\ActiveRecord;

class IsSame
    extends Validator
{
    protected $_activeRecordForComparison = null;

    public function __construct($activeRecordForComparison)
    {
        $this->_activeRecordForComparison = $activeRecordForComparison;
    }

    /**
     * @param ActiveRecord $activeRecord
     * @return \skeeks\sx\validate\Result
     */
    public function validate($activeRecord)
    {

        if (Validate::validate(new NotNewRecord(), $activeRecord)->isValid() && Validate::validate(new NotNewRecord(), $this->_activeRecordForComparison)->isValid())
        {
            if ($activeRecord->primaryKey == $this->_activeRecordForComparison->primaryKey)
            {
                return $this->_ok();
            }
        }

        return $this->_bad(\Yii::t('app','Essence different'));
    }


}