<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\relatedProperties\models\RelatedPropertyModel;

/**
 * This is the model class for table "{{%cms_content_property}}".
 *
 * @property CmsUserUniversalPropertyEnum[] $enums
 * @property CmsUserProperty[] $elementProperties
 */
class CmsUserUniversalProperty extends RelatedPropertyModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_universal_property}}';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getElementProperties()
    {
        return $this->hasMany(CmsUserProperty::className(), ['property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnums()
    {
        return $this->hasMany(CmsUserUniversalPropertyEnum::className(), ['property_id' => 'id']);
    }


    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['code'], 'unique'],
        ]);
    }
}