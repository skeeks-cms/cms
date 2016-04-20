<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.04.2016
 */
$configCommon = [];
if (file_exists(COMMON_CONFIG_DIR . '/main.php'))
{
    $configCommon = (array) include COMMON_CONFIG_DIR . '/main.php';
}

$configCommonEnv = [];
if (file_exists(COMMON_ENV_CONFIG_DIR . '/main.php'))
{
    $configCommonEnv = (array) include COMMON_ENV_CONFIG_DIR . '/main.php';
}

$configApp = [];
if (file_exists(APP_CONFIG_DIR . '/main.php'))
{
    $configApp = (array) include APP_CONFIG_DIR . '/main.php';
}

$configAppEnv = [];
if (file_exists(APP_ENV_CONFIG_DIR . '/main.php'))
{
    $configAppEnv = (array) include APP_ENV_CONFIG_DIR . '/main.php';
}

$configData = \yii\helpers\ArrayHelper::merge(
    $configCommon,
    $configCommonEnv,
    $configApp,
    $configAppEnv
);

return (array) $configData;