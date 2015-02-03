<?php
/**
 * Breadcrumbs
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 15.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\widgets\breadcrumbs;

use skeeks\cms\base\Widget;
use skeeks\cms\models\Publication;
use skeeks\cms\models\Tree;
use skeeks\cms\widgets\WidgetHasTemplate;
use Yii;

/**
 * Class Breadcrumbs
 * @package skeeks\cms\widgets\breadcrumbs
 */
class Breadcrumbs extends \skeeks\cms\widgets\base\hasTemplate\WidgetHasTemplate
{
    /**
     * @var null|string
     */
    public $title                   = '';

    /**
     * Подготовка данных для шаблона
     * @return $this
     */
    public function bind()
    {
        $this->_data->set('data',       \Yii::$app->breadcrumbs->parts);
        $this->_data->set('component',  \Yii::$app->breadcrumbs);

        return $this;
    }
}
