<?php
/**
 * modules-config
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */
$config                 = $cmsConfig;
//Автоматически создаваемый файл со списком и путями конфига подключенных модулей.
$autoEnbladeModules     = $config['components']['cms']['tmpModulesConfigFile'];


//Уже подключенные модули
$included   = [];
$included[] = $cmsConfigFile;


/*$config = \yii\helpers\ArrayHelper::merge(
    $config,
    include $vendorPath . '/skeeks/cms-module-kladr/configs/main.php',
    include $vendorPath . '/skeeks/cms-module-money/configs/main.php'
);*/

//Автоматическое подключение модулей. Можно отключить эту штуку. Перекрыть ниже по совему.
if (file_exists($autoEnbladeModules))
{
    $enableFiles[] = $cmsConfigFile;
    $enableFiles = include $autoEnbladeModules;
    $enableFiles = array_unique($enableFiles);

    if (is_array($enableFiles))
    {
        foreach ($enableFiles as $extension => $file)
        {
            if (file_exists($file))
            {
                if (!in_array($file, $included))
                {
                    $config = \yii\helpers\ArrayHelper::merge($config, (array) include $file);
                }
            }
        }
    }
}

return $config;