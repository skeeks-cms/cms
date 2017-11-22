<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\breadcrumbs;

use skeeks\cms\base\WidgetRenderable;

/**
 * Class breadcrumbs
 * @package skeeks\cms\cmsWidgets\Breadcrumbs
 */
class BreadcrumbsCmsWidget extends WidgetRenderable
{
    public static function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/cms', 'Breadcrumbs')
        ]);
    }
}