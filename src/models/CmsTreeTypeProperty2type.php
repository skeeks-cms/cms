<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link https://skeeks.com/
 * @copyright (c) 2010 SkeekS
 * @date 15.05.2017
 */

namespace skeeks\cms\models;

/**
 * This is the model class for table "cms_tree_type_property2type".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $cms_tree_type_property_id
 * @property integer $cms_tree_type_id
 *
 * @property CmsTreeTypeProperty $cmsTreeTypeProperty
 * @property CmsTreeType $cmsTreeType
 */
class CmsTreeTypeProperty2type extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree_type_property2type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                [
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at',
                    'cms_tree_type_property_id',
                    'cms_tree_type_id'
                ],
                'integer'
            ],
            [['cms_tree_type_property_id', 'cms_tree_type_id'], 'required'],
            [
                ['cms_tree_type_property_id', 'cms_tree_type_id'],
                'unique',
                'targetAttribute' => ['cms_tree_type_property_id', 'cms_tree_type_id'],
                'message' => 'The combination of Cms Tree Type Property ID and Cms Tree Type ID has already been taken.'
            ],
            [
                ['cms_tree_type_property_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => CmsTreeTypeProperty::className(),
                'targetAttribute' => ['cms_tree_type_property_id' => 'id']
            ],
            [
                ['cms_tree_type_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => CmsTreeType::className(),
                'targetAttribute' => ['cms_tree_type_id' => 'id']
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'id' => 'ID',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'cms_tree_type_property_id' => 'Cms Tree Type Property ID',
            'cms_tree_type_id' => 'Cms Tree Type ID',
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeTypeProperty()
    {
        return $this->hasOne(CmsTreeTypeProperty::className(), ['id' => 'cms_tree_type_property_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeType()
    {
        return $this->hasOne(CmsTreeType::className(), ['id' => 'cms_tree_type_id']);
    }
}