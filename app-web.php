<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 10.11.2017
 */
require(__DIR__ . '/bootstrap.php');

\Yii::beginProfile('Load config app');

if (YII_ENV == 'dev') {
    \Yii::beginProfile('Rebuild config');
    \skeeks\cms\composer\config\Builder::rebuild();
    \Yii::endProfile('Rebuild config');
}

$configFile = \skeeks\cms\composer\config\Builder::path('web-' . YII_ENV);
if (!file_exists($configFile)) {
    $configFile = \skeeks\cms\composer\config\Builder::path('web');
}
$config = (array)require $configFile;

\Yii::endProfile('Load config app');

$application = new yii\web\Application($config);
$application->run();
