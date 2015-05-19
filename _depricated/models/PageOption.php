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
     * @var array
     */
    public $defaultValue;


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
        return new $modelValueClassName($this->defaultValue);
    }

    /**
     * @var PageOptionValue
     */
    protected $_value = null;


    /**
     * @return PageOptionValue
     */
    public function getValue()
    {
        if ($this->_value === null)
        {
            $this->_value = $this->createModelValue();
        }

        return $this->_value;
    }

}