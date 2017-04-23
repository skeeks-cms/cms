===
FAQ
===

Examples
========

Тут временно публикуются различные полезные примеры, которые можно использовать на своих сайтах.
Куча разных несвязных примеров, которые могут быть полезны. Позже это будет структурированно и разнесено

Отметка обязательных полей формы ``*``
--------------------------------------

Для того чтобы добавить во все формы генерируемые стандартными средствами yii2, звездочки ``*`` обязтельных полей. Глобально на стринце можно подключить js и css.

.. code-block:: js

    $(function()
    {
        $('.form-group.required label').each(function()
        {
            $(this).append($('<span class="sx-from-required">').text(' *'));
        });
    });

.. code-block:: css

    .sx-from-required
    {
        color: red;
        font-weight: bold;
    }


Изменить timeout pjax по умолчанию глобально
--------------------------------------------

.. code-block:: js

    $(function()
    {
        $.pjax.defaults.timeout = 30000;
    });



Перенос проекта на другой хостинг
=================================

Архивация
---------

Создать актуальный архив базы данных

.. code-block:: bash

    php yii dbDumper/mysql/dump

Создать архив вашего проекта



Восстановление
--------------

1. Скачать файлы проекта
~~~~~~~~~~~~~~~~~~~~~~~~
Развернуть архив, или склонировать проект из git репозитория

2. Установка composer и зависимостей
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    # Download latest version of composer in project
    curl -sS https://getcomposer.org/installer | COMPOSER_HOME=.composer php

    # Extra plug-ins
    COMPOSER_HOME=.composer php composer.phar global require fxp/composer-asset-plugin --no-plugins
    # Enter your github api key in composer.json
    # Download dependency
    COMPOSER_HOME=.composer php composer.phar install -o
    # Run the command to initialize the project, the installer executable file and the necessary rights to the directory
    php yii cms/init

3. Configuring the database
~~~~~~~~~~~~~~~~~~~~~~~~~~~
Прописать коннект к базе данных `common/config/db.php`

4. Installation of migrations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: bash

    #Installation of ready-dump
    php yii dbDumper/mysql/restore



