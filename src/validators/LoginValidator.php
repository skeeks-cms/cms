<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.09.2015
 */

namespace skeeks\cms\validators;

use yii\validators\Validator;
use Exception;

/**
 * Class LoginValidator
 * @package skeeks\cms\validators
 */
class LoginValidator extends Validator
{
    public function validateAttribute($model, $attribute)
    {
        $string = $model->{$attribute};

        if (!preg_match('/^[a-z]{1}[a-z0-9_]+$/', $string)) {
            $this->addError($model, $attribute, \Yii::t('skeeks/cms',
                'Use only letters (lowercase) and numbers. Must begin with a letter. Example {sample}',
                ['sample' => 'demo1']));
            return false;
        }
    }
}