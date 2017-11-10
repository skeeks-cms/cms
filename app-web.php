<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 10.11.2017
 */
//Standard loader
require(__DIR__ . '/bootstrap.php');

$configFile = \skeeks\cms\composer\config\Builder::path('web-' . YII_ENV);
if (!file_exists($configFile)) {
    $configFile = \skeeks\cms\composer\config\Builder::path('web');
}
$config = (array) require $configFile;

$application = new yii\web\Application($config);
$application->run();
