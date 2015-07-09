<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 26.04.2015
 */
namespace skeeks\cms\checks;
use skeeks\cms\base\CheckComponent;

/**
 * Class ConfigCheck
 * @package skeeks\cms\checks
 */
class InstallScriptCheck extends CheckComponent
{
    public $installDir = "";

    public function init()
    {
        $this->installDir = ROOT_DIR . "/install";


        $this->name             = "Проверка наличия установочных скриптов";
        $this->description      = <<<HTML
<p>
    После установки проекта рекоммендуется удалить диррикторию с установочными скриптами.
    <code>{$this->installDir}</code>
</p>
HTML;
;
        $this->errorText    = "Есть ошибки";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		if (is_dir($this->installDir))
        {
            $this->addError('Необходимо удалить скрипт установщик');
        } else
        {}
    }

}
