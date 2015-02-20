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
//define('GETENV_POSSIBLE_NAMES',   'env,environment');       //Если не будет определена константа YII_ENV то значение этой константы будут прочитаны фунцией getenv(); перебирая имена возможных окружений.
//define('ALLOW_CACHE_CONFIG',      true);                    //Включить или отключить кэширование конфигов, по умолчанию включено, сильно разгружает проект. Много логики и мержа конфигов. Эта опция полностью отключает все эти хитрые мержи.
//define("COMMON_DIR",              ROOT_DIR . '/common');    //Где общая папка
//define("COMMON_CONFIG_DIR",       COMMON_DIR . '/config');  //Общие конфиги
//define("VENDOR_DIR",              ROOT_DIR . '/vendor');    //Вендоры

/**
 * Будут определены по ходу выполенения кода
 * COMMON_ENV_CONFIG_DIR    //Дирриктория с конфигами common для текущего окружения
 * APP_ENV_CONFIG_DIR       //Дирриктория с конфигами текущего приложения для текущего окружения
 */

define("SKEEKS_DIR", __DIR__);

//Корень проекта
defined('ROOT_DIR') or die('Please specify the constant "ROOT_DIR" in index.php in your application.');
//Корень запущеного приложения
defined('APP_DIR') or die('Please specify the constant "APP_DIR" in index.php in your application.');
//Корень запущеного приложения
defined('APP_CONFIG_DIR') or die('Please specify the constant "APP_CONFIG_DIR" in index.php in your application.');


defined('COMMON_DIR') or define('COMMON_DIR', ROOT_DIR . '/common');
defined('COMMON_CONFIG_DIR') or define('COMMON_CONFIG_DIR', COMMON_DIR . '/config');
defined('VENDOR_DIR') or define('VENDOR_DIR', ROOT_DIR . '/vendor');

//Использовать кэширование конфигов
defined('ALLOW_CACHE_CONFIG') or define("ALLOW_CACHE_CONFIG", true);

//Определение всех неопределенных необходимых констант
require(SKEEKS_DIR . '/config/global.php');