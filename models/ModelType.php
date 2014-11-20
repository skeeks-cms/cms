<?php
/**
 * ModelType
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 20.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Model;

/**
 * Class ModelType
 * @package skeeks\cms\models
 */
class ModelType extends ComponentModel
{
    /**
     * @var string
     */
    public $layout;

    /**
     * @var string
     */
    public $actionView;


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
     * @return null|ModelType
     */
    /*public function getActionView()
    {
        if ($this->actionView)
        {
            return \Yii::$app->treeTypes->getComponent($this->template);
        }

        return null;
    }*/
}