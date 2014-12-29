<?php
/**
 * AllowExtension
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.12.2014
 * @since 1.0.0
 */

namespace skeeks\cms\components\imaging\validators;


use skeeks\sx\String;
use skeeks\sx\validators\Validator;

class AllowExtension extends Validator
{
    /**
     * @return array
     */
    static public function getPossibleExtensions()
    {
        return \Yii::$app->imaging->extensions;
    }
    /**
     * Проверка валидности значения
     *
     * @param  mixed $value
     * @return Ix_Validate_Result
     */
    public function validate($extension)
    {
        return !in_array(String::strtolower($extension), self::getPossibleExtensions()) ? $this->_bad("Расширение '{$extension}' Не поддерживается данной библиотекой")
                                 : $this->_ok();
    }
}