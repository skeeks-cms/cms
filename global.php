<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 10.11.2017
 */
defined('ROOT_DIR') or die('Please specify the constant "ROOT_DIR" in index.php in your application.');
defined('VENDOR_DIR') or define('VENDOR_DIR', ROOT_DIR . '/vendor');

//Если Yii окружение не определено раньше в index.php или @app/config/global.php
if (!defined('YII_ENV')) {
    define('YII_ENV', 'prod');
}

//А мы все равно ее определим
if (!defined('YII_DEBUG')) {
    if (YII_ENV == 'prod') {
        defined('YII_DEBUG') or define('YII_DEBUG', false);
    } else {
        defined('YII_DEBUG') or define('YII_DEBUG', true);
    }
}

if (!defined('ENV')) {
    $env = getenv('ENV');
    if (empty($env)) {
        $env = YII_ENV;
    }
    define('ENV', $env);
}

/**
 * Глобальный файл где задается настройка окружения.
 * Если файла не будет создано, то окружение будет считано функцией getenv() или по другому прниципу
 */
defined('APP_ENV_GLOBAL_FILE') or define('APP_ENV_GLOBAL_FILE', ROOT_DIR . '/global.php');
//Проверка файла который создается скриптом в момент установки проекта, если он создан, то прочитаются его настройки.
$globalFileInited = APP_ENV_GLOBAL_FILE;
if (file_exists($globalFileInited)) {
    require $globalFileInited;
}