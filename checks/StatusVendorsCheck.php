<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.06.2015
 */
namespace skeeks\cms\checks;
use skeeks\cms\base\CheckComponent;
use skeeks\cms\console\controllers\CheckController;
/**
 * Class StatusVendorsCheck
 * @package skeeks\cms\checks
 */
class StatusVendorsCheck extends CheckComponent
{
    public function init()
    {
        $vendorDir              = VENDOR_DIR;

        $this->name             = "Проверка модификации ядра и библиотек";
        $this->description      = <<<HTML
<p>
Осуществаляется проверка, изменения ядра cms и сторонних библиотек (Папка /vendor). Расположение папки и ее название задаются глобальной константой VENDOR_DIR.
Для текущего проекта:
</p>
<p>
<code>{$vendorDir}</code>
</p>
<p>
Мы настоятельно не рекоммендуем модифицировать ядро проекта, поскольку это может привезти к ошибкам обновления, или же ваши модификации будут удалены в процессе обновления.
Что в свою очередь, может привести к ошибкам работы проекта.
</p>
<p>Для решения проблемы, можно запустить команду в консоле:</p>
<p><code>php yii cms/composer/revert-modified-files</code></p>
HTML;
;
        $this->errorText    = "Найдены модификации ядра";
        $this->successText  = "Ядро не модифицировалось";

        parent::init();
    }


    public function run()
    {
		$emptyCheck = new EmptyConsoleCheck();
        $emptyCheck->run();

        if (!$emptyCheck->isSuccess())
        {
            $this->addError('Найдены ошибки в процессе работы консольных комманд, проверка модификации ядра не может быть запущена.');
            return;
        }

        ob_start();
            system('cd '  . ROOT_DIR . '; COMPOSER_HOME=.composer php composer.phar status --verbose');
        $result = ob_get_clean();
        $result = trim($result);

        if ($result)
        {
            $this->addError('Найдены модификации ядра: ' . <<<HTML
<pre><code>$result</code></pre>
HTML
);
        }

    }

}
