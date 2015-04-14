<?php
/**
 * LoginValidator
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 26.02.2015
 * @since 1.0.0
 */
namespace skeeks\cms\validators\string;

use common\models\User;
use skeeks\cms\models\user\UserEmail;
use skeeks\sx\validate\Validate;
use skeeks\sx\validators\Validator;

/**
 * Class UserEmailValidator
 * @package skeeks\cms\validators\user
 */
class LoginValidator
    extends Validator
{
    /**
     * @param mixed $string
     * @return \skeeks\sx\validate\Result
     */
    public function validate($string)
    {
        if (!preg_match('/^[a-z]{1}[a-z0-9]{2,11}$/', $string))
        {
            return $this->_bad('Используйте только буквы латинского алфавита и цифры. Начинаться должен с буквы. Пример demo1.');
        }

        return $this->_ok();
    }


}