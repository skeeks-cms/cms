<?php
/**
 * Определение глобальных констант
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.02.2015
 * @since 1.0.0
 */
/**
 * Перечень необязательных констант, если их не определить они будут определены по ходу выполнения проекта.
 * Ниже закомментированы их значения по умолчанию
 */
//define('YII_ENV',                 'dev');                   //Необязательная константа, если не будет определена, то определение произойдет по ходу выполнения проекта
//define('YII_DEBUG',               true);                    //Необязательная константа, если не будет определена, то определение произойдет по ходу выполнения проекта
//define("COMMON_CONFIG_DIR",       ROOT_DIR . '/common/config');  //Общие конфиги
//define("VENDOR_DIR",              ROOT_DIR . '/vendor');    //Вендоры

/**
 * Будут определены по ходу выполенения кода
 * COMMON_ENV_CONFIG_DIR    //Дирриктория с конфигами common для текущего окружения
 * APP_ENV_CONFIG_DIR       //Дирриктория с конфигами текущего приложения для текущего окружения
 */
//Корень запущеного приложения
defined('APP_CONFIG_DIR') or die('Please specify the constant "APP_CONFIG_DIR" in index.php in your application.');

//Корень проекта
defined('ROOT_DIR') or die('Please specify the constant "ROOT_DIR" in index.php in your application.');
defined('VENDOR_DIR') or define('VENDOR_DIR', ROOT_DIR . '/vendor');

defined('COMMON_CONFIG_DIR') or define('COMMON_CONFIG_DIR', ROOT_DIR . '/common/config');

define("TMP_CONFIG_FILE_EXTENSIONS", VENDOR_DIR . '/skeeks/tmp-config-extensions.php' );
define("TMP_CONSOLE_CONFIG_FILE_EXTENSIONS", VENDOR_DIR . '/skeeks/tmp-console-config-extensions.php' );

/**
 * Глобальный файл где задается настройка окружения.
 * Если файла не будет создано, то окружение будет считано функцией getenv() или по другому прниципу
 */
defined('APP_ENV_GLOBAL_FILE') or define('APP_ENV_GLOBAL_FILE', ROOT_DIR . '/global.php');


//Проверка файла который создается скриптом в момент установки проекта, если он создан, то прочитаются его настройки.
$globalFileInited = APP_ENV_GLOBAL_FILE;
if (file_exists($globalFileInited))
{
    require $globalFileInited;
}

//Если Yii окружение не определено раньше в index.php или @app/config/global.php
if (!defined('YII_ENV'))
{
    define('YII_ENV', 'dev');
}

define('COMMON_ENV_CONFIG_DIR', COMMON_CONFIG_DIR . '/env/' . YII_ENV);
define('APP_ENV_CONFIG_DIR',    APP_CONFIG_DIR . '/env/' . YII_ENV);


//Здесь уже определена константа YII_ENV, на нее можно опираться
if (!defined('YII_DEBUG'))
{
    //Пытаемся подключить global.php для нужного окружения, общего приложения
    $envGlobal = COMMON_ENV_CONFIG_DIR . '/global.php';

    if (file_exists($envGlobal))
    {
        include $envGlobal;
    }
}

if (!defined('YII_DEBUG'))
{
    //Пытаемся подключить global.php для нужного окружения, общего приложения
    $envGlobal = COMMON_CONFIG_DIR . '/global.php';

    if (file_exists($envGlobal))
    {
        include $envGlobal;
    }
}

//А мы все равно ее определим
if (!defined('YII_DEBUG'))
{
    //TODO: можно вынести в еще одну константу, типо для каких окружений включить или отключать дебаг.
    if (YII_ENV == 'prod')
    {
        defined('YII_DEBUG') or define('YII_DEBUG', false);
    } else
    {
        defined('YII_DEBUG') or define('YII_DEBUG', true);
    }
}