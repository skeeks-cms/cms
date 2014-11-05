Descriptor cms компонента
========================
Зачем нужен дескриптор?
Зачастую необходимо описать тот или иной компонент, виджет или модуль (все это по сути есть компонент), и для это реализован этот класс дескриптора.
Настройки которые описаны в дескрипторе как правило закрытые, и задаются жестко разработчиком.
Формат описания настроек напоминает композер.
Как и любой компонент yii дескриптор можно сконструировать следующим образом.

Базовый дескриптор лежит в папке базовых классов cms skeeks\cms\base\components

```
use skeeks\cms\base\components\Descriptor;

new Descriptor(
        [
            "version"               => "1.0.0",

            "name"                  => "Module Skeeks Cms",
            "description"           => "",
            "keywords"              => "skeeks, cms",

            "homepage"              => "http://www.skeeks.com/",
            "license"               => "BSD-3-Clause",

            "support"               =>
            [
                "issues"    =>  "http://www.skeeks.com/",
                "wiki"      =>  "http://cms.skeeks.com/wiki/",
                "source"    =>  "http://git.skeeks.com/skeeks/yii2-app"
            ],

            "companies"   =>
            [
                [
                    "name"      =>  "SkeekS",
                    "emails"    => ["info@skeeks.com", "support@skeeks.com"],
                    "phones"    => ["+7 (495) 722-28-73"],
                    "sites"     => ["skeeks.com"]
                ]
            ],

            "authors"    =>
            [
                [
                    "name"      => "Semenov Alexander",
                    "emails"    => ["semenov@skeeks.com"],
                    "phones"    => ["+7 (495) 722-28-73"]
                ],

                [
                    "name"      => "Semenov Alexander",
                    "emails"    => ["semenov@skeeks.com"],
                    "phones"    => ["+7 (495) 722-28-73"]
                ],
            ],
        ]
)

```