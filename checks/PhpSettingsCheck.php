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
        $this->name             = \Yii::t('app',"Required parameters PHP");
		$txt = \Yii::t('app','Checks critical parameters defined in the configuration file php.ini. If an error occurs, shows a list of parameters that are not configured correctly. For details on each parameter can be found at php.net.');
        $this->description      = <<<HTML
<p>
{$txt}
</p>
HTML;
;
        $this->errorText    = \Yii::t('app',"Incorrect settings");
        $this->successText  = \Yii::t('app',"Settings are correct");

        parent::init();
    }


    public function run()
    {
        $strError = '';
		$PHP_vercheck_min = '5.4.0';
		if (version_compare($v = phpversion(), $PHP_vercheck_min, '<'))
        {
            $this->addError(\Yii::t('app','Installed version of PHP {cur}, {req] or higher is required',['cur' => $v,'req' => $PHP_vercheck_min]));
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
                $curS = $cur ? htmlspecialchars($cur) : 'off';
                $valS = $val ? 'on' : 'off';

                $this->addError(\Yii::t('app','Parameter {p} = {v}, required {r}',['p' => $param, 'v' => $curS, 'r' => $valS]));
            }
		}

		$param = 'default_socket_timeout';
		if (($cur = ini_get($param)) < 60)
        {
            $cur = htmlspecialchars($cur);
            $this->addError(\Yii::t('app','Parameter {p} = {v}, required {r}',['p' => $param, 'v' => $cur, 'r' => '60']));
        }

		if (version_compare(phpversion(), '5.3.9', '>='))
		{
			if (($m = ini_get('max_input_vars')) && $m < 10000)
            {
                $this->addError(\Yii::t('app','{var} value should not be less than {max}. Current value',['var' => 'max_input_vars','max' => '10000']).": {$m}");
            }
		}

		// check_divider
		$locale_info = localeconv();
		$delimiter = $locale_info['decimal_point'];
		if ($delimiter != '.')
        {
            $this->addError(\Yii::t('app','Current delimiter: {delim}, {delim2} is required',['delim' => '&quot;'.$delimiter.'&quot;','delim2' => '&quot;.&quot;']));
        }

		// check_precision
		if (1234567891 != (string) doubleval(1234567891))
        {
            $this->addError(\Yii::t('app','Parameter {p} has invalid value',['p' => 'precision']));
        }

		// check_suhosin
		if (in_array('suhosin',get_loaded_extensions()) && !ini_get('suhosin.simulation'))
        {
            $val = ini_get('suhosin.simulation') ? 1 : 0;
            $this->addError(\Yii::t('app','Loaded module {m}, there may be problems work in the administrative part ({s})',['m' => 'suhosin', 's' => 'suhosin.simulation='.$val]));
        }

		// check_backtrack_limit
		$param = 'pcre.backtrack_limit';
		$cur = self::Unformat(ini_get($param));
		ini_set($param,$cur + 1);
		$new = ini_get($param);
		if ($new != $cur + 1)
        {
            $this->addError(\Yii::t('app','Not possible to change the value {v} through {f}',['v' => 'pcre.backtrack_limit', 'f' => 'ini_set']));
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
