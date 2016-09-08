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
 * Class SessionCheck
 * @package skeeks\cms\checks
 */
class SessionCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = \Yii::t('skeeks/cms',"Saved sessions");
        $txt1 = \Yii::t('skeeks/cms','Checking the ability to store data on the server using the session mechanism. This basic ability necessary to preserve authorization between hits.');
        $txt2 = \Yii::t('skeeks/cms','Sessions may not work if their support is not installed, in php.ini contains the incorrect folder to store the sessions or it is not available on the record.');
        $this->description      = <<<HTML
<p>
{$txt1}
</p>
<p>
{$txt2}
</p>
HTML;
;
        $this->errorText    = \Yii::t('skeeks/cms',"Error");
        $this->successText  = \Yii::t('skeeks/cms',"Successfully");

        parent::init();
    }


    public function run()
    {
		if (!$this->lastValue)
		{
			$_SESSION['CHECKER_CHECK_SESSION'] = 'SUCCESS';
			$this->ptc          = 50;
			$this->lastValue    = "Y";
		}
		else
		{
            $this->lastValue    = null;
            $this->ptc          = 100;

			if ($_SESSION['CHECKER_CHECK_SESSION'] != 'SUCCESS')
            {
                $this->addError(\Yii::t('skeeks/cms','Could not to keep the session'));
            }

			unset($_SESSION['CHECKER_CHECK_SESSION']);
		}
    }

}
