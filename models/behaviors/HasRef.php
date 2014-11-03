<?php
/**
 * HasRef
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 04.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;
use skeeks\cms\Exception;
use skeeks\sx\models\Ref;


/**
 * Class HasLinkedModels
 * @package skeeks\cms\models\behaviors
 */
class HasRef extends ActiveRecord
{

    public $savedClassName = null;

    public function init()
    {
        parent::init();
    }

    /**
     * @return null|string
     */
    public function getSavedClassName()
    {
        if ($this->savedClassName === null)
        {
            return $this->owner->className();
        }

        return $this->savedClassName;
    }
    /**
     * Объект ссылка
     * @return Ref
     * @throws Exception
     */
    public function getRef()
    {
        if ($this->owner->primaryKey)
        {
            return new Ref($this->getSavedClassName(), $this->owner->primaryKey);
        }

        throw new Exception("Can't get a ref of the entity that is not saved yet.");
    }

    /**
     * Ссылка есть?
     * @return bool
     */
    public function hasRef()
    {
        return $this->owner->primaryKey ? true : false;
    }
}