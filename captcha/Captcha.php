<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 14.07.2015
 */
namespace skeeks\cms\captcha;
/**
 * Class Captcha
 * @package skeeks\cms
 */
class Captcha extends \yii\captcha\Captcha
{
    public $captchaAction       = '/cms/tools/captcha';
    public $template            = '<div class="row sx-captcha-wrapper"><div class="col-lg-3">{image}</div><div class="col-lg-6">{input}</div></div>';
}
