=========
CMS Admin
=========

Административная часть сайта реализована компонентом ``skeeks/cms-admin``
По умолчанию CMS уже сдержит этот компонент.

Настройка и конфигурирование
----------------------------

Компонент админки подключается и настраивается стандартным способом в проект.

Пример конфигурирования
~~~~~~~~~~~~~~~~~~~~~~~

В файле конфига проекта ``frontend/config/main.php`` отредактировать секцию ``components``

.. code-block:: php

   'admin' =>
   [
      'class' => '\skeeks\cms\modules\admin\components\settings\AdminSettings',

      'languageCode' => 'ru',

      'allowedIPs' => [
          '91.219.167.252',
          '93.186.50.*'
      ]
   ],

   'urlManager' =>
   [
      'rules' =>
      [
         'cms-admin' => [
             "class" => 'skeeks\cms\modules\admin\components\UrlRule',
             'adminPrefix' => '~sx' //Префикс админки, то есть путь к админке сайта может быть любой
         ],
      ]
   ]


Доступные настройки
~~~~~~~~~~~~~~~~~~~

languageCode
""""""""""""
Язык интерфейса административной части, по умолчанию ``ru``

allowedIPs
""""""""""
Разрешенный массив ip адресов, по умолчанию ``['*']``


Меню
----

Административное меню формируется путем слияния конфигов всех установленных расширений и конфига проекта.

* ``@skeeks/cms/config/admin/menu.php``
* ``@skeeks/cms-admin/config/admin/menu.php``
* ``@all-other-extensions/config/admin/menu.php``
* ``@app/config/admin/menu.php``


Формат
~~~~~~

В конечном виде конфиг меню представляет собой один большой массив с элементами

.. code-block:: php

    [
        'users' =>
        [
            'label'     => \Yii::t('skeeks/cms', 'Users'),
            'priority'  => 200,
            'enabled'   => true,
            "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],

            'items' =>
            [
                [
                    "label"     => \Yii::t('skeeks/cms',"User management"),
                    "url"       => ["cms/admin-user"],
                    "img"       => ['\skeeks\cms\modules\admin\assets\AdminAsset', 'images/icons/user.png'],
                    'priority'  => 0
                ],

                //....
            ]
        ],
    ],

Каждый элемент массива может содержать следующие опции:

* **label** — Название пункта меню
* **priority** — Порядок чем меньше тем выше пункт
* **enabled** — Показывается или не показывается
* **img** — Картинка (массив [Asset, 'путь к файлу'])
* **url** — URL массив который будет передан в ``yii\helpers\Url::to()``;


Создание контроллера
--------------------

Создание контроллера
~~~~~~~~~~~~~~~~~~~~

