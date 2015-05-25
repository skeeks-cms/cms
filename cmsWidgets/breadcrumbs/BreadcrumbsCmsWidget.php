<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.05.2015
 */

namespace skeeks\cms\cmsWidgets\breadcrumbs;

use skeeks\cms\base\Widget;
use skeeks\cms\helpers\UrlHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Class breadcrumbs
 * @package skeeks\cms\cmsWidgets\Breadcrumbs
 */
class BreadcrumbsCmsWidget extends Widget
{
    public $viewFile    = null;

    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => 'Хлебные крошки'
        ]);
    }

    public $text = '';

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(),
        [
            'viewFile'  => 'Файл-шаблон',
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(),
        [
            [['viewFile'], 'string'],
        ]);
    }

    protected function _run()
    {
        return $this->text;
    }

}