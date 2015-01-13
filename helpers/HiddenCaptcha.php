<?php
/**
 * HiddenCaptcha
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 13.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\helpers;

use skeeks\sx\String;
use yii\base\Component;
use yii\base\Object;

class HiddenCaptcha extends Component
{
    private $_captchaSource = null;

    /**
     * @param string $captchaSource
     */
    public function __construct($captchaSource = '')
    {
        $this->_captchaSource = (string) $captchaSource;
    }

    /**
     * @param string $captcha2
     * @return bool
     */
    public function verify($captcha2 = '')
    {
        return (bool) ($this->getVerifyedCaptcha() == $captcha2);
    }

    /**
     * @return null|string
     */
    public function getVerifyedCaptcha()
    {
        $captcha = $this->_captchaSource;
        $captcha = String::substr($this->_captchaSource, 4, 5);
        $captcha = $captcha . String::substr($this->_captchaSource, 1, 4);
        $captcha = $captcha . String::substr($this->_captchaSource, 1, 2);
        $captcha = $captcha . String::substr($this->_captchaSource, 5, 6);

        return $captcha;
    }

}