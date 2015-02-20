<?php
/**
 * bootstrap
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

function coreIncludeConfigs($files = [])
{

}