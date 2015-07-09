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
        $this->name             = "Версия MySQL сервера";
        $this->description      = <<<HTML
<p>
Известны версии MySQL с ошибками, препятствующими нормальной работе сайта:
</p>
<p><b>5.0.41</b> - некорректно работает метод EXISTS, поиск работает неправильно;</p>
<p><b>5.1.34</b> - шаг auto_increment по умолчанию равен 2, требуется 1;</p>
<p>
Обновите MySQL, если у вас установлена одна их этих версий.
</p>
HTML;
;
        $this->errorText    = "Ошибка";
        $this->successText  = "Успешно";

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
            $this->addError("Установлена MySQL версии {$version}, требуется {$MySql_vercheck_min}");
        }

		if ($version == '4.1.21' // sorting
			|| $version == '5.1.34' // auto_increment
			|| $version == '5.0.41' // search
//			|| $ver == '5.1.66' // forum page navigation
			)
        {
            $this->addError("Проблемная версия БД: " . trim($founded["r"]));
        }

        if (!$this->errorMessages)
        {
            $this->addSuccess("Текущая версия БД: " . trim($founded["r"]));
        }

		return true;
    }

}
