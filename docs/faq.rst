===
FAQ
===

Разные вопросы
==============

Тут временно публикуются различные полезные примеры, которые можно использовать на своих сайтах.
Куча разных несвязных примеров, которые могут быть полезны. Позже это будет структурированно и разнесено

Как правильно сделать resize изображений?
-----------------------------------------

Вот так можно получить ссылку на resize изображения.

.. code-block:: php

    echo \Yii::$app->imaging->thumbnailUrlOnRequest($model->image ? $model->image->src : null,
         new \skeeks\cms\components\imaging\filters\Thumbnail([
             'w' => 0,
             'h' => 200,
         ]), $model->code
    );


Как отметить обязательные поля в формах ``*``
---------------------------------------------

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


Как изменить timeout pjax?
--------------------------

Для того, чтобы изменить timeout pjax, глобально во всех виджетах pjax проекта, можно добавить код javascript:

.. code-block:: js

    $(function()
    {
        $.pjax.defaults.timeout = 30000;
    });


Как включить js, css и html оптимизацию?
----------------------------------------

Для этих целей существуют дополнительное расширение, которое обычно уже стоит в базовых проектах.

`https://github.com/skeeks-cms/cms-assets-auto-compress <https://github.com/skeeks-cms/cms-assets-auto-compress>`_

Включение и настройка оптимизаций, проивзодится через систему управления сайтом:

.. figure:: _static/screen/faq/js-css-compress.png
       :width: 300 px
       :align: center
       :alt: SkeekS CMS


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

