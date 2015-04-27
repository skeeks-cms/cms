<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
namespace skeeks\cms\checks;
use skeeks\cms\base\CheckComponent;
use skeeks\sx\String;

/**
 * Class ServerVarsCheck
 * @package skeeks\cms\checks
 */
class ServerVarsCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = "Значения переменных сервера";
        $this->description      = <<<HTML
<p>
Проверяются значения переменных, определяемых веб сервером.
</p>
<p>
Значение HTTP_HOST берется на основе имени текущего виртуального хоста (домена). Невалидный домен приводит к тому, что некоторые браузеры (например, Internet Explorer 6) отказываются сохранять для него cookie, как следствие - не сохраняется авторизация.
</p>
HTML;
;
        $this->errorText    = "Не корректные";
        $this->successText  = "Корректные";

        parent::init();
    }


    public function run()
    {
		list($host, $port) = explode(':',$_SERVER['HTTP_HOST']);
		if ($host != 'localhost' && !preg_match('#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$#',$host))
		{
			if (!preg_match('#^[a-z0-9\-\.]{2,192}\.(xn--)?[a-z0-9]{2,63}$#i', $host))
            {
                $val = htmlspecialchars($_SERVER['HTTP_HOST']);
                $this->addError("Текущий домен не валидный ({$val}). Может содержать только цифры, латинские буквы и дефис. Должен содержать точку.");
            }
		}
    }

}
