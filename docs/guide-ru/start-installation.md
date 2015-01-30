Установка, кофигурирование и запуск проекта (расширенная)
========================================================
В данном разделе описан только процесс установки базового проекта, без дополнительных модулей, а также стандартное конфигурирование.

 1) [Установка файлов (скачивание проекта)](#1)
 
 2) [Базовое конфигурирование проекта](#2)
 
 3) [Инициализация](#3)
 
 4) [Установка миграций](#4)
 
 5) [Итог](#5)
 
 
1) Установка файлов (скачивание проекта)
----------------------------------------
Мы предлагаем несколько вариантов установки проекта:

### - Упрощенная установка

Вы можете установить приложение с помощью следующих команд (необходим доступ по ssh):

~~~
# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# установка composer (если уже установлен пропускаем этот шаг). Так же следует учесть чтобы в пхп был включен модуль phar 
php -r "readfile('https://getcomposer.org/installer');" | php

# устанавливаем composer-asset-plugin глобально. Это нужно сделать один раз.
php composer.phar global require "fxp/composer-asset-plugin:1.0.0-beta2"

# устанавливаем шаблон приложения skeeks-cms
php composer.phar create-project skeeks/cms-app
~~~


### - Установка с использованием только Composer

Вы можете установить приложение с помощью следующих команд (необходим доступ по ssh):

~~~
# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# установка composer (если уже установлен пропускаем этот шаг). Так же следует учесть чтобы в пхп был включен модуль phar 
php -r "readfile('https://getcomposer.org/installer');" | php

# устанавливаем composer-asset-plugin глобально. Это нужно сделать один раз.
php composer.phar global require "fxp/composer-asset-plugin:1.0.0-beta2"

# устанавливаем шаблон приложения skeeks-cms
php composer.phar create-project skeeks/cms-app

# Если делали под другим пользователем
chown -R www-data:www-data ../
~~~


### - Установка с использованием Git репозитория

~~~
# если git не установлен выполнить команду
sudo apt-get install git

# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# клонирование проекта из git репозитория
git clone http://git.skeeks.com/skeeks/cms-app.git

# переключаемся на релизную ветку (стабилная версия проекта)
git checkout origin/master

# установка composer (если уже установлен пропускаем этот шаг). Так же следует учесть чтобы в пхп был включен модуль phar 
php -r "readfile('https://getcomposer.org/installer');" | php

# устанавливаем composer-asset-plugin глобально. Это нужно сделать один раз.
php composer.phar global require "fxp/composer-asset-plugin:1.0.0-beta2"

# запускаем установку cms и всех необходимых зависимостей
php composer.phar install

# Если делали под другим пользователем
chown -R www-data:www-data ../
~~~

### - Установка используя FTP (пока невозможно)

1) заходим на [сайт](http://git.skeeks.com/skeeks/cms-app.git), качаем проект.

2) заливаем все файлы на FTP

Далее необходимо установить композер, и запустить установку через композер

TODO: необходимо доработать этот момент, чтобы можно было запускать через браузер все это дело.


2) Базовое конфигурирование проекта
-----------------------------------
Необходимо прописать настройки подключения к базе данных:
/var/www/sites/test.ru/common/config/main.php - настройки подключения к базе всех приложений

Собственно наше приложение уже будет настроено согласно базовым настройкам cms, все что нам необходимо сделать в простейшем случае, добавить настройки компонента базы данных.
Вот таким может быть простейший файл настроек.
~~~
<?php
/**
 * Общий конфиг для всего приложения
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.10.2014
 * @since 1.0.0
 */
 
$vendorPath         = dirname(dirname(__DIR__)) . '/vendor';
$configSkeeksCms    = include $vendorPath . '/skeeks/cms/config/main.php';

return \yii\helpers\ArrayHelper::merge($configSkeeksCms, [
//......
'components'    =>
    [
        //......

        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=host;dbname=DbName',
            'username' => 'user',
            'password' => 'pwd',
            'charset' => 'utf8',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
        ],
        
        //......
    ]
//......
]);
~~~

3) Инициализация
-----------------
~~~
# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# выполнить команду
php init
~~~

4) Установка миграций
---------------------
~~~
# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# установка миграций cms (миграции модуля админ находятся тут же)
php yii migrate --migrationPath=@skeeks/cms/migrations

# установка миграций проекта (по умолчанию их не будет, cms с собой принесет все что нужно)
php yii migrate

# установка прав доступа
php yii cms/rbac/init
~~~



5) Итог
-------

После всех проделанных действий в дирриктории /frontend/web/ будет сгенерирован файлик index.php:

~~~
<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/main-local.php'),
    require(__DIR__ . '/../config/main.php'),
    require(__DIR__ . '/../config/main-local.php')
);

$application = new yii\web\Application($config);
$application->run();
~~~

Админка сайта будет находиться по адресу: http://site.ru/~sx (адрес админки можно сконфигурировать каким угодно)

Root доступ для управления:

user: root

password: skeeks



5) Обновление проекта
-------

~~~
# Полное обновление проекта
php yii cms/update/project


#так же можно использовать по отдельности команды
php yii cms/update/clear-runtime
php yii cms/update/migration
php yii cms/update/composer
php yii cms/update/update
php yii cms/update/generate-modules-config-file
~~~

