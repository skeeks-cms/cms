<?php

namespace skeeks\cms\models;

/**
 * This is the model class for table "{{%cms_content_element2cms_user}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $cms_user_id
 * @property integer $cms_content_element_id
 *
 * @property CmsContentElement $cmsContentElement
 * @property CmsUser $cmsUser
 */
class CmsContentElement2cmsUser extends \skeeks\cms\models\Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_element2cms_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_user_id', 'cms_content_element_id'],
                'integer'
            ],
            [['cms_user_id', 'cms_content_element_id'], 'required'],
            [
                ['cms_user_id', 'cms_content_element_id'],
                'unique',
                'targetAttribute' => ['cms_user_id', 'cms_content_element_id'],
                'message' => 'The combination of Cms User ID and Cms Content Element ID has already been taken.'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'cms_user_id' => 'Cms User ID',
            'cms_content_element_id' => 'Cms Content Element ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElement()
    {
        return $this->hasOne(CmsContentElement::className(), ['id' => 'cms_content_element_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(CmsUser::className(), ['id' => 'cms_user_id']);
    }
}