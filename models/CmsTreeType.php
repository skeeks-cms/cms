<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\traits\ValidateRulesTrait;
use Yii;

/**
 * This is the model class for table "{{%cms_tree_type}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $code
 * @property string $active
 * @property integer $priority
 * @property string $description
 * @property string $index_for_search
 * @property string $name_meny
 * @property string $name_one
 * @property string $viewFile
 * @property integer $default_children_tree_type
 *
 * @property CmsTree[] $cmsTrees
 * @property CmsTreeTypeProperty[] $cmsTreeTypeProperties
 * @property CmsTreeType $defaultChildrenTreeType
 */
class CmsTreeType extends Core
{
    use ValidateRulesTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree_type}}';
    }

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
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code'),
            'active' => Yii::t('app', 'Active'),
            'priority' => Yii::t('app', 'Priority'),
            'description' => Yii::t('app', 'Description'),
            'index_for_search' => Yii::t('app', 'To index for search module'),
            'name_meny' => Yii::t('app', 'Name Meny'),
            'name_one' => Yii::t('app', 'Name One'),
            'viewFile' => Yii::t('app', 'Template'),
            'default_children_tree_type' => Yii::t('app', 'Type of child partitions by default'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority', 'default_children_tree_type'], 'integer'],
            [['name', 'code'], 'required'],
            [['description'], 'string'],
            [['name', 'viewFile'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['active', 'index_for_search'], 'string', 'max' => 1],
            [['name_meny', 'name_one'], 'string', 'max' => 100],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            ['priority', 'default', 'value'         => 500],
            ['active', 'default', 'value'           => "Y"],
            ['name_meny', 'default', 'value'        => \Yii::t('app','Sections')],
            ['name_one', 'default', 'value'         => \Yii::t('app','Section')],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTrees()
    {
        return $this->hasMany(CmsTree::className(), ['tree_type_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeTypeProperties()
    {
        return $this->hasMany(CmsTreeTypeProperty::className(), ['tree_type_id' => 'id'])->orderBy(['priority' => SORT_ASC]);;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDefaultChildrenTreeType()
    {
        return $this->hasOne(CmsTreeType::className(), ['id' => 'default_children_tree_type']);
    }
}