<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

$env = getenv('ENV');
if (!empty($env)) {
    defined('ENV') or define('ENV', $env);
}

require_once(__DIR__ . '/bootstrap.php');

\Yii::beginProfile('Load config app');

if (YII_ENV == 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', 'On');
}


$config = new \Yiisoft\Config\Config(
    new \Yiisoft\Config\ConfigPaths(ROOT_DIR, "config"),
    null,
    [
        \Yiisoft\Config\Modifier\RecursiveMerge::groups('web', 'web-' . ENV, 'params', "params-web-" . ENV),
    ],
    "params-web-" . ENV
);


if ($config->has('web-' . ENV)) {
    $configData = $config->get('web-' . ENV);
} else {
    $configData = $config->get('web');
}

/*print_r($configData);die;*/
\Yii::endProfile('Load config app');

\Yii::beginProfile('new app');
/*print_r($configData);die;*/
$application = new yii\web\Application($configData);
\Yii::endProfile('new app');
$application->run();
