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
 * Class PhpModulesCheck
 * @package skeeks\cms\components
 */
class PhpModulesCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = \Yii::t('app',"Availability of required modules {php}",['php' => 'php']);
        $txt1 = \Yii::t('app','Checking the availability of the required extensions for maximality work product. If an error occurs, show a list of modules that are unavailable.');
        $txt2 = \Yii::t('app','To solve the problem, refer to the host, and for the local installation to independently install the required extension on the basis of documentation at website {site}',['site' => 'php.net']);
        $this->description      = <<<HTML
<p>{$txt1}</p>
<p>{$txt2}</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"Not installed required extensions");
        $this->successText  = \Yii::t('app',"All necessary modules are installed");

        parent::init();
    }


    public function run()
    {
        $arMods = [
			'fsockopen'             => \Yii::t('app',"Functions to work with sockets"),
			'xml_parser_create'     => \Yii::t('app',"{p} support",['p' => 'XML']),
			'preg_match'            => \Yii::t('app','Support for regular expressions')." (Perl-Compatible)",
			'imagettftext'          => \Yii::t('app',"Free Type Text"),
			'gzcompress'            => "Zlib",
			'imagecreatetruecolor'  => \Yii::t('app','GD Library'),
			'imagecreatefromjpeg'   => \Yii::t('app',"Jpeg support in GD"),
			'json_encode'           => \Yii::t('app',"{p} support",['p' => 'JSON']),
			'mcrypt_encrypt'        => \Yii::t('app','The encryption function {mcrypt}',['mcrypt' => 'MCrypt']),
			'highlight_file'        => 'PHP Syntax Highlight',
			'mb_substr'             => \Yii::t('app',"{p} support",['p' => 'mbstring']),
			'curl_init'             => \Yii::t('app',"{p} support",['p' => 'curl']),
		];

        $strError = '';
		foreach($arMods as $func => $desc)
		{
			if (!function_exists($func))
            {
                $this->addError($desc);
            } else
            {
                $this->addSuccess($desc);
            }
		}

		if (!in_array('ssl', stream_get_transports()))
        {
            $this->addError(\Yii::t('app',"{ssl} support is not configured in {php}",['ssl' => 'ssl','php' => 'php']));
        }

        if (!extension_loaded('fileinfo'))
        {
            $this->addError(\Yii::t('app','Do not set extension {ext}. Do not set extension {ext}. Will not work on the file download link (for those files which can not parse file extension in the url, for example {smpl})',['ext' => 'fileinfo', 'smpl' => 'https://im3-tub-ru.yandex.net/i?id=7bc5907fe7558cf8f2e97e7a760c6fdd&n=21']));
        } else
        {
            $this->addSuccess(\Yii::t('app','Extension {ext} is installed',['ext' => 'php fileinfo']));
        }
    }
}
