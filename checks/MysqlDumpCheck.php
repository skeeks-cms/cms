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
 * Class MysqlDumpCheck
 * @package skeeks\cms\checks
 */
class MysqlDumpCheck extends CheckComponent
{
    public $installDir = "";

    public function init()
    {
        $this->installDir = ROOT_DIR . "/install";


        $this->name             = "Проверка наличия mysqldump";
        $this->description      = <<<HTML
<p>
    Для корректной работы обновлений требуется наличие mysqldump
</p>
HTML;
;
        $this->errorText    = "Есть ошибки";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		if (!file_exists("/usr/bin/mysqldump"))
        {
            $this->addError('На сервере не установлен mysqldump');
        } else
        {}
    }

}
