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
        $this->name             = "Время на БД и веб сервере";
        $this->description      = <<<HTML
<p>
Сравнивается системное время базы данных и веб-сервера. Рассинхронизация может быть, когда они установлены на разные физические машины, но чаще всего в результате неправильной установки часового пояса.
</p>
HTML;
;
        $this->errorText    = "Ошибка";
        $this->successText  = "Успешно";

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
            $this->addError("Время отличается на {$diff} секунд");
        }
    }

}
