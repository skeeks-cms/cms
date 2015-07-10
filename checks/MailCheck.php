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
class MailCheck extends CheckComponent
{

    public function init()
    {
        $this->name             = "Отправка почты (функция mail)";
        $this->description      = <<<HTML
<p>
Осуществляется передача тестового письма на почтовый адрес hosting_test@skeeks.com через стандартную php функцию mail.
Чтобы максимально приблизить тест к реальной работе почты, заведен служебный ящик.
</p>
<p>
В качестве тестового текста письма передается исходный код скрипта проверки сайта.
</p>
<p>
<b>Никакие пользовательские данные не передаются!</b>
</p>
<p>
Обратите внимание, что тест не проверяет доставку письма в почтовый ящик. Более того, нельзя протестировать доставку почты на другие почтовые сервера.
</p>
<p>
Если время отправки письма больше секунды, это может значительно затормозить работу сайта. Обратитесь к хостеру с просьбой настроить отложенную отправку почты (через спулер) или включите передачу почты (и работу агентов) через cron. Для этого в dbconn.php надо добавить константу:
</p>
HTML;
;
        $this->errorText    = "Ошибка";
        $this->successText  = "Успешно";

        parent::init();
    }


    public function run()
    {
		$body = "Test message.\nDelete it.";

		list($usec0, $sec0) = explode(" ", microtime());
		$val = mail("hosting_test@skeeks.com", "Skeeks site checker".(""), $body, (''));
		list($usec1, $sec1) = explode(" ", microtime());
		$time = round($sec1 + $usec1 - $sec0 - $usec0, 2);
		if ($val)
		{
			if ($time > 1)
            {
                $this->addError("Отправлено. Время отправки: " . $time . " сек.");
            } else
            {
                $this->addSuccess("Отправлено. Время отправки: " . $time . " сек.");
            }
		}
		else
        {
            $this->addError("Письмо не отправлено.");
        }

		return true;
    }

}
