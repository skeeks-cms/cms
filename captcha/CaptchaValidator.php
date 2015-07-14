<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.07.2015
 */
namespace skeeks\cms\captcha;
/**
 * Class CaptchaValidator
 * @package skeeks\cms\captcha
 */
class CaptchaValidator extends \yii\captcha\CaptchaValidator
{
    public $captchaAction       = '/cms/tools/captcha';
}
