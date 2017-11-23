<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 29.07.2016
 */

namespace skeeks\cms\models;

use skeeks\cms\relatedProperties\models\RelatedPropertyEnumModel;

/**
 * This is the model class for table "{{%cms_content_property_enum}}".
 *
 * @property CmsContentProperty $property
 */
class CmsContentPropertyEnum extends RelatedPropertyEnumModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_property_enum}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), []);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProperty()
    {
        return $this->hasOne(CmsContentProperty::className(), ['id' => 'property_id']);
    }
}