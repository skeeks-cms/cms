<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.03.2015
 * @var \skeeks\cms\Config $config
 */
//Определение всех неопределенных необходимых констант
require(__DIR__ . '/global.php');
//Стандартный загрузчик конфигов
$config = (array) require(__DIR__ . '/bootstrap.php');

$application = new yii\web\Application($config);
$application->run();
