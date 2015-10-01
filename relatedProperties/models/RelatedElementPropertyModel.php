<?php
/**
 * Модель связанного свойства.
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 18.05.2015
 */
namespace skeeks\cms\relatedProperties\models;

use skeeks\cms\components\Cms;
use skeeks\cms\models\Core;
use Yii;
use yii\db\BaseActiveRecord;
use yii\widgets\ActiveForm;

/**
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
 * @property RelatedPropertyModel $property
 * @property RelatedElementModel  $element
 */
abstract class RelatedElementPropertyModel extends Core
{
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id' => Yii::t('app', 'ID'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'property_id' => Yii::t('app', 'Property ID'),
            'element_id' => Yii::t('app', 'Element ID'),
            'value' => Yii::t('app', 'Value'),
            'value_enum' => Yii::t('app', 'Value Enum'),
            'value_num' => Yii::t('app', 'Value Num'),
            'description' => Yii::t('app', 'Description'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'property_id', 'element_id', 'value_enum'], 'integer'],
            [['value'], 'required'],
            [['value_num'], 'number'],
            [['description'], 'string', 'max' => 255],
            [['value'], 'string']
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    abstract public function getProperty();
    /*{
        return $this->hasOne(CmsContentProperty::className(), ['id' => 'property_id']);
    }*/

    /**
     * @return \yii\db\ActiveQuery
     */
    abstract public function getElement();
    /*{
        return $this->hasOne(CmsContentElement::className(), ['id' => 'element_id']);
    }*/
}