Установка, кофигурирование и запуск проекта
===========================================
В данном разделе описан только процесс установки базового проекта, без дополнительных модулей, а также стандартное конфигурирование.
 
1) Установка
------------
Мы предлагаем несколько вариантов установки проекта

### Установка с использованием только Composer

Вы можете установить приложение с помощью следующих команд (необходим доступ по ssh):

~~~
# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# установка composer (если уже установлен пропускаем этот шаг) 
curl -sS https://getcomposer.org/installer | php

# устанавливаем composer-asset-plugin глобально. Это нужно сделать один раз.
php composer.phar global require "fxp/composer-asset-plugin:1.0.0-beta2"

# устанавливаем шаблон приложения skeeks-cms
php composer.phar create-project skeeks/cms-app
~~~

### Установка с использованием git

~~~
# если git не установлен выполнить команду
sudo apt-get install git

# перейти в папку своего проекта
cd /var/www/sites/test.ru/

# установка composer (если уже установлен пропускаем этот шаг) 
curl -sS https://getcomposer.org/installer | php

# устанавливаем composer-asset-plugin глобально. Это нужно сделать один раз.
php composer.phar global require "fxp/composer-asset-plugin:1.0.0-beta2"

# клонирование проекта из git репозитория
git clone http://git.skeeks.com/skeeks/cms-app.git

# переключаемся на релизную ветку (стабилная версия проекта)
git checkout origin/release

# запускаем установку cms и всех необходимых зависимостей
php composer.phar install
~~~

### Установка используя FTP (пока невозможно)

1) заходим на [сайт](http://git.skeeks.com/skeeks/cms-app.git), качаем проект.
2) заливаем все файлы на FTP

Далее необходимо установить композер, и запустить установку через композер
TODO: необходимо доработать этот момент, чтобы можно было запускать через браузер все это дело.





After in /frontend/web/ dir will be generated file index.php

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


Next step install migrations 

~~~
php yii migrate
php yii migrate --migrationPath=common/modules/game/migrations
~~~

GETTING STARTED
---------------

After you install the application, you have to conduct the following steps to initialize
the installed application. You only need to do these once for all.

1. Run command `init` to initialize the application with a specific environment.
2. Create a new database and adjust the `components['db']` configuration in `common/config/main-local.php` accordingly.
3. Apply migrations with console command `yii migrate`. This will create tables needed for the application to work.
4. Set document roots of your Web server:

- for frontend `/path/to/yii-application/frontend/web/` and using the URL `http://frontend/`
- for backend `/path/to/yii-application/backend/web/` and using the URL `http://backend/`

To login into the application, you need to first sign up, with any of your email address, username and password.
Then, you can login into the application with same email address and password at any time.

