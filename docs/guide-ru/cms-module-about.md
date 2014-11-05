Что такое модуль cms?
=====================
Cms модуль — это расширенны модуль Yii, который добавляет набор дополнительных свойств, для того чтобы вписаться в общую работу cms.

```
    /**
     * @var string название модуля
     */
    public $name = "";

    /**
     *  [
            "label"     => "Управление пользователями",
            "url"       => "cms/test-admin",
            "priority"  => 10,
        ],

        [
            "label"     => "Управление группами",
            "url"       => "cms/user-group-admin",
            "priority"  => 5,
        ]
     *
     * @var array пункуты меню админки
     */
    public $adminMenuItems      = [];
    /**
     * @var string название модуля в меню админки, опционально
     */
    public $adminMenuName       = "";
    /**
     * @var bool включить/отключить отображение блока меню в админке
     */
    public $adminMenuEnabled    = true;
    
    /**
     * @return array
     */
    protected function _descriptor()
    {
        return
        []
    }
    
```

Любое из публичных свойств модуля, возможно перекрыть и настроить в любой момент. А это значит, что все эти настрйки можно опредилть в конфиге проекта, и каждый проект может настроить любой из модулей под себя.

Однако _descriptor() - это закрытая функция, которая вернет данные для дескриптора ([читать про дескриптор тут](base-descriptor-cms.md))

Простой пример использования:

```
<?php
/**
 * Module
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms;
/**
 * Class Module
 * @package skeeks\cms
 */
class Module extends base\Module
{
    public $noImage         = "http://vk.com/images/deactivated_100.gif";
    public $adminEmail      = "semenov@skeeks.com";
    public $supportEmail    = "support@skeeks.com";

    public $controllerNamespace = 'skeeks\cms\controllers';


    /**
     * @var string
     */
    public $adminMenuName   = "Основное меню";

    /**
     * @var array настройки админки
     */
    public $adminMenuItems  =
    [
        [
            "label"     => "Сайты",
            "url"       => ["cms/admin-user-group"],
        ],
    ]
    
    /**
     * @return array
     */
    protected function _descriptor()
    {
        return array_merge(parent::_descriptor(), [
            "version"               => "1.0.0",
            "name"          => "Cms module",
            "description"   => "Базовый модуль cms, без него не будет работать ничего и весь мир рухнет.",
            "companies"   =>
            [
                [
                    "name"      =>  "",
                    "emails"    => ["", "support@skeeks.com"],
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
              
            ],
        ]);
    }
}
```