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
 * Class SecurityApacheCheck
 * @package skeeks\cms\checks
 */
class SecurityApacheCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = "Модули веб-сервера";
        $this->description      = <<<HTML
<p>
Модуль Apache mod_security подобно модулю php suhosin призван защищать сайт от атак хакеров, но на практике он чаще препятствует нормальной работе сайта.
Рекомендуется его отключить, вместо него использовать модуль проактивной защиты Skeeks CMS.
</p>
HTML;
;
        $this->errorText    = "Выявленные конфликты";
        $this->successText  = "Конфликтов не выявлено";

        parent::init();
    }


    public function run()
    {
		if (function_exists('apache_get_modules'))
		{
			$arLoaded = apache_get_modules();
			if (in_array('mod_security', $arLoaded))
            {
                $this->addError("Загружен модуль mod_security, возможны проблемы в работе административной части");
            }
			if (in_array('mod_dav', $arLoaded) || in_array('mod_dav_fs', $arLoaded))
            {
                $this->addError("Загружен модуль mod_dav/mod_dav_fs, WebDav не будет работать");
            }
		}
    }

}
