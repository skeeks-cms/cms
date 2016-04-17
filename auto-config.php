<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */
//Автоматически созданный файл, хранит пути к конфигам всех модулей
if (ENABLE_MODULES_CONF)
{
    $config             = new \skeeks\cms\Config(); //добавлены пути к конфигам cms

    if (APP_TYPE == 'web')
    {
        $config->appendFiles([SKEEKS_CONFIG_DIR . '/main.php']); //добавлены пути к конфигам cms
    } else if (APP_TYPE == 'console')
    {
        $config->appendFiles([SKEEKS_CONFIG_DIR . '/main-console.php']); //добавлены пути к конфигам cms
    }

    $modulesConfigFiles = [];
    if (file_exists(AUTO_GENERATED_MODULES_FILE)) {
        $modulesConfigFiles = include AUTO_GENERATED_MODULES_FILE;
        if (isset($modulesConfigFiles[APP_TYPE]))
        {
            $modulesConfigFiles = $modulesConfigFiles[APP_TYPE];
        }
    }

    $config->appendFiles($modulesConfigFiles); //добавлены пути к конфигам всех файлов

    $config->cacheDir   = APP_RUNTIME_DIR;
    $config->cache      = CONFIG_CACHE;
    $config->name       = 'config_' . APP_TYPE;

    $config->appendDependency(YII_ENV);
    $config->appendDependency(PHP_VERSION); //кэш будет сбрасываться при редактировании файла с общим конфигом
    $config->appendDependency(filemtime(COMMON_CONFIG_DIR . '/main.php')); //кэш будет сбрасываться при редактировании файла с общим конфигом
    $config->appendDependency(filemtime(APP_CONFIG_DIR . '/main.php')); //кэш будет сбрасываться при редактировании файла с общим конфигом
    $config->appendDependency(filemtime(COMMON_CONFIG_DIR . '/db.php')); //кэш будет сбрасываться при включении и отключении модульных конфигов

    return (array) $config->getResult();

} else
{
    return [];
}