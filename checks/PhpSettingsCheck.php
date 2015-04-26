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
 * Class PhpSettingsCheck
 * @package skeeks\cms\checks
 */
class PhpSettingsCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = "Обязательные параметры PHP";
        $this->description      = <<<HTML
<p>
Проверяются критические значения параметров, определяемых в файле настроек php.ini. В случае ошибки выводится список параметров, которые настроены неправильно. Подробную информацию по каждому параметру можно найти на сайте php.net.
</p>
HTML;
;
        $this->errorText    = "Настройки правильные";
        $this->successText  = "Настройки неправильные";

        parent::init();
    }


    public function run()
    {
        $strError = '';
		$PHP_vercheck_min = '5.4.0';
		if (version_compare($v = phpversion(), $PHP_vercheck_min, '<'))
        {
            $this->addError("Установлена версия PHP {$v}, требуется {$PHP_vercheck_min} и выше");
        }

		$arRequiredParams = array(
			'safe_mode' => 0,
			'file_uploads' => 1,
//			'session.cookie_httponly' => 0, # 14.0.1:main/include.php:ini_set("session.cookie_httponly", "1");
			'wincache.chkinterval' => 0,
			'session.auto_start' => 0,
			'magic_quotes_runtime' => 0,
			'magic_quotes_sybase' => 0,
			'magic_quotes_gpc' => 0,
			'arg_separator.output' => '&'
		);

		foreach ($arRequiredParams as $param => $val)
		{
			$cur = ini_get($param);
			if (strtolower($cur) == 'on')
				$cur = 1;
			elseif (strtolower($cur) == 'off')
				$cur = 0;

			if ($cur != $val)
            {
                $curS = $cur ? htmlspecialcharsbx($cur) : 'off';
                $valS = $val ? 'on' : 'off';

                $this->addError("Параметр {$param} = {$curS}, требуется {$valS}");
            }
		}

		$param = 'default_socket_timeout';
		if (($cur = ini_get($param)) < 60)
        {
            $cur = htmlspecialcharsbx($cur);
            $this->addError("Параметр {$param} = {$cur}, требуется 60");
        }

		if (version_compare(phpversion(), '5.3.9', '>='))
		{
			if (($m = ini_get('max_input_vars')) && $m < 10000)
            {
                $this->addError("Значение max_input_vars должно быть не ниже 10000. Текущее значение: {$m}");
            }
		}

		// check_divider
		$locale_info = localeconv();
		$delimiter = $locale_info['decimal_point'];
		if ($delimiter != '.')
        {
            $this->addError("Текущий разделитель: &quot;{$delimiter}&quot;, требуется &quot;.&quot;");
        }

		// check_precision
		if (1234567891 != (string) doubleval(1234567891))
        {
            $this->addError("Параметр precision имеет неверное значение");
        }

		// check_suhosin
		if (in_array('suhosin',get_loaded_extensions()) && !ini_get('suhosin.simulation'))
        {
            $val = ini_get('suhosin.simulation') ? 1 : 0;
            $this->addError("Загружен модуль suhosin, возможны проблемы в работе административной части (suhosin.simulation={$val})");
        }

		// check_backtrack_limit
		$param = 'pcre.backtrack_limit';
		$cur = self::Unformat(ini_get($param));
		ini_set($param,$cur + 1);
		$new = ini_get($param);
		if ($new != $cur + 1)
        {
            $this->addError("Нет возможности изменить значение pcre.backtrack_limit через ini_set");
        }
    }


    function Unformat($str)
	{
		$str = strtolower($str);
		$res = intval($str);
		$suffix = substr($str, -1);
		if($suffix == "k")
			$res *= 1024;
		elseif($suffix == "m")
			$res *= 1048576;
		elseif($suffix == "g")
			$res *= 1048576*1024;
		elseif($suffix == "b")
			$res = self::Unformat(self::substr($str,0,-1));
		return $res;
	}
}
