Работа с URL в CMS
============

SkeekS CMS предоставляет удобный \skeeks\cms\helpers\UrlHelper для работы с URL

Данный объект добавляет ряд удобных, часто используемых методов. И все url желатльно собирать через этот хелпер.


Как это работает?
Примеры:
------------

 - ссылка на контроллер controller модуля module действие action
 

```php
use \skeeks\cms\helpers\UrlHelper;

UrlHelper::construct("module/controller/action")->createUrl();
```

 - ссылка на контроллер controller модуля module действие action с дополнительными параметрами
 
```php
use \skeeks\cms\helpers\UrlHelper;

$url = UrlHelper::construct("module/controller/action");

$url->param1 = "value1";
$url->param2 = "value2";

$url->merge([
    "param3" => "value3"
]);


$url
    ->set("param4", "value4")
    ->set("param5", "valu5");
    
print_r((string) $url);
die;

//Будет напечатано: /module/controller/action?param1=value1&param2=value2&param3=value3&param4=value4&param5=valu5

```

 - Системные опции
 Так же cms предоставляет ряд системных опций. Для упрощения работы. Например если мы хотим отправить пользователя на какое либо действие, и сообщить url возврата, можно сделать так.

```php

$url = UrlHelper::construct("module/controller/action");
$url->setRef("/backUrl/");
print_r((string) $url);
//echo: /module/controller/action?_sx%5Bref%5D=%2FbackUrl%2F

$url->setRef(\Yii::$app->request->getUrl()); //Установить в реф параметр текущий адрес
//или проще так
$url->setCurrentRef();

```

 - Вытащить служебные параметры из запроса.
 
 ```php
 
$url = UrlHelper::getCurrent(); //Из объекта \Yii::$app->request собирается объект UrlHelper
$url->getSystem(); //получение массива служебных параметров
$url->getSystem("ref", "значение по умолчанию, если нет параметра ref"); //проучение одного служебного параметра
$url->getRef() //Функция заготовка


 ```
