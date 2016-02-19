<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\modules\admin\helpers;
use skeeks\cms\models\CmsContent;
use skeeks\cms\modules\admin\assets\AdminAsset;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class AdminMenuItem
 * @package skeeks\cms\modules\admin\helpers
 */
class AdminMenuItemCmsConent extends AdminMenuItem
{
    /**
     * @return bool
     */
    public function isActive()
    {
        /*if (!parent::isActive())
        {
            return false;
        }*/

        if (is_array($this->url))
        {
            if ($content_id = ArrayHelper::getValue($this->url, 'content_id'))
            {
                if ($content_id == \Yii::$app->request->get("content_id"))
                {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Разрешены ли привелегии
     * @return bool
     */
    public function isPermissionCan()
    {
        if (is_array($this->url))
        {
            $controller = null;

            try
            {
                /**
                 * @var $controller \yii\web\Controller
                 */
                list($controller, $route) = \Yii::$app->createController($this->url[0]);
            } catch (\Exception $e)
            {}


            if (!$controller)
            {
                return true;
            }


            if ($content_id = ArrayHelper::getValue($this->url, 'content_id'))
            {
                $controller->content = CmsContent::findOne($content_id);
            }

            if ($permission = \Yii::$app->authManager->getPermission($controller->permissionName))
            {
                if (\Yii::$app->user->can($permission->name))
                {
                    return $this->_accessCallback();
                } else
                {
                    return false;
                }
            } else
            {
                return $this->_accessCallback();
            }
        }

        return $this->_accessCallback();
    }
}