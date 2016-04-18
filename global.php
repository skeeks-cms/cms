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
//define('CONFIG_CACHE',            true);                    //Включить или отключить кэширование конфигов, по умолчанию включено, сильно разгружает проект. Много логики и мержа конфигов. Эта опция полностью отключает все эти хитрые мержи.
//define("COMMON_DIR",              ROOT_DIR . '/common');    //Где общая папка
//define("COMMON_CONFIG_DIR",       COMMON_DIR . '/config');  //Общие конфиги
//define("VENDOR_DIR",              ROOT_DIR . '/vendor');    //Вендоры
//define("BACKUP_DIR",              ROOT_DIR . '/backup');    //Дирриктория для бекапов

/**
 * Будут определены по ходу выполенения кода
 * COMMON_ENV_CONFIG_DIR    //Дирриктория с конфигами common для текущего окружения
 * APP_ENV_CONFIG_DIR       //Дирриктория с конфигами текущего приложения для текущего окружения
 */
//Корень проекта
defined('ROOT_DIR') or die('Please specify the constant "ROOT_DIR" in index.php in your application.');
//Корень запущеного приложения
defined('APP_CONFIG_DIR') or die('Please specify the constant "APP_CONFIG_DIR" in index.php in your application.');

defined('APP_TYPE') or define('APP_TYPE', 'web');

defined('COMMON_DIR') or define('COMMON_DIR', ROOT_DIR . '/common');
defined('COMMON_CONFIG_DIR') or define('COMMON_CONFIG_DIR', COMMON_DIR . '/config');
defined('VENDOR_DIR') or define('VENDOR_DIR', ROOT_DIR . '/vendor');
//Дирриктория для бекапов
defined('BACKUP_DIR') or define('BACKUP_DIR', ROOT_DIR . '/backup');

//Использовать кэширование конфигов
defined('CONFIG_CACHE') or define("CONFIG_CACHE", true);

//Временный файл, в котором храняться пути к подключенным модулям
defined('AUTO_GENERATED_MODULES_FILE') or define("AUTO_GENERATED_MODULES_FILE", ROOT_DIR . '/auto-config-map.php' );

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

//Если Yii окружение не определено раньше в index_.php или @app/config/global.php
if (!defined('YII_ENV'))
{
    define('YII_ENV', 'dev');
}

define('COMMON_ENV_CONFIG_DIR', COMMON_CONFIG_DIR . '/env/' . YII_ENV);
define('APP_ENV_CONFIG_DIR',    APP_CONFIG_DIR . '/env/' . YII_ENV);

//TODO хорошо бы добавитьл, чтение фйлов global для для текущего приложения, и текущего окружения, но пока обойдемся, не хочется покдлючить много файлов.
//TODO можно вынести это в отдельную константу

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