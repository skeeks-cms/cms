==========
Javascript
==========

Общее
=====

Мы развиваем и поддерживаем свой js фреймворк.

Он позволяет легко писать собственные js классы и наследовать их друг от друга.

Используется глобальный namespace ``sx``

Установка
=========

В SkeekS CMS уже используется данный фреймворк.

Для установки в любой yii2 проект, можно использовать ``skeeks/yii2-sx`` пакет.

Базовые классы
==============

Вот так можно получить ссылку на resize изображения.

.. code-block:: js

    sx.classes.Entity
    sx.classes.Component
    sx.classes.AjaxQuery
    sx.classes.Cookie


Пространство видимости
======================

Используется глобальный namespace ``sx``

Если же нужно создать класс внутри области видимости нужно использовать следующую конструкцию.

.. code-block:: js

    sx.createNamespace('test.classes', sx);

Это позволит создавать классы внутри пространства sx.test.classes

Например:

.. code-block:: js

    sx.test.classes.Entity = sx.classes.Entity.extend({

    });


Наследование
============

Пример конструкции наследования:

.. code-block:: js

    sx.classes.Product = sx.classes.Entity.extend({
        //Тут код расширяющий возможности класса Entity

        getCustomName: function()
        {
            return this.get('name') + " (" + this.get('article') + ")";
        }
    });



Использование классов
=====================

.. code-block:: js

    var Product = new sx.classes.Product({
        "name" : "Подушка",
        "article" : "A15226",
    });

    Product.get('name'); //Подушка
    Product.get('article'); //A15226
    Product.getCustomName(); //Подушка (A15226)

    Product.set('name', 'Новое название');
    Product.get('name'); //Новое название

    //объект продукт можно положить так же в пространство sx
    sx.Product = new sx.classes.Product({
        "name" : "Подушка",
        "article" : "A15226",
    });


Переопределение родительского конструктора
==========================================

.. code-block:: js

    sx.classes.ProductCustom = sx.classes.Product.extend({

        construct: function (name, opts)
        {
            opts = opts || {};
            this.set('name', name);
            this.applyParentMethod(sx.classes.Product, 'construct', [opts]);
        }

    });

    //Тогда продукт нужно создавать так

    new sx.classes.ProductCustom("Подушка", {
        "article" : "A15226",
    });


Базовый компонент
=================

.. code-block:: js

    sx.classes.Demo = sx.classes.Component.extend({

        _init: function()
        {
            //Тут код который исполняется сразу же в момент создания класса
        },

        _onDomReady: function()
        {
            //Тут написать код которые выполнится когда сработает событие domReady
        },

        _onWindowReady: function()
        {
            //Тут написать код которые выполнится когда сработает событие windowReady
        }
    });


Подключение библиотеки к проекту на yii2
========================================

В шаблоне:

.. code-block:: php


    <?

    //Минимум
    skeeks\sx\assets\Core::register($this);
    //Или более полную библиотеку
    skeeks\sx\assets\Custom::register($this);

    ?>

Или через asset bundle:

.. code-block:: php

    namespace frontend\assets;

    class AppAsset extends AssetBundle
    {
        public $basePath = '@webroot';
        public $baseUrl = '@web';
        public $css = [
            'css/app.css',

        ];
        public $js = [
            'js/app.js',
        ];
        public $depends = [
            'skeeks\sx\assets\Custom',
        ];
    }


