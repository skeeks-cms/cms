=========
Structure
=========

Directories
===========

Стандартная структура проекта выглядит следующим образом (корневая директория проекта):

.. code-block:: bash

        common              содержит общие файлы всех приложений
            config/              содержит общие конфигурационные файлы приложений
            mail/                содержит файлы представлений для электронной почты
            models/              содержит классы моделей, используемые во всех приложениях
            runtime/             временно генерируемые файлы используемые всеми приложениями
            widgets/             классы виджетов приложений
        console             консольное приложение, скрипты для крона и прочее
            config/              содержит конфигурационные файлы
            controllers/         содержит консольные контроллеры (commands)
            migrations/          содержит миграции
            models/              содержит классы моделей
            runtime/             временно генерируемые файлы
        frontend            приложение 1
            assets/              описание и храенение Asset блоков Yii2
            config/              содержит конфигурационные файлы
            controllers/         contains Web controller classes
            models/              содержит классы моделей
            runtime/             временно генерируемые файлы
            templates/           содержит набор шаблонов
                default/         Файлы представлений шаблона по умолчанию
            web/                 публичная директория (файлы js, css, img...)
                assets/          временные js, css, файлы
            widgets/             классы виджетов приложения

        frontend2           приложение 2
        //    ... полностью повторяет структуру предыдущего приложения...
        frontend3           приложение 3
        //    ... полностью повторяет структуру предыдущего приложения...

        vendor/                  используемые дополнительные библиотеки в проекте
        tests                    contains various tests for the advanced application
            codeception/         contains tests developed with Codeception PHP Testing Framework


Стандартный проект содержит следющие папки в корневой директории проекта:

- **common** - файлы общие для всех приложений.
- **console** - консольное приложение.
- **frontend** - frontend приложение.


Глобальные константы
====================

* ``ROOT_DIR`` — путь до корневой директории проекта
* ``ENV`` — названия окружения (от окружения будет зависеть, то какие настройки будут подключены)

Предопределенные псевдонимы путей
=================================

Подробнее про псевдонимы `https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases <https://www.yiiframework.com/doc/guide/2.0/en/concept-aliases>`_

- `@yii` - фремворк директория.
- `@app` - базовый путь текущего запущеного приложения.
- `@common` - файлы общие для всех приложений.
- `@frontend` - frontend приложение.
- `@console` - console приложение.
- `@runtime` - runtime directory of currently running web application.
- `@vendor` - Composer vendor directory.
- `@bower` - vendor directory that contains the `bower packages <http://bower.io/>`_.
- `@npm` - vendor directory that contains `npm packages <https://www.npmjs.org/>`_.
- `@web` - base URL of currently running web application.
- `@webroot` - web root directory of currently running web application.
- `@root` - корневая директория проекта

Алиасы специфичные для ваших проектов, можно прописать в общем конфиге проекта, следующим образом:

.. code-block:: php

    'aliases'    => [
        'frontend2'     => '@root/frontend2',
        'frontend3'     => '@root/frontend3',
    ],


Приложения
==========
По умолчанию в проекте есть два приложения: frontend и console. Frontend обычно представляет то, что представлено конечному пользователю, собственно сам сайт.

Консоль обычно используется для заданий cron и управления серверами низкого уровня. Также он используется во время развертывания приложений и обрабатывает миграции и т.д.

Существует также общий каталог, содержащий файлы, используемые более чем одним приложением. Например, модель пользователя.

Каждое приложение имеет собственное пространство имен и псевдоним, соответствующий его имени. То же самое относится к общему каталогу.


Конфигурирование
================

Простейшая конфигурация приложения
----------------------------------

В файле ``/frontend/web/index.php`` определяется путь слияния кофигурационных файлов проекта.

В простейшем виде можно сконфигурировать приложение стандартным способом, вот так может выглядить файл ``/frontend/web/index.php``:

.. code-block:: php

    define("ENV", 'prod');
    define("ROOT_DIR", dirname(dirname(__DIR__)));

    require_once(ROOT_DIR . '/vendor/skeeks/cms/bootstrap.php');

    $config = \yii\helpers\ArrayHelper::merge([]
        , require(__DIR__ . '/../../common/config/main.php')
        , require(__DIR__ . '/../../frontend/config/main.php')
    );

    $application = new \yii\web\Application($config);
    $application->run();

В этом случае, как и в любом yii2 проекте, необходимо полность сконфигурировать приложение самостоятельно.


Автоматическая конфигурация приложения
--------------------------------------

Слиянием файлов конфигураций занимается специальный composer-plugin `cms-composer <https://github.com/skeeks-cms/cms-composer>`_. Подробнее можно прочитать тут: `https://habr.com/post/329286/ <https://habr.com/post/329286/>`_

Идея в том, что любое расширение yii2 (модуль, компонент, пакет), может пердоставить собственные настройки, которые автоматически подключатся к проекту.

Слиянием файлов конфигураций занимается `composer` по команде или после обновления зависимостей.

Пути слияния прописываются в `composer.json` проекта, по умолчанию следующим образом:


.. code-block:: json

    {
        "extra": {
            "config-plugin": {
                //Каждый из установленных расширений в проекте, уже предоставил конфиги для соответсвующих секций
                "web": [
                    "common/config/main.php",
                    "common/config/db.php",
                    "frontend/config/main.php"
                ],
                "web-dev": [
                    "$web",
                    "?frontend/config/env/dev/main.php"
                ],
                "web-prod": [
                    "$web",
                    "?frontend/config/env/prod/main.php"
                ],
                "console": [
                    "common/config/main.php",
                    "common/config/db.php",
                    "console/config/main.php"
                ],
                "console-dev": [
                    "$console",
                    "?console/config/env/dev/main.php"
                ],
                "console-prod": [
                    "$console",
                    "?console/config/env/prod/main.php"
                ]
            }
        }
    }



А файл ``/frontend/web/index.php``:

.. code-block:: php

    define("ENV", 'prod');
    define("ROOT_DIR", dirname(dirname(__DIR__)));

    require(ROOT_DIR . '/vendor/skeeks/cms/app-web.php');


В приведенной конфигурации проекта, если определить константу ``ENV`` как ``prod``

То в web приложении результирующая конфигурация будет состоять из:

.. code-block:: json

    "web-prod": [
        "$web", //сюда попадут все конфиги расширений + "common/config/main.php" + "common/config/db.php" + "frontend/config/main.php"
        "?frontend/config/env/prod/main.php"
    ],


Для того чтобы перекомпилировать конфигурацию приложения, необходимо выполнить команду:

.. code-block:: bash

    composer du

Для того чтобы посмотреть пути наследования конфигураций:

.. code-block:: bash

    composer du --verbose

.. attention::

    Не забывайте обновлять файл конфигураций во время разработки!

Автоматическая конфигурация приложения + автообновление конфигураций
--------------------------------------------------------------------

.. code-block:: php

    define("ENV", 'dev');
    define("ROOT_DIR", dirname(dirname(__DIR__)));

    //Стандартная загрузка yii2 + всего необходимого для skeeks cms
    require(ROOT_DIR . '/vendor/skeeks/cms/bootstrap.php');

    //Если включен dev режим работы с сайтом, то сляния настроек будет происходить при выполнении каждого сценария
    if (ENV == 'dev') {
        \Yii::beginProfile('Rebuild config');
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');
        \skeeks\cms\composer\config\Builder::rebuild();
        \Yii::endProfile('Rebuild config');
    }

    //Подключение стандартного слитого файла конфигураций для текущего окружения
    $configFile = \skeeks\cms\composer\config\Builder::path('web-' . ENV);
    if (!file_exists($configFile)) {
        $configFile = \skeeks\cms\composer\config\Builder::path('web');
    }
    $config = (array)require $configFile;

    $application = new yii\web\Application($config);
    $application->run();


Варианты определения константы ENV
----------------------------------

.htaccess
~~~~~~~~~

Определение через .htaccess ``/frontend/web/index.php``:


.. code-block:: bash

    SetEnv ENV dev

``/frontend/web/index.php``:

.. code-block:: php

    $env = getenv('ENV');
    if (!empty($env)) {
        defined('ENV') or define('ENV', $env);
    }

    define("ROOT_DIR", dirname(dirname(__DIR__)));
    require(ROOT_DIR . '/vendor/skeeks/cms/app-web.php');


ip адрес
~~~~~~~~

Определение окружения для определенного ip адреса ``/frontend/web/index.php``:

.. code-block:: php

    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
    if (in_array($ip, ['31.148.139...'])) {
        defined('ENV') or define('ENV', 'dev');
    }

    define("ROOT_DIR", dirname(dirname(__DIR__)));
    require(ROOT_DIR . '/vendor/skeeks/cms/app-web.php');


Таким образом любой разработчик имеет возможность иметь собственную конфигурацию, а проект единую кодовую базу.
Так же любое установленное расширение, которое предоставляет конфигурацию по текущим правилам, сразу приносит настройку в проект.