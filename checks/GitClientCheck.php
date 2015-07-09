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
 * Class GitClientCheck
 * @package skeeks\cms\checks
 */
class GitClientCheck extends CheckComponent
{
    public $installDir = "";

    public function init()
    {
        $this->installDir = ROOT_DIR . "/install";


        $this->name             = "Проверка наличия git client";
        $this->description      = <<<HTML
<p>
    Для корректной работы обновлений требуется наличие git клиента
</p>
HTML;
;
        $this->errorText    = "Есть ошибки";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		if (!file_exists("/usr/bin/git"))
        {
            $this->addError('На сервере не установлен git клиент');
        } else
        {}
    }

}
