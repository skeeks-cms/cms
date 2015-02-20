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
//Загрузка
require(__DIR__ . '/bootstrap.php');

/*$config = coreIncludeConfigs([

]);*/
$confCommonEnv = [];
if (file_exists(COMMON_ENV_CONFIG_DIR . '/main.php'))
{
    $confCommonEnv = require(COMMON_ENV_CONFIG_DIR . '/main.php');
}

$confAppEnv = [];
if (file_exists(APP_ENV_CONFIG_DIR . '/main.php'))
{
    $confAppEnv = require(APP_ENV_CONFIG_DIR . '/main.php');
}

$config = yii\helpers\ArrayHelper::merge(
    require(COMMON_CONFIG_DIR . '/main.php'), //common общий
    $confCommonEnv,                                     //common для текущего окружения
    require(APP_CONFIG_DIR . '/main.php'),           //app общий
    $confAppEnv                                         //app для текущего окружения
);


$application = new yii\web\Application($config);
$application->run();

