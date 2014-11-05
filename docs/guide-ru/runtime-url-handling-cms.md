Работа с URL в CMS
============

SkeekS CMS предоставляет удобный \skeeks\cms\helpers\UrlHelper для работы с URL

Данный объект добавляет ряд удобных, часто используемых методов. И все url желатльно собирать через этот хелпер.


Как это работает?
Примеры:

 - ссылка на контроллер controller модуля module действие action
 -------------------

```
use \skeeks\cms\helpers\UrlHelper;

UrlHelper::construct("module/controller/action")->createUrl();

```

 - ссылка на контроллер controller модуля module действие action с дополнительными параметрами
 -------------------
```
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