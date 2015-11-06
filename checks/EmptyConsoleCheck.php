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
        $this->name             = \Yii::t('app',"Check {php} and {notice} in the {console}",['php' => "php warning", 'notice' => "notice", 'console' => "console"]);
        $txt = \Yii::t('app','Checks console commands.').' '.\Yii::t('app',"Check {php} and {notice} in the {console}",['php' => "php warning", 'notice' => "notice", 'console' => "console"]);
        $this->description      = <<<HTML
<p>
{$txt}
</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"There are mistakes");
        $this->successText  = \Yii::t('app',"Successfully");

        parent::init();
    }


    public function run()
    {
        $result = \Yii::$app->console->execute('cd '  . ROOT_DIR . '; php yii cms/check/empty-console');

        if ($result == CheckController::EMPTY_CONSOLE_TEXT)
        {
            //$this->addSuccess();
        } else
        {
            $this->addError(\Yii::t('app','Excess text into console.') . $result);
        }
    }

}
