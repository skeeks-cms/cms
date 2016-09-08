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
class MailBigCheck extends MailCheck
{

    public function init()
    {
        parent::init();
        $this->name             = \Yii::t('skeeks/cms',"Sending e-mail messages larger than 64 KB (function {mail})",['mail' => 'mail']);

    }


    public function run()
    {
		$str = file_get_contents(__FILE__);
        if (!$str)
        {
            $this->addError(\Yii::t('skeeks/cms','Unable to retrieve the contents of the file').": " . __FILE__);
        }

        $body = str_repeat($str, 10);

		list($usec0, $sec0) = explode(" ", microtime());
		$val = mail("hosting_test@skeeks.com", "Skeeks site checker".($this->GetMailEOL() . "\tmultiline subject"), $body, ('BCC: noreply@skeeks.com'."\r\n"));
		list($usec1, $sec1) = explode(" ", microtime());
		$time = round($sec1 + $usec1 - $sec0 - $usec0, 2);
		if ($val)
		{
			if ($time > 1)
            {
                $this->addError(\Yii::t('skeeks/cms','Sent. Dispatch time: {s} sec.',['s' => $time]));
            } else
            {
                $this->addSuccess(\Yii::t('skeeks/cms','Sent. Dispatch time: {s} sec.',['s' => $time]));
            }
		}
		else
        {
            $this->addError(\Yii::t('skeeks/cms',"The letter has not been sent."));
        }

		return true;
    }

    function GetMailEOL()
	{
		static $eol = false;
		if($eol!==false)
			return $eol;

		if(strtoupper(substr(PHP_OS,0,3)=='WIN'))
			$eol="\r\n";
		elseif(strtoupper(substr(PHP_OS,0,3)!='MAC'))
			$eol="\n"; 	 //unix
		else
			$eol="\r";

		return $eol;
	}

}
