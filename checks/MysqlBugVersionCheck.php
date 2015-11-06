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
class MysqlBugVersionCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = \Yii::t('app',"Version MySQL server");
        $txt1 = \Yii::t('app','Known versions of MySQL with errors that prevent normal operation of the site:');
        $txt2 = \Yii::t('app','incorrect method works {ex}, search does not work properly');
        $txt3 = \Yii::t('app','Step auto_increment default is 2, requires 1');
        $txt4 = \Yii::t('app','Update MySQL, if you have one of these versions.');
        $this->description      = <<<HTML
<p>
{$txt1}
</p>
<p><b>5.0.41</b> - {$txt2};</p>
<p><b>5.1.34</b> - {$txt3};</p>
<p>
{$txt4}
</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"Error");
        $this->successText  = \Yii::t('app',"Successfully");

        parent::init();
    }


    public function run()
    {
		$MySql_vercheck_min = "5.0.0";

		$command = \Yii::$app->db->createCommand("SELECT VERSION() as r");
        $founded = $command->queryOne();

        $version = trim($founded["r"]);
        preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $arr);
        $version = $arr[0];

		if (version_compare($version, $MySql_vercheck_min,'<'))
        {
            $this->addError(\Yii::t('app','MySQL installed version {cur}, {req} is required',['cur' => $version,'req' => $MySql_vercheck_min]));
        }

		if ($version == '4.1.21' // sorting
			|| $version == '5.1.34' // auto_increment
			|| $version == '5.0.41' // search
//			|| $ver == '5.1.66' // forum page navigation
			)
        {
            $this->addError(\Yii::t('app','Problem version of the database').": " . trim($founded["r"]));
        }

        if (!$this->errorMessages)
        {
            $this->addSuccess(\Yii::t('app','The current version of the database').": " . trim($founded["r"]));
        }

		return true;
    }

}
