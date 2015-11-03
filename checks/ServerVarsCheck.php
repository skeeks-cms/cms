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
 * Class ServerVarsCheck
 * @package skeeks\cms\checks
 */
class ServerVarsCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = \Yii::t('app',"The values of server variables");
        $txt1 = \Yii::t('app','Check the values of variables defined by the web server.');
        $txt2 = \Yii::t('app','value HTTP_HOST is taken based on the name of this virtual host (domain). Invalid domain leads to the fact that some browsers (ie, Internet Explorer 6) refuse to maintain his cookie, as a consequence - not stored authorization.');
        $this->description      = <<<HTML
<p>
{$txt1}
</p>
<p>
{$txt2}
</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"Incorrect");
        $this->successText  = \Yii::t('app','Correct');

        parent::init();
    }


    public function run()
    {
		list($host, $port) = explode(':',$_SERVER['HTTP_HOST']);
		if ($host != 'localhost' && !preg_match('#^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$#',$host))
		{
			if (!preg_match('#^[a-z0-9\-\.]{2,192}\.(xn--)?[a-z0-9]{2,63}$#i', $host))
            {
                $val = htmlspecialchars($_SERVER['HTTP_HOST']);
                $this->addError(\Yii::t('app','The current domain is not valid ({val}). It may only contain numbers, letters and hyphens. It must contain the point.',['val' => $val]));
            }
		}
    }

}
