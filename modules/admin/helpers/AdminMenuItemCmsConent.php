<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\modules\admin\helpers;
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
}