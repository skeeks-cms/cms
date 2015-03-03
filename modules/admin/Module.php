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

use skeeks\cms\base\Module as CmsModule;
use skeeks\cms\App;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Site;
use skeeks\cms\modules\admin\assets\AdminAsset;
use skeeks\cms\modules\admin\components\UrlRule;

/**
 * Class Module
 * @package skeeks\modules\cms\user
 */
class Module extends CmsModule
{
    //Скрывать кнопки действи сущьности
    const SYSTEM_QUERY_NO_ACTIONS_MODEL = 'no-actions';
    const SYSTEM_QUERY_EMPTY_LAYOUT     = 'sx-empty-layout';

    public $controllerNamespace = 'skeeks\cms\modules\admin\controllers';

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
            "name"          => "Админка cms",
            "description"   => "Модуль входит в состав модуля cms, содержит все необходимые элементы для админки",
        ]);
    }

    public $noImage = '';

    public function init()
    {
        parent::init();

        if (!$this->noImage)
        {
            $this->noImage = \Yii::$app->getAssetManager()->getAssetUrl(AdminAsset::register(\Yii::$app->view), "images/no-photo.gif");
        }
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
        $modules = \Yii::$app->cms->getModules();

        $result = [];
        /**
         * @var \skeeks\cms\Module $module
         */
        foreach ($modules as $key => $module)
        {
            //Каждый модуль добавляет свои пункты меню
            $result = array_merge($result, $module->getAdminMenuItems());
        }

        $result = array_merge($result, \Yii::$app->cms->getAdminMenuItems());

        return $result;
    }

    /**
     * @param array $data
     * @return string
     */
    public function createUrl(array $data)
    {
        $data[UrlRule::ADMIN_PARAM_NAME] = UrlRule::ADMIN_PARAM_VALUE;
        return \Yii::$app->urlManager->createUrl($data);
    }

    /**
     * @return bool
     */
    public function requestIsAdmin()
    {
        $request = \Yii::$app->request;
        $urlRuleAdmin = new UrlRule();
        $pathInfo       = $request->getPathInfo();
        $params         = $request->getQueryParams();
        $firstPrefix    = substr($pathInfo, 0, strlen($urlRuleAdmin->adminPrefix));

        if ($firstPrefix == $urlRuleAdmin->adminPrefix)
        {
            return true;
        }

        return false;
    }


    /**
     * @return null|\skeeks\cms\models\Lang
     */
    public function getCurrentLang()
    {
        $langId = (string) \Yii::$app->getSession()->get('lang');

        if ($langId)
        {
            return \Yii::$app->langs->getComponent($langId);
        }

        return null;
    }

    /**
     * @return null|Site
     */
    public function getCurrentSite()
    {
        $siteId = \Yii::$app->getSession()->get('site');

        if ($siteId)
        {
            return Site::findById($siteId);
        }

        return null;
    }


    /**
     * @param string $route
     * @return string
     */
    public function getPermissionCode($route = '')
    {
        return $route;
    }

    public function getPermissionCodeOwn($route = '')
    {
        return $this->getPermissionCode($route) . '/own';
    }
}