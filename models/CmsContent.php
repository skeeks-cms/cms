<?php
/**
 * Infoblock
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\base\Widget;
use skeeks\cms\components\registeredWidgets\Model;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasFiles;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRef;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\modules\cms\user\models\User;
use Yii;

/**
 * This is the model class for table "{{%cms_content}}".
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
 * @property string $files
 * @property string $content_type
 * @property string $index_element
 * @property string $index_tree
 * @property string $tree_chooser
 * @property string $list_mode
 * @property string $trees_name
 * @property string $tree_name
 * @property string $elements_name
 * @property string $element_name
 *
 * @property CmsContentType $contentType
 * @property User $createdBy
 * @property User $updatedBy
 * @property CmsContentElement[] $cmsContentElements
 */
class CmsContent extends Core
{
    use \skeeks\cms\models\behaviors\traits\HasFiles;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasFiles::className() => HasFiles::className(),
        ]);
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
            'files' => Yii::t('app', 'Files'),
            'content_type' => Yii::t('app', 'Content Type'),
            'index_element' => Yii::t('app', 'Индексировать элементы для модуля поиска'),
            'index_tree' => Yii::t('app', 'Индексировать разделы для модуля поиска'),
            'tree_chooser' => Yii::t('app', 'Интерфейс привязки элемента к разделам'),
            'list_mode' => Yii::t('app', 'Режим просмотра разделов и элементов'),
            'trees_name' => Yii::t('app', 'Разделы'),
            'tree_name' => Yii::t('app', 'Раздел'),
            'elements_name' => Yii::t('app', 'Элементы'),
            'element_name' => Yii::t('app', 'Элемент'),
        ]);
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();

        $scenarios['create'] = $scenarios[self::SCENARIO_DEFAULT];
        $scenarios['update'] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'priority'], 'integer'],
            [['name', 'content_type'], 'required'],
            [['description', 'files'], 'string'],
            [['name'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['active', 'index_element', 'index_tree', 'tree_chooser', 'list_mode'], 'string', 'max' => 1],
            [['content_type'], 'string', 'max' => 32],
            [['trees_name', 'tree_name', 'elements_name', 'element_name'], 'string', 'max' => 100],
            ['code', 'default', 'value' => function($model, $attribute)
            {
                return "sx_auto_" . md5(rand(1, 10) . time());
            }],
            ['priority', 'default', 'value' => function($model, $attribute)
            {
                return 500;
            }],
            ['active', 'default', 'value' => function($model, $attribute)
            {
                return "Y";
            }],
            ['trees_name', 'default', 'value' => function($model, $attribute)
            {
                return "Разделы";
            }],
            ['tree_name', 'default', 'value' => function($model, $attribute)
            {
                return "Раздел";
            }],
            ['elements_name', 'default', 'value' => function($model, $attribute)
            {
                return "Элементы";
            }],
            ['element_name', 'default', 'value' => function($model, $attribute)
            {
                return "Элемент";
            }],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContentType()
    {
        return $this->hasOne(CmsContentType::className(), ['code' => 'content_type']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCreatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::className(), ['content_id' => 'id']);
    }
}