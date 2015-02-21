<?php
/**
 * Общий конфиг для всего приложения
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.10.2014
 * @since 1.0.0
 */

\Yii::beginProfile('common config init');

//Уже подключенные модули
$included   = [];
//Текущий модуль cms его конфиги
$included[] = __DIR__ . '/main.php';
//Автоматическое подключение модулей. Можно отключить эту штуку. Перекрыть ниже по совему.
if (file_exists(AUTO_GENERATED_MODULES_FILE))
{
    $enableFiles = include AUTO_GENERATED_MODULES_FILE;
    $enableFiles = array_unique($enableFiles);

    if (is_array($enableFiles))
    {
        foreach ($enableFiles as $extension => $file)
        {
            if (!in_array($file, $included))
            {
                $included[] = $file;
            }
        }
    }
}
$config = new \skeeks\cms\Config($included);
return $config->getResult();


$cacheId = 'cache-config-file-' . md5(YII_ENV . YII_ENV_DEV . PHP_VERSION . filemtime($appConfigFile) .  \Yii::getVersion());
$cacheConfigFile = \Yii::getAlias('@common/runtime/' . $cacheId . '.cache.conf');
if (file_exists($cacheConfigFile))
{
        \Yii::beginProfile('common config cache file read');
            $config = unserialize(file_get_contents($cacheConfigFile));
        \Yii::endProfile('common config cache file read');

    \Yii::endProfile('common config init');
    return $config;
}

$vendorPath             = dirname(dirname(__DIR__)) . '/vendor';

$cmsConfigFile          = $vendorPath . '/skeeks/cms/config/main-default.php';
///$configCms = include 'modules-config.php';
$configCms = include $cmsConfigFile;

$config = \yii\helpers\ArrayHelper::merge(
    $configCms,
    (array) include $appConfigFile
);

    \Yii::beginProfile('write common config cache file');
        $file = fopen($cacheConfigFile, "w");
        fwrite($file, serialize($config));
        fclose($file);
    \Yii::endProfile('write common config cache file');

\Yii::endProfile('common config init');
return $config;
