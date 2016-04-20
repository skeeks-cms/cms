<?php
/**
 * Запуск console приложения
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.02.2015
 * @since 1.0.0
 */
// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

//Determination of uncertainty must be constants
require(__DIR__ . '/global.php');
//Standard loader
require(__DIR__ . '/bootstrap.php');

//Result config
$config = \yii\helpers\ArrayHelper::merge(
    (array) require(__DIR__ . '/tmp-config-console-extensions.php'),
    (array) require(__DIR__ . '/app-config.php')
);
$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);