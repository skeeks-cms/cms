<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.03.2015
 * @var \skeeks\cms\Config $config
 */
//Determination of uncertainty must be constants
require(__DIR__ . '/global.php');
//Standard loader
require(__DIR__ . '/bootstrap.php');

//Result config
$config = \yii\helpers\ArrayHelper::merge(
    (array) require(__DIR__ . '/auto-config.php'),
    (array) require(__DIR__ . '/app-config.php')
);

$application = new yii\web\Application($config);
$application->run();
