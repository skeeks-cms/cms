<?php
/**
 * PageOption
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Model;
use skeeks\cms\models\pageOption\PageOptionValue;

/**
 * Class PageOption
 * @package skeeks\cms\models
 */
class PageOption extends Model
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var mixed
     */
    public $value;


    /**
     * @var PageOptionValue
     */
    public $modelValueClass;


    /**
     * @return PageOptionValue
     */
    public function createModelValue()
    {
        if (!$this->modelValueClass)
        {
            $this->modelValueClass = PageOptionValue::className();
        }

        $modelValueClassName = $this->modelValueClass;
        return new $modelValueClassName($this->value);
    }

    /**
     * @var null|PageOptionValue
     */
    protected $_modelValue = null;

    /**
     * @return null|PageOptionValue
     */
    public function getModelValue()
    {
        if ($this->_modelValue === null)
        {
            $this->_modelValue = $this->createModelValue();
        }

        return $this->_modelValue;
    }
}