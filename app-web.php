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

//$config->appendDependency(Yii::getVersion()); //Так можно подмешать чего либо к сбросу кэша

$application = new yii\web\Application($config->getResult());
$application->run();

