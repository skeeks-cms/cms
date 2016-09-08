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
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_content_property}}".
 *
 * @property integer $content_id
 *
 * @property CmsContent $cmsContent
 * @property CmsContentPropertyEnum[] $enums
 * @property CmsContentElementProperty[] $elementProperties
 */
class CmsContentProperty extends RelatedPropertyModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_property}}';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getElementProperties()
    {
        return $this->hasMany(CmsContentElementProperty::className(), ['property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContent()
    {
        return $this->hasOne(CmsContent::className(), ['id' => 'content_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnums()
    {
        return $this->hasMany(CmsContentPropertyEnum::className(), ['property_id' => 'id']);
    }


    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'content_id' => Yii::t('skeeks/cms', 'Linked to content'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = ArrayHelper::merge(parent::rules(), [
            [['content_id'], 'integer'],
            [['code', 'content_id'], 'unique', 'targetAttribute' => ['content_id', 'code'], 'message' => \Yii::t('skeeks/cms','For the content of this code is already in use.')],
        ]);

        return $rules;
    }
}