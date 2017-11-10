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
require(__DIR__ . '/bootstrap.php');

$configFile = \skeeks\cms\composer\config\Builder::path('console-' . YII_ENV);
if (!file_exists($configFile)) {
    $configFile = \skeeks\cms\composer\config\Builder::path('console');
}

$config = (array) require $configFile;

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);