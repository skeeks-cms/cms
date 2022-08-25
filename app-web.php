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
    //\skeeks\cms\composer\config\Builder::rebuild();
    if (isset($_GET['rebuild'])) {
        \Yii::beginProfile('Rebuild config');
        \Yiisoft\Composer\Config\Builder::rebuild();
        \Yii::endProfile('Rebuild config');
    }
}

//$configFile = \skeeks\cms\composer\config\Builder::path('web-' . ENV);
$configFile = \Yiisoft\Composer\Config\Builder::path('web-' . ENV);
if (!file_exists($configFile)) {
    $configFile = \Yiisoft\Composer\Config\Builder::path('web');
    //$configFile = \skeeks\cms\composer\config\Builder::path('web');
}
$config = (array)require $configFile;
//print_r($config);die;
\Yii::endProfile('Load config app');

\Yii::beginProfile('new app');
$application = new yii\web\Application($config);
\Yii::endProfile('new app');
$application->run();
