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

        [
            "label"     => "Дерево страниц",
            "url"       => ["cms/admin-tree"],
        ],

        [
            "label"     => "Инфоблоки",
            "url"       => ["cms/admin-user-group"],
        ],

        [
            "label"     => "Управление пользователями",
            "url"       => ["cms/admin-user"],
        ],

        [
            "label"     => "Управление группами пользователей",
            "url"       => ["cms/admin-user-group"],
        ],

        [
            "label"     => "Публикации",
            "url"       => ["cms/admin-user-group"],
        ],

        [
            "label"     => "Комментарии",
            "url"       => ["cms/admin-user-group"],
        ],

        [
            "label"     => "Голоса",
            "url"       => ["cms/admin-user-group"],
        ],

        [
            "label"     => "Подписки",
            "url"       => ["cms/admin-user-group"],
        ],
    ];


    /**
     * Используем свой layout
     * @var string
     */
    //public $layout ='@skeeks/cms/modules/admin/views/layouts/main.php';

    /**
     * @return array
     */
    protected function _descriptor()
    {
        return array_merge(parent::_descriptor(), [
            "name"          => "Cms module",
            "description"   => "Базовый модуль cms, без него не будет работать ничего и весь мир рухнет.",
        ]);
    }
}