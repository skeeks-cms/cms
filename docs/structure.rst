=========
Structure
=========

Directories
-----------

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
        vendor/                  используемые дополнительные библиотеки в проекте
        tests                    contains various tests for the advanced application
            codeception/         contains tests developed with Codeception PHP Testing Framework


The root directory contains the following subdirectories:

- **common** - files common to all applications.
- **console** - console application.
- **frontend** - frontend web application.

Root directory contains a set of files.

- **.gitignore** contains a list of directories ignored by git version system. If you need something never get to your source
  code repository, add it there.
- **composer.json** - Composer config described in Configuring Composer.
- **LICENSE.md** - license info. Put your project license there. Especially when opensourcing.
- **README.md** - basic info about installing template. Consider replacing it with information about your project and its
  installation.
- **yii** - console application bootstrap.
- **yii.bat** - same for Windows.


Predefined path aliases
-----------------------

- `@yii` - framework directory.
- `@app` - base path of currently running application.
- `@common` - common directory.
- `@frontend` - frontend web application directory.
- `@console` - console directory.
- `@runtime` - runtime directory of currently running web application.
- `@vendor` - Composer vendor directory.
- `@bower` - vendor directory that contains the `bower packages <http://bower.io/>`_.
- `@npm` - vendor directory that contains `npm packages <https://www.npmjs.org/>`_.
- `@web` - base URL of currently running web application.
- `@webroot` - web root directory of currently running web application.

The aliases specific to the directory structure of the advanced application
(`@common`,  `@frontend` and `@console`) are defined in `common/config/bootstrap.php`.


Applications
------------
There are two applications in advanced template: frontend and console. Frontend is typically what is presented to end user, the project itself. Console is typically used for cron jobs and low-level server management. Also it's used during application deployment and handles migrations and assets.

There's also a common directory that contains files used by more than one application. For example, User model.

Frontend and backend are both web applications and both contain the web directory. That's the webroot you should point your web server to.

Each application has its own namespace and alias corresponding to its name. Same applies to the common directory.


Configuration and environments
------------------------------
There are multiple problems with a typical approach to configuration:

* Each team member has its own configuration options. Committing such config will affect other team members.
* Production database password and API keys should not end up in the repository.
* There are multiple server environments: development, testing, production. Each should have its own configuration.
* Defining all configuration options for each case is very repetitive and takes too much time to maintain.

In order to solve these issues Yii introduces a simple environments concept. Each environment is represented by a set of files under the environments directory.

By default there are two environments: dev and prod. First is for development. It has all the developer tools and debug turned on. Second is for server deployments. It has debug and developer tools turned off.

In order to avoid duplication configurations are overriding each other. For example, the frontend reads configuration in the following order:

* ``@skeeks/cms/config/main.php``
* Все компонент подключенные компоненты yii2 у которых есть ``config/main.php``
* ``common/config/main.php``
* ``common/config/env/{your-env}/main.php``
* ``frontend/config/main.php``
* ``frontend/config/env/{your-env}/main.php``

Parameters are read in the following order:

* ``common/config/params.php``
* ``common/config/env/{your-env}/params.php``
* ``frontend/config/params.php``
* ``frontend/config/env/{your-env}/params.php``

The later config file overrides the former.