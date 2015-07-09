<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.06.2015
 */
namespace skeeks\cms\checks;
use skeeks\cms\base\CheckComponent;
use skeeks\cms\console\controllers\CheckController;

/**
 * Class EmptyConsoleCheck
 * @package skeeks\cms\checks
 */
class EmptyConsoleCheck extends CheckComponent
{
    public function init()
    {
        $this->name             = "Проверка php warning и notice в console";
        $this->description      = <<<HTML
<p>
Осуществляется проверка консольных команд. Проверка php warning и notice в console.
</p>
HTML;
;
        $this->errorText    = "Есть ошибки";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		ob_start();
            system('cd '  . ROOT_DIR . '; php yii cms/check/empty-console');
        $result = ob_get_clean();
        $result = trim($result);

        if ($result == CheckController::EMPTY_CONSOLE_TEXT)
        {
            //$this->addSuccess();
        } else
        {
            $this->addError('Лишний текст в консоль.' . $result);
        }
    }

}
