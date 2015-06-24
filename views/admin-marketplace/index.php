<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.06.2015
 */

$api = new \skeeks\cms\components\marketplace\MarketPlaceApi();
$packages = $api->get('packages');
print_r($packages);die;
?>
asd
