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


        $this->name             = \Yii::t('app',"Check availability {git}",['git' => "git client"]);
        $txt = \Yii::t('app','To work correctly the update, requires a git client');
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
		if (!file_exists("/usr/bin/git"))
        {
            $this->addError(\Yii::t('app','The git client is not installed at the server'));
        } else
        {}
    }

}
