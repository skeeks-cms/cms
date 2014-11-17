<?php
/**
 * HasMultiLangAndSiteFields
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 18.11.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models\behaviors;
use skeeks\cms\base\behaviors\ActiveRecord;

/**
 * Class HasPageOptions
 * @package skeeks\cms\models\behaviors
 */
class HasMultiLangAndSiteFields extends ActiveRecord
{
    const DEFAULT_VALUE_SECTION = '_';
    /**
     * @var string
     */
    public $fields = [];

    /**
     * @param \skeeks\cms\base\db\ActiveRecord $owner
     * @throws \skeeks\cms\Exception
     */
    public function attach($owner)
    {
        $owner->attachBehavior("serialize_multi_lang_and_site_fields", [
            "class"  => Serialize::className(),
            "fields" => $this->fields
        ]);

        parent::attach($owner);
    }



}