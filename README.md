Yii2 cms extension
====================
Yii2 cms extension

Installation
------------

This extension is used by other modules and components cms, it is a basic need for the expansion of the cms in general.

Contains useful behaviors and helpers to work with yii framework

```
php composer.phar require --prefer-dist skeeks/yii2-cms "*"
```

or add

```
"skeeks/yii2-cms": "*"
```

to the require section of your `composer.json` file.

Install migrations 

```
php yii migrate --migrationPath=@skeeks/cms/migrations
```

Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \skeeks\cms\Module ?>```