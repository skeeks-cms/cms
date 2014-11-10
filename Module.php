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
            "name"          => "SkeekS cms",
            "description"   => "Базовый модуль cms, без него не будет работать ничего и весь мир рухнет.",
        ]);
    }
}