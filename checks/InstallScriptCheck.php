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


        $this->name             = \Yii::t('app',"Checking availability installation scripts");
        $txt = \Yii::t('app','After installation it is recommended to remove the directory with installation script.');
        $this->description      = <<<HTML
<p>
    {$txt}
    <code>{$this->installDir}</code>
</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"There are mistakes");
        $this->successText  = \Yii::t('app',"Successfully");

        parent::init();
    }


    public function run()
    {
		if (is_dir($this->installDir))
        {
            $this->addError(\Yii::t('app','You must remove the installation script'));
        } else
        {}
    }

}
