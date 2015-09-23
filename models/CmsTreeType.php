<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */
namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\traits\ValidateRulesTrait;
use skeeks\modules\cms\user\models\User;
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
 *
 * @property CmsTree[] $cmsTrees
 * @property CmsTreeTypeProperty[] $cmsTreeTypeProperties
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
            'index_for_search' => Yii::t('app', 'Index For Search'),
            'name_meny' => Yii::t('app', 'Name Meny'),
            'name_one' => Yii::t('app', 'Name One'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['name', 'code'], 'required'],
            [['description'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['active', 'index_for_search'], 'string', 'max' => 1],
            [['name_meny', 'name_one'], 'string', 'max' => 100],
            [['code'], 'unique'],
            [['code'], 'validateCode'],
            ['priority', 'default', 'value'         => 500],
            ['active', 'default', 'value'           => "Y"],
            ['name_meny', 'default', 'value'        => "Разделы"],
            ['name_one', 'default', 'value'         => "Раздел"],
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
        return $this->hasMany(CmsTreeTypeProperty::className(), ['tree_type_id' => 'id'])->orderBy(['priority' => SORT_DESC]);;
    }
}