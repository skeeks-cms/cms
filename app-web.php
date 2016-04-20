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

\Yii::beginProfile('Load config app');
//Result config
$config = \yii\helpers\ArrayHelper::merge(
    (array) require(__DIR__ . '/tmp-config-extensions.php'),
    (array) require(__DIR__ . '/app-config.php')
);
\Yii::endProfile('Load config app');

$application = new yii\web\Application($config);
$application->run();
