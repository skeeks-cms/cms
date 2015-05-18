<?php
/**
 * TODO: is depricated (1.2.0)
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 03.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;

/**
 * Class HasPageUrl
 * @package skeeks\cms\models\behaviors
 */
abstract class HasPageUrl extends ActiveRecord
{
    /**
     * @return string
     */
    abstract public function createUrl();

    /**
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl();
    }
}