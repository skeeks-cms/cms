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
 * Class StatusVendorsCheck
 * @package skeeks\cms\checks
 */
class StatusVendorsCheck extends CheckComponent
{
    public function init()
    {
        $vendorDir              = VENDOR_DIR;

        $this->name             = \Yii::t('app',"Checking kernel and libraries modification");
        $txt1 = \Yii::t('app','Checks, changes kernel {cms} and third-party libraries (Folder {folder}). Folder location and the name given by the global constant VENDOR_DIR. For the current project:',['cms' => 'cms', 'folder' => '/vendor']);
        $txt2 = \Yii::t('app','We strongly not recommend to modify the core of the project, as it can bring to the update failed, or your modifications will be removed during the upgrade process. That in turn may result in errors of work the project.');
        $txt3 = \Yii::t('app','To solve the problem, you can run the command in the console');
        $this->description      = <<<HTML
<p>
{$txt1}
</p>
<p>
<code>{$vendorDir}</code>
</p>
<p>
{$txt2}
</p>
<p>{$txt3}:</p>
<p><code>php yii cms/composer/revert-modified-files</code></p>
HTML;
;
        $this->errorText    = \Yii::t('app',"Found modified kernel");
        $this->successText  = \Yii::t('app',"The kernel has not been modified");

        parent::init();
    }


    public function run()
    {
		$emptyCheck = new EmptyConsoleCheck();
        $emptyCheck->run();

        if (!$emptyCheck->isSuccess())
        {
            $this->addError(\Yii::t('app','Found an error in the process of console commands, check the kernel modification can not be started.'));
            //return;
        }

        $result = \Yii::$app->console->execute('cd '  . ROOT_DIR . '; COMPOSER_HOME=.composer php composer.phar status --verbose');

        /*ob_start();
            system('cd '  . ROOT_DIR . '; COMPOSER_HOME=.composer php composer.phar status --verbose');
        $result = ob_get_clean();
        $result = trim($result);*/

        if ($result)
        {
            $this->addError(\Yii::t('app','Found modified kernel').': ' . <<<HTML
<pre><code>$result</code></pre>
HTML
);
        }

    }

}
