<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.04.2015
 */

namespace skeeks\cms\relatedProperties\propertyTypes;

use skeeks\cms\relatedProperties\PropertyType;

/**
 * Class PropertyTypeFile
 * @package skeeks\cms\relatedProperties\propertyTypes
 */
class PropertyTypeFile extends PropertyType
{
    public $code = self::CODE_FILE;

    public function init()
    {
        parent::init();

        if (!$this->name) {
            $this->name = \Yii::t('skeeks/cms', 'File');
        }
    }
}