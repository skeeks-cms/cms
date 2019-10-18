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

/**
 * This is the model class for table "{{%cms_content_property}}".
 *
 * @property integer $tree_type_id
 *
 * ***
 * @property CmsTreeProperty[] $cmsTreeProperties
 * @property CmsTreeTypeProperty2type[] $cmsTreeTypeProperty2types
 * @property CmsTreeType[] $cmsTreeTypes
 *
 * @property CmsTreeType $treeType
 * @property CmsTreeTypePropertyEnum[] $enums
 * @property CmsTreeProperty[] $elementProperties
 */
class CmsTreeTypeProperty extends RelatedPropertyModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree_type_property}}';
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
        return $this->hasMany(CmsTreeProperty::className(), ['property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnums()
    {
        return $this->hasMany(CmsTreeTypePropertyEnum::className(), ['property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTreeType()
    {
        return $this->hasOne(CmsTreeType::className(), ['id' => 'tree_type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeTypeProperty2types()
    {
        return $this->hasMany(CmsTreeTypeProperty2type::className(), ['cms_tree_type_property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeTypes()
    {
        return $this->hasMany(CmsTreeType::className(),
            ['id' => 'cms_tree_type_id'])->viaTable('cms_tree_type_property2type',
            ['cms_tree_type_property_id' => 'id']);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'cmsTreeTypes' => Yii::t('skeeks/cms', "Linked To Section's Type"),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['tree_type_id'], 'integer'],
            [['cmsTreeTypes'], 'safe'],
            //[['code'], 'unique'],
            [
                ['code', 'tree_type_id'],
                'unique',
                'targetAttribute' => ['tree_type_id', 'code'],
                'message' => \Yii::t('skeeks/cms', "For this section's type of the code is already in use.")
            ],
        ]);
    }

    public function asText()
    {
        $result = parent::asText();
        return $result . " ($this->code)";
    }
}