<?php
/**
 * Запуск веб приложения
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.02.2015
 * @since 1.0.0
 */
//Определение всех неопределенных необходимых констант
require(__DIR__ . '/global.php');
//Стандартный загрузчик конфигов
$config = require(__DIR__ . '/bootstrap.php');

// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

//$config->appendDependency(Yii::getVersion()); //Так можно подмешать чего либо к сбросу кэша

$application = new yii\console\Application($config->getResult());
$exitCode = $application->run();
exit($exitCode);