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
class SessionCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = "Сохранение сессии";
        $this->description      = <<<HTML
<p>
Проверяется возможность хранить данные на сервере используя механизм сессий. Эта базовая возможность необходима для сохранения авторизации между хитами.
</p>
<p>
Сессии могут не работать, если их поддержка не установлена, в php.ini неправильно указана папка для хранения сессий или она не доступна на запись.
</p>
HTML;
;
        $this->errorText    = "Ошибка";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		if (!$this->lastValue)
		{
			$_SESSION['CHECKER_CHECK_SESSION'] = 'SUCCESS';
			$this->ptc          = 50;
			$this->lastValue    = "Y";
		}
		else
		{
            $this->lastValue    = null;
            $this->ptc          = 100;

			if ($_SESSION['CHECKER_CHECK_SESSION'] != 'SUCCESS')
            {
                $this->addError('Не получилось сохранить сессию');
            }

			unset($_SESSION['CHECKER_CHECK_SESSION']);
		}
    }

}
