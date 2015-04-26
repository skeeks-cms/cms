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
        $this->name             = "Наличие необходимых модулей php";
        $this->description      = <<<HTML
<p>Проверяется доступность требуемых расширений для полноценной работы продукта. В случае ошибки выводится список модулей, которые недоступны.</p>
<p>Для решения проблемы необходимо обратиться к хостеру, а для локальной установки самостоятельно установить требуемые расширения на основе документации на сайте php.net</p>
HTML;
;
        $this->errorText    = "Не установлены требуемые расширения";
        $this->successText  = "Все необходимые модули установлены";

        parent::init();
    }


    public function run()
    {
        $arMods = [
			'fsockopen'             => "Функции для работы с сокетами",
			'xml_parser_create'     => "Поддержка XML",
			'preg_match'            => "Поддержка регулярных выражений (Perl-Compatible)",
			'imagettftext'          => "Free Type Text",
			'gzcompress'            => "Zlib",
			'imagecreatetruecolor'  => "Библиотека GD",
			'imagecreatefromjpeg'   => "Поддержка jpeg в GD",
			'json_encode'           => "Поддержка JSON",
			'mcrypt_encrypt'        => "Функции шифрования MCrypt",
			'highlight_file'        => 'PHP Syntax Highlight',
			'mb_substr'             => "Поддержка mbstring",
		];

        $strError = '';
		foreach($arMods as $func => $desc)
		{
			if (!function_exists($func))
            {
                $this->addError($desc);
            }
		}

		if (!in_array('ssl', stream_get_transports()))
        {
            $this->addError("Поддержка ssl не настроена в php");
        }
    }
}
