<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 10.11.2017
 */
// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

//Standard loader
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
        \Yiisoft\Config\Modifier\RecursiveMerge::groups('console', 'console-' . ENV, 'params', "params-console-" . ENV),
    ],
    "params-console-" . ENV
);

if ($config->has('console-' . ENV)) {
    $configData = $config->get('console-' . ENV);
} else {
    $configData = $config->get('console');
}

\Yii::endProfile('Load config app');

$application = new yii\console\Application($configData);
$exitCode = $application->run();
exit($exitCode);