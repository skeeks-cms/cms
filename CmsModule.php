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
class CmsModule extends Module
{

    public $controllerNamespace = 'skeeks\cms\controllers';

    /**
     * Используем свой layout
     * @var string
     */
    public $layout ='@skeeks/cms/modules/admin/views/layouts/main.php';

    /**
     * @return array
     */
    protected function _descriptor()
    {
        return array_merge(parent::_descriptor(), [
            "name"          => "Cms module",
            "description"   => "Базовый модуль cms, без него не будет работать ничего и весь мир рухнет.",

            "admin" =>
            [
                "items" =>
                [
                    [
                        "label"     => "Управление пользователями",
                        "route"     => "cms/admin-user",
                        "priority"  => 10,
                    ],

                    [
                        "label"     => "Управление группами",
                        "route"     => "cms/admin-user-group",
                        "priority"  => 5,
                    ]
                ]
            ]
        ]);
    }
}