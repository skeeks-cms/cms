<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */
namespace skeeks\cms\traits;
/**
 * Class ValidateRulesTrait
 * @package skeeks\cms\traits
 */
trait ValidateRulesTrait
{
    public function validateServerName($attribute)
    {
        if(!preg_match('/^[а-яa-z0-9.-]{2,255}$/', $this->$attribute))
        {
            $this->addError($attribute, \Yii::t('skeeks/cms','Use only lowercase letters and numbers. Example {site} (2-255 characters)',['site' => 'site.ru']));
        }
    }


    public function validateCode($attribute)
    {
        if(!preg_match('/^[a-zA-Z]{1}[a-zA-Z0-9-]{1,255}$/', $this->$attribute))
        {
            $this->addError($attribute, \Yii::t('skeeks/cms','Use only letters of the alphabet in lower or upper case and numbers, the first character of the letter (Example {code})',['code' => 'code1']));
        }
    }
}