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
 * Class SecurityApacheCheck
 * @package skeeks\cms\checks
 */
class SecurityApacheCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = \Yii::t('app',"Web-server modules");
        $txt = \Yii::t('app','Apache mod_security module like module php suhosin designed to protect the site from hackers, but in practice it often interferes with normal operation of the site. It is recommended to turn it off, instead, to use the module of proactive protection Skeeks CMS.');
        $this->description      = <<<HTML
<p>
{$txt}
</p>
HTML;
;
        $this->errorText    = \Yii::t('app','Identified conflicts');
        $this->successText  = \Yii::t('app',"No conflicts found");

        parent::init();
    }


    public function run()
    {
		if (function_exists('apache_get_modules'))
		{
			$arLoaded = apache_get_modules();
			if (in_array('mod_security', $arLoaded))
            {
                $this->addError(\Yii::t('app',"Loaded module {m}, there may be problems in the work administrative part",['m' => 'mod_security']));
            }
			if (in_array('mod_dav', $arLoaded) || in_array('mod_dav_fs', $arLoaded))
            {
                $this->addError(\Yii::t('app','Loaded module {m}, {m1} will not work',['m' => 'mod_dav/mod_dav_fs', 'm1' => 'WebDav']));
            }
		}
    }

}
