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
namespace skeeks\cms\modules\admin;

use skeeks\cms\Module as CmsModule;
use skeeks\cms\App;
use yii\helpers\Inflector;
/**
 * Class Module
 * @package skeeks\modules\cms\user
 */
class Module extends CmsModule
{
    public $controllerNamespace = 'skeeks\cms\modules\admin\controllers';

    /**
     * Используем свой layout
     * @var string
     */
    public $layout ='@skeeks/cms/modules/admin/views/layouts/main.php';

    public function init()
    {
        parent::init();
    }

    /**
     * @return array
     */
    protected function _descriptor()
    {
        return array_merge(parent::_descriptor(), [
            "name"          => "Админка cms",
            "description"   => "Модуль входит в состав модуля cms",
        ]);
    }


    /**
     * @var array
     * @see [[items]]
     */
    private $_menuItems;


    /**
     * Get avalible menu.
     * @return array
     */
    public function getMenuItems()
    {
        if ($this->_menuItems === null)
        {
            return $this->_menuItems = $this->_loadMenuItems();
        }

        return $this->_menuItems;
    }

    /**
     * Get core menu
     * @return array
     */
    private function _loadMenuItems()
    {
        $modules = App::getModules();

        $result = [];
        /**
         * @var \skeeks\cms\Module $module
         */
        foreach ($modules as $key => $module)
        {
            //Каждый модуль добавляет свои пункты меню
            $result = array_merge($result, $module->getAdminMenuItems());
        }

        $result = array_merge($result, App::getAdminMenuItems());

        return $result;
    }

    /**
     * @param array $data
     * @return string
     */
    public function createUrl(array $data)
    {
        $data["namespace"] = "admin";
        return \Yii::$app->urlManager->createUrl($data);
    }

}