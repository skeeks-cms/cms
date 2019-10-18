<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */

namespace skeeks\cms\models;

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
 * ***
 *
 * @property CmsTree[] $cmsTrees
 * @property CmsTreeType $defaultChildrenTreeType
 *
 * @property CmsTreeType[] $cmsTreeTypes
 * @property CmsTreeTypeProperty2type[] $cmsTreeTypeProperty2types
 * @property CmsTreeTypeProperty[] $cmsTreeTypeProperties
 */
class CmsTreeType extends Core
{
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
            'id' => Yii::t('skeeks/cms', 'ID'),
            'created_by' => Yii::t('skeeks/cms', 'Created By'),
            'updated_by' => Yii::t('skeeks/cms', 'Updated By'),
            'created_at' => Yii::t('skeeks/cms', 'Created At'),
            'updated_at' => Yii::t('skeeks/cms', 'Updated At'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'code' => Yii::t('skeeks/cms', 'Code'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'description' => Yii::t('skeeks/cms', 'Description'),
            'index_for_search' => Yii::t('skeeks/cms', 'To index for search module'),
            'name_meny' => Yii::t('skeeks/cms', 'Name of many sections'),
            'name_one' => Yii::t('skeeks/cms', 'Name of one section'),
            'view_file' => Yii::t('skeeks/cms', 'Template'),
            'default_children_tree_type' => Yii::t('skeeks/cms', 'Type of child partitions by default'),
        ]);
    }
    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'code' => \Yii::t('skeeks/cms', 'The name of the template to draw the elements of this type will be the same as the name of the code.'),
            'view_file' => \Yii::t('skeeks/cms', 'The path to the template. If not specified, the pattern will be the same code.'),
            'default_children_tree_type' => \Yii::t('skeeks/cms', 'If this parameter is not specified, the child partition is created of the same type as the current one.'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['created_by', 'updated_by', 'created_at', 'updated_at', 'priority', 'default_children_tree_type'],
                'integer'
            ],
            [['name', 'code'], 'required'],
            [['description'], 'string'],
            [['name', 'view_file'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['active', 'index_for_search'], 'string', 'max' => 1],
            [['name_meny', 'name_one'], 'string', 'max' => 100],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => "Y"],
            ['name_meny', 'default', 'value' => \Yii::t('skeeks/cms', 'Sections')],
            ['name_one', 'default', 'value' => \Yii::t('skeeks/cms', 'Section')],
        ]);
    }

    public function validateCode($attribute)
    {
        if (!preg_match('/^[a-zA-Z]{1}[a-zA-Z0-9-]{1,255}$/', $this->$attribute)) {
            $this->addError($attribute, \Yii::t('skeeks/cms',
                'Use only letters of the alphabet in lower or upper case and numbers, the first character of the letter (Example {code})',
                ['code' => 'code1']));
        }
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
        /*$query = CmsTreeTypeProperty::find()->joinWith('cmsTreeTypeProperty2types as cmsTreeTypeProperty2types')->andWhere(['cmsTreeTypeProperty2types.cms_tree_type_id' => $this->id]);
        $query->multiple = true;
        return $query;*/

        return $this->hasMany(CmsTreeTypeProperty::className(),
            ['id' => 'cms_tree_type_property_id'])->via('cmsTreeTypeProperty2types');

        //return $this->hasMany(CmsTreeTypeProperty::className(), ['tree_type_id' => 'id'])->orderBy(['priority' => SORT_ASC]);;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDefaultChildrenTreeType()
    {
        return $this->hasOne(CmsTreeType::className(), ['id' => 'default_children_tree_type']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeTypes()
    {
        return $this->hasMany(CmsTreeType::className(), ['default_children_tree_type' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeTypeProperty2types()
    {
        return $this->hasMany(CmsTreeTypeProperty2type::className(), ['cms_tree_type_id' => 'id']);
    }

    public function asText()
    {
        $result = parent::asText();
        return $result . " ($this->code)";
    }
}