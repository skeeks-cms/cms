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
 * @property CmsContent[] $cmsContents
 * @property CmsContentProperty2content[] $cmsContentProperty2contents
 * 
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
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            \skeeks\cms\behaviors\RelationalBehavior::class,
        ]);
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
    public function getEnums()
    {
        return $this->hasMany(CmsContentPropertyEnum::className(), ['property_id' => 'id']);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cmsContents' => Yii::t('skeeks/cms', 'Linked to content'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = ArrayHelper::merge(parent::rules(), [
            [['content_id'], 'integer'],
            [['cmsContents'], 'safe'],
            [['code'], 'unique'],
            //[['code', 'content_id'], 'unique', 'targetAttribute' => ['content_id', 'code'], 'message' => \Yii::t('skeeks/cms','For the content of this code is already in use.')],
        ]);

        return $rules;
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty2contents()
    {
        return $this->hasMany(CmsContentProperty2content::className(), ['cms_content_property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContents()
    {
        return $this->hasMany(CmsContent::className(), ['id' => 'cms_content_id'])->viaTable('cms_content_property2content', ['cms_content_property_id' => 'id']);
    }
}