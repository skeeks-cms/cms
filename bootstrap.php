<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 10.11.2017
 */
require(__DIR__ . '/global.php');

require(VENDOR_DIR . '/autoload.php');
require(VENDOR_DIR . '/yiisoft/yii2/Yii.php');

\Yii::setAlias('@root', ROOT_DIR);
\Yii::setAlias('@vendor', VENDOR_DIR);