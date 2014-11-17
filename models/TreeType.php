<?php
/**
 * TreeType
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 11.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Model;

/**
 * Class TreeType
 * @package skeeks\cms\models
 */
class TreeType extends Model
{
    public $id;
    public $name;

    /**
     * @var string
     */
    public $layout;

    /**
     * @var string
     */
    public $template;


    /**
     * @return null|Layout
     */
    public function getLayout()
    {
        if ($this->layout)
        {
            return \Yii::$app->registeredLayouts->getComponent($this->layout);
        }

        return null;
    }

    /**
     * @return null|TreeType
     */
    public function getTemplate()
    {
        if ($this->template)
        {
            return \Yii::$app->treeTypes->getComponent($this->template);
        }

        return null;
    }
}