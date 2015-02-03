<?php
/**
 * main-default
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.01.2015
 * @since 1.0.0
 */

$cmsConfigFile          = __DIR__ . '/main.php'; //стандартный конфиг цмс
$cmsConfig              = (array) include $cmsConfigFile; //стандартный конфиг цмс

return include __DIR__ . '/auto-include-modules.php'; //автоматическое подключение конфигов