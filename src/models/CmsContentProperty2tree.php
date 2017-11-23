<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_content_property2tree".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $cms_content_property_id
 * @property integer $cms_tree_id
 *
 * @property CmsContentProperty $cmsContentProperty
 * @property CmsTree $cmsTree
 */
class CmsContentProperty2tree extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_property2tree}}';
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_content_id' => Yii::t('skeeks/cms', 'Linked to content'),
            'cms_content_property_id' => Yii::t('skeeks/cms', 'Linked to content'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                ['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_content_property_id', 'cms_tree_id'],
                'integer'
            ],
            [['cms_content_property_id', 'cms_tree_id'], 'required'],
            [
                ['cms_content_property_id', 'cms_tree_id'],
                'unique',
                'targetAttribute' => ['cms_content_property_id', 'cms_tree_id'],
                'message' => 'The combination of Cms Content Property ID and Cms Tree ID has already been taken.'
            ],
            [
                ['cms_content_property_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => CmsContentProperty::className(),
                'targetAttribute' => ['cms_content_property_id' => 'id']
            ],
            [
                ['cms_tree_id'],
                'exist',
                'skipOnError' => true,
                'targetClass' => CmsTree::className(),
                'targetAttribute' => ['cms_tree_id' => 'id']
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty()
    {
        return $this->hasOne(CmsContentProperty::className(), ['id' => 'cms_content_property_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTree()
    {
        return $this->hasOne(CmsTree::className(), ['id' => 'cms_tree_id']);
    }

}