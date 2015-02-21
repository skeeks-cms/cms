<?php
/**
 * Стандартный загрузчик
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.02.2015
 * @since 1.0.0
 */

require(VENDOR_DIR . '/autoload.php');
require(VENDOR_DIR . '/yiisoft/yii2/Yii.php');
require(COMMON_CONFIG_DIR . '/bootstrap.php');
require(APP_CONFIG_DIR . '/bootstrap.php');

$config = new \skeeks\cms\Config([SKEEKS_CONFIG_DIR . '/main.php']); //добавлены пути к конфигам cms
//Автоматически созданный файл, хранит пути к конфигам всех модулей
$modulesConfigFiles = [];
if (file_exists(TMP_AUTO_GENERATE_MODULES)) {
    $modulesConfigFiles = include TMP_AUTO_GENERATE_MODULES;
}

$config->appendFiles($modulesConfigFiles); //добавлены пути к конфигам всех файлов

$config->appendFiles([
    COMMON_CONFIG_DIR . '/main.php',                    //common для текущего окружения
    COMMON_ENV_CONFIG_DIR . '/main.php',                //common для текущего окружения
    APP_CONFIG_DIR . '/main.php',                       //app общий
    APP_ENV_CONFIG_DIR . '/main.php',                   //app для текущего окружения
]);

$config->cacheDir   = APP_RUNTIME_DIR;
$config->cache      = CONFIG_CACHE;
$config->name       = 'Standart all config';

$config->appendDependency(Yii::getVersion());
$config->appendDependency(filemtime(COMMON_CONFIG_DIR . '/main.php')); //кэш будет сбрасываться при редактировании файла с общим конфигом

return $config;