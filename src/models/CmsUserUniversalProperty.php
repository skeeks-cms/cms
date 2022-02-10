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
use yii\helpers\ArrayHelper;

/**
 * @property CmsSite $cmsSite
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
     * @inheritdoc
     */
    public function rules()
    {
        $rules = ArrayHelper::merge(parent::rules(), [
            [['cms_site_id'], 'integer'],
            [['cms_site_id'], 'default', 'value' => function() {
                if (\Yii::$app->skeeks->site) {
                    return \Yii::$app->skeeks->site->id;
                }
            }],

            [['code', 'cms_site_id'], 'unique', 'targetAttribute' => ['code', 'cms_site_id']],
        ]);

        return $rules;
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
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        $class = \Yii::$app->skeeks->siteClass;
        return $this->hasOne($class, ['id' => 'cms_site_id']);
    }

}