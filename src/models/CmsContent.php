<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\components\Cms;
use Yii;
use yii\helpers\ArrayHelper;

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
 * @property string $content_type
 * @property string $index_for_search
 * @property string $tree_chooser
 * @property string $list_mode
 * @property string $name_meny
 * @property string $name_one
 * @property integer $default_tree_id
 * @property string $is_allow_change_tree
 * @property integer $is_count_views
 * @property integer $root_tree_id
 * @property string $view_file
 * @property string $access_check_element
 *
 * @property string $meta_title_template
 * @property string $meta_description_template
 * @property string $meta_keywords_template
 *
 * @property integer $parent_content_id
 * @property string $visible
 * @property string $parent_content_on_delete
 * @property string $parent_content_is_required
 *
 * ***
 *
 * @property string $adminPermissionName
 *
 * @property CmsTree $rootTree
 * @property CmsTree $defaultTree
 * @property CmsContentType $contentType
 * @property CmsContentElement[] $cmsContentElements
 * @property CmsContentProperty[] $cmsContentProperties
 *
 * @property CmsContent $parentContent
 * @property CmsContent[] $childrenContents
 */
class CmsContent extends Core
{
    const CASCADE = 'CASCADE';
    const RESTRICT = 'RESTRICT';
    const SET_NULL = 'SET_NULL';

    /**
     * @return array
     */
    public static function getOnDeleteOptions()
    {
        return [
            self::CASCADE => "CASCADE (" . \Yii::t('skeeks/cms', 'Remove all items of that content') . ")",
            self::RESTRICT => "RESTRICT (" . \Yii::t('skeeks/cms',
                    'Deny delete parent is not removed, these elements') . ")",
            self::SET_NULL => "SET NULL (" . \Yii::t('skeeks/cms', 'Remove the connection to a remote parent') . ")",
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content}}';
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
            'content_type' => Yii::t('skeeks/cms', 'Content Type'),
            'index_for_search' => Yii::t('skeeks/cms', 'To index for search module'),
            'tree_chooser' => Yii::t('skeeks/cms', 'The Interface Binding Element to Sections'),
            'list_mode' => Yii::t('skeeks/cms', 'View Mode Sections And Elements'),
            'name_meny' => Yii::t('skeeks/cms', 'The Name Of The Elements (Plural)'),
            'name_one' => Yii::t('skeeks/cms', 'The Name One Element'),

            'default_tree_id' => Yii::t('skeeks/cms', 'Default Section'),
            'is_allow_change_tree' => Yii::t('skeeks/cms', 'Is Allow Change Default Section'),
            'is_count_views' => Yii::t('skeeks/cms', 'Считать количество просмотров?'),
            'root_tree_id' => Yii::t('skeeks/cms', 'Root Section'),
            'view_file' => Yii::t('skeeks/cms', 'Template'),

            'meta_title_template' => Yii::t('skeeks/cms', 'Шаблон META TITLE'),
            'meta_description_template' => Yii::t('skeeks/cms', 'Шаблон META KEYWORDS'),
            'meta_keywords_template' => Yii::t('skeeks/cms', 'Шаблон META DESCRIPTION'),

            'access_check_element' => Yii::t('skeeks/cms', 'Включить управление доступом к элементам'),
            'parent_content_id' => Yii::t('skeeks/cms', 'Parent content'),

            'visible' => Yii::t('skeeks/cms', 'Show in menu'),
            'parent_content_on_delete' => Yii::t('skeeks/cms', 'At the time of removal of the parent element'),
            'parent_content_is_required' => Yii::t('skeeks/cms', 'Parent element is required to be filled'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['created_by', 'updated_by', 'created_at', 'updated_at', 'priority', 'default_tree_id', 'root_tree_id', 'is_count_views'],
                'integer'
            ],
            [['name', 'content_type'], 'required'],
            [['description'], 'string'],
            [['meta_title_template'], 'string'],
            [['meta_description_template'], 'string'],
            [['meta_keywords_template'], 'string'],
            [['name', 'view_file'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['code'], 'unique'],
            [['access_check_element'], 'string'],
            [['code'], 'validateCode'],
            [['active', 'index_for_search', 'tree_chooser', 'list_mode', 'is_allow_change_tree'], 'string', 'max' => 1],
            [['content_type'], 'string', 'max' => 32],
            [['name_meny', 'name_one'], 'string', 'max' => 100],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            ['is_allow_change_tree', 'default', 'value' => Cms::BOOL_Y],
            ['access_check_element', 'default', 'value' => Cms::BOOL_N],
            ['name_meny', 'default', 'value' => Yii::t('skeeks/cms', 'Elements')],
            ['name_one', 'default', 'value' => Yii::t('skeeks/cms', 'Element')],


            ['visible', 'default', 'value' => Cms::BOOL_Y],
            ['parent_content_is_required', 'default', 'value' => Cms::BOOL_Y],
            ['parent_content_on_delete', 'default', 'value' => self::CASCADE],

            ['parent_content_id', 'integer'],

            [
                'code',
                'default',
                'value' => function($model, $attribute) {
                    return "sxauto" . md5(rand(1, 10) . time());
                }
            ],
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


    static protected $_selectData = [];

    /**
     * Данные для мультиселекта с группами типов
     *
     * @param bool|false $refetch
     * @return array
     */
    public static function getDataForSelect($refetch = false, $contentQueryCallback = null)
    {
        if ($refetch === false && static::$_selectData) {
            return static::$_selectData;
        }

        static::$_selectData = [];

        if ($cmsContentTypes = CmsContentType::find()->orderBy("priority ASC")->all()) {
            /**
             * @var $cmsContentType CmsContentType
             */
            foreach ($cmsContentTypes as $cmsContentType) {
                $query = $cmsContentType->getCmsContents();
                if ($contentQueryCallback && is_callable($contentQueryCallback)) {
                    $contentQueryCallback($query);
                }

                static::$_selectData[$cmsContentType->name] = ArrayHelper::map($query->all(), 'id', 'name');
            }
        }

        return static::$_selectData;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRootTree()
    {
        return $this->hasOne(CmsTree::className(), ['id' => 'root_tree_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDefaultTree()
    {
        return $this->hasOne(CmsTree::className(), ['id' => 'default_tree_id']);
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
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::className(), ['content_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    /*public function getCmsContentProperties()
    {
        return $this->hasMany(CmsContentProperty::className(), ['content_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }*/


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty2contents()
    {
        return $this->hasMany(CmsContentProperty2content::className(), ['cms_content_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperties()
    {
        return $this->hasMany(CmsContentProperty::className(),
            ['id' => 'cms_content_property_id'])
            ->via('cmsContentProperty2contents')
            //->viaTable('cms_content_property2content', ['cms_content_id' => 'id'])
            ->orderBy('priority')
            ;
    }


    /**
     * @return string
     */
    public function getAdminPermissionName()
    {
        return 'cms/admin-cms-content-element__' . $this->id;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentContent()
    {
        return $this->hasOne(CmsContent::className(), ['id' => 'parent_content_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildrenContents()
    {
        return $this->hasMany(CmsContent::className(), ['parent_content_id' => 'id']);
    }

    /**
     * @return CmsContentElement
     */
    public function createElement()
    {
        return new CmsContentElement([
            'content_id' => $this->id
        ]);
    }
}