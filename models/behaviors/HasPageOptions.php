<?php
/**
 * HasPageOptions
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 17.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;
use skeeks\cms\models\Lang;
use skeeks\cms\models\Site;

/**
 * Class HasPageOptions
 * @package skeeks\cms\models\behaviors
 */
class HasPageOptions extends ActiveRecord
{
    /**
     * @var string
     */
    public $fieldName = 'page_options';

    /**
     * Разрешенные опции
     * @var array
     */
    public $enabledOptions  = [];

    /**
     * Запрещенные опции
     * @var array
     */
    public $disabledOptions = [];

    /**
     * @param \skeeks\cms\base\db\ActiveRecord $owner
     * @throws \skeeks\cms\Exception
     */
    public function attach($owner)
    {
        $owner->attachBehavior(HasMultiLangAndSiteFields::className(), [
            "class"  => HasMultiLangAndSiteFields::className(),
            "fields" => [$this->fieldName]
        ]);

        parent::attach($owner);
    }

    /**
     * @return array
     */
    public function getMultiPageOptions()
    {
        return (array) $this->owner->getMultiFieldValue($this->fieldName);
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMultiPageOptions($value)
    {
        return $this->owner->setMultiFieldValue($this->fieldName, $value);
    }


    /**
     * @param $id
     * @return null
     */
    public function getPageOptionValue($id)
    {
        $options = $this->owner->getMultiPageOptions();
        if (isset($options[$id]))
        {
            return $options[$id];
        }

        return null;
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasPageOptionValue($id)
    {
        $options = $this->owner->getMultiPageOptions();
        if (isset($options[$id]))
        {
            return true;
        }

        return false;
    }

}