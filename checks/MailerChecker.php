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
class MailerChecker extends CheckComponent
{
    public function init()
    {
        $this->name             = \Yii::t('app','Sending mail (through the object {obj})',['obj' => 'Mailer']);
        $txt1 = \Yii::t('app','The system is transmitting a test letter to the postal address {email} through the library {obj}.',['email' => 'hosting_test@skeeks.com', 'obj','Mailer']);
        $txt2 = \Yii::t('app','Created special mailbox, for maximality testing for real work.');
        $txt3 = \Yii::t('app','As a test message text is transferred the source code of the script checking site.');
        $txt4 = \Yii::t('app','No user data is not transmitted!');
        $txt5 = \Yii::t('app','Please note that the test does not check the delivery letter in the mailbox. Moreover, it is impossible to test the delivery of mail to other mail servers.');
        $txt6 = \Yii::t('app','If the time of sending the letter more than a second, it can significantly slow down the work site. Contact your hosting provider to set up a pending request to send mail (through the spooler), or turn on the transfer of mail (and the work of agents) through {cron}. To do this we must add the constant into {file}:',['cron' => 'cron', 'file' => 'dbconn.php']);
        $this->description      = <<<HTML
<p>
{$txt1}
{$txt2}
</p>
<p>
{$txt3}
</p>
<p>
<b>{$txt4}</b>
</p>
<p>
{$txt5}
</p>
<p>
{$txt6}
</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"Error");
        $this->successText  = \Yii::t('app',"Successfully");

        parent::init();
    }


    public function run()
    {

		$body = "Test message.\nDelete it.";

		list($usec0, $sec0) = explode(" ", microtime());
		$val = \Yii::$app->mailer
            ->compose("@skeeks/cms/mail/checker")
            ->setFrom([\Yii::$app->cms->adminEmail => \Yii::$app->cms->appName . ' robot'])
            ->setTo("hosting_test@skeeks.com")
            ->setSubject('Skeeks site checker ' . \Yii::$app->cms->appName)
            ->send();
        ;
		list($usec1, $sec1) = explode(" ", microtime());
		$time = round($sec1 + $usec1 - $sec0 - $usec0, 2);
		if ($val)
		{
			if ($time > 1)
            {
                $this->addError(\Yii::t('app','Sent. Dispatch time: {s} sec.',['s' => $time]));
            } else
            {
                $this->addSuccess(\Yii::t('app','Sent. Dispatch time: {s} sec.',['s' => $time]));
            }
		}
		else
        {
            $this->addError(\Yii::t('app',"The letter has not been sent."));
        }

		return true;
    }

}
