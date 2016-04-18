<?php
/**
 * До настройка неопределенных констант
 *
 * ENV_POSSIBLE_NAMES
 * YII_ENV
 * YII_DEBUG
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 19.02.2015
 * @since 1.0.0
 */

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



