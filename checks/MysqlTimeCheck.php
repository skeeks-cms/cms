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
class MysqlTimeCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = \Yii::t('skeeks/cms',"Time at database and web server");
        $txt = \Yii::t('skeeks/cms','Compares the system time database and web server. It may be of unsync when they are installed on different physical machines, but more often as a result of improper installation time zone.');
        $this->description      = <<<HTML
<p>
{$txt}
</p>
HTML;
;
        $this->errorText    = \Yii::t('skeeks/cms',"Error");
        $this->successText  = \Yii::t('skeeks/cms',"Successfully");

        parent::init();
    }


    public function run()
    {
		$s = time();
		while($s == time());
		$s++;
        $founded = \Yii::$app->db->createCommand('SELECT NOW() AS A')->queryOne();
		if (($diff = abs($s - strtotime($founded['A']))) == 0)
        {
            return true;
        } else
        {
            $this->addError(\Yii::t('skeeks/cms',"Time is different for {diff} seconds",['diff' => $diff]));
        }
    }

}
