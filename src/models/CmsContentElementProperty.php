<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\relatedProperties\models\RelatedElementPropertyModel;

/**
 * This is the model class for table "{{%cms_content_element_property}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $property_id
 * @property integer $element_id
 * @property string $value
 * @property integer $value_enum
 * @property string $value_num
 * @property string $description
 *
 * @property CmsContentProperty $property
 * @property CmsContentElement $element
 *
 * @property CmsContentElement $valueCmsContentElements
 */
class CmsContentElementProperty extends RelatedElementPropertyModel
{
    public $elementClass = CmsContentElement::class;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_element_property}}';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProperty()
    {
        return $this->hasOne(CmsContentProperty::class, ['id' => 'property_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getElement()
    {
        $class = $this->elementClass;
        return $this->hasOne($class, ['id' => 'element_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValueCmsContentElement()
    {
        $class = $this->elementClass;
        return $this->hasOne($class, ['id' => 'value_element_id']);
    }
}