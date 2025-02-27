<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\queries\CmsContentQuery;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_content}}".
 *
 * @property integer              $id
 * @property integer|null         $created_by
 * @property integer|null         $updated_by
 * @property integer|null         $created_at
 * @property integer|null         $updated_at
 *
 * @property string               $name
 * @property string               $code
 * @property integer              $priority
 * @property string               $description
 * @property string               $content_type
 *
 * @property bool                 $is_tree_only_max_level Разрешено привязывать только к разделам, без подразделов
 * @property bool                 $is_tree_only_no_redirect Разрешено привязывать только к разделам, не редирректам
 * @property bool                 $is_tree_required Раздел необходимо выбирать обязательно
 * @property bool                 $is_tree_allow_change Разраешено менять раздел при редактировании
 *
 * @property string               $index_for_search
 * @property string               $tree_chooser
 * @property string               $list_mode
 * @property string               $name_meny
 * @property string               $name_one
 * @property integer              $default_tree_id
 * @property integer              $is_allow_change_tree
 * @property integer              $is_active
 * @property integer              $is_count_views
 * @property integer              $root_tree_id
 * @property string               $view_file
 * @property integer              $is_access_check_element
 * @property array                $editable_fields
 * @property integer              $cms_tree_type_id
 * @property integer              $saved_filter_tree_type_id
 * @property string|null          $base_role
 *
 * @property string               $meta_title_template
 * @property string               $meta_description_template
 * @property string               $meta_keywords_template
 *
 * @property integer              $parent_content_id
 * @property integer              $is_visible
 * @property string               $parent_content_on_delete
 * @property integer              $is_parent_content_required
 * @property integer              $is_have_page
 * @property integer              $is_show_on_all_sites
 *
 * ***
 *
 * @property string               $adminPermissionName
 *
 * @property CmsTree              $rootTree
 * @property CmsTree              $defaultTree
 * @property CmsContentType       $contentType
 * @property CmsContentElement[]  $cmsContentElements
 * @property CmsContentProperty[] $cmsContentProperties
 *
 * @property CmsContent           $parentContent
 * @property CmsContent[]         $childrenContents
 * @property CmsTreeType          $savedFilterTreeType
 * @property CmsTreeType          $cmsTreeType
 */
class CmsContent extends Core
{
    const CASCADE = 'CASCADE';
    const RESTRICT = 'RESTRICT';
    const SET_NULL = 'SET_NULL';

    const ROLE_PRODUCTS = "products";

    /**
     * @param string|null $code
     * @return string|string[]
     */
    static public function baseRoles(string $code = null)
    {
        $roles = [
            self::ROLE_PRODUCTS    => "Товары",
        ];

        if ($code === null) {
            return $roles;
        }

        return (string)ArrayHelper::getValue($roles, $code);
    }

    /**
     * @return array
     */
    public static function getOnDeleteOptions()
    {
        return [
            self::CASCADE  => "CASCADE (".\Yii::t('skeeks/cms', 'Remove all items of that content').")",
            self::RESTRICT => "RESTRICT (".\Yii::t('skeeks/cms',
                    'Deny delete parent is not removed, these elements').")",
            self::SET_NULL => "SET NULL (".\Yii::t('skeeks/cms', 'Remove the connection to a remote parent').")",
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content}}';
    }

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [

            HasJsonFieldsBehavior::class => [
                'class'  => HasJsonFieldsBehavior::class,
                'fields' => ['editable_fields'],
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'is_show_on_all_sites'      => Yii::t('skeeks/cms', 'Если эта опция включена, то страница элемента этого контента, может отображаться на любом из сайтов.'),
            'is_have_page'              => Yii::t('skeeks/cms', 'Если эта опция включена, то показываются настройки SEO и URL'),
            'code'                      => Yii::t('skeeks/cms', 'The name of the template to draw the elements of this type will be the same as the name of the code.'),
            'view_file'                 => Yii::t('skeeks/cms', 'The path to the template. If not specified, the pattern will be the same code.'),
            'root_tree_id'              => Yii::t('skeeks/cms', 'If it is set to the root partition, the elements can be tied to him and his sub.'),
            'editable_fields'           => Yii::t('skeeks/cms', 'Поля которые отображаются при редактировании. Если ничего не выбрано, то показываются все!'),
            'cms_tree_type_id'          => Yii::t('skeeks/cms', 'Этот параметр ограничевает возможность привязки элементов этого контента к разделам только этого типа'),
            'saved_filter_tree_type_id' => Yii::t('skeeks/cms', 'Элементы этого контента не имеют самостоятельной страницы а создают посадочную на корневой раздел этого типа'),
            'base_role'                 => Yii::t('skeeks/cms', 'Базовый сценарий определяет поведение этого контента'),
            'is_tree_only_max_level'    => Yii::t('skeeks/cms', 'То есть разрешено привязывать элементы к разделам у которых нет подразделов'),
            'is_tree_only_no_redirect'    => Yii::t('skeeks/cms', 'Если раздел является редирректом на другой раздел или ссылку, то к такому разделу нельзя привязывать элементы'),
            'is_tree_required'    => Yii::t('skeeks/cms', 'Нельзя просто создать элемент, не выбрав его категорию-раздел'),
            'is_tree_allow_change'    => Yii::t('skeeks/cms', 'Эта настройка касается только редактирования. По хорошему, раздел должен выбираться у элемента только в момент создания! Потому что от этого зависят и характеристики элемента.'),
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'cms_tree_type_id'          => Yii::t('skeeks/cms', 'Привязывать только к разделам этого типа'),
            'saved_filter_tree_type_id' => Yii::t('skeeks/cms', 'Тип раздела для посадочной страницы'),
            'id'                        => Yii::t('skeeks/cms', 'ID'),
            'created_by'                => Yii::t('skeeks/cms', 'Created By'),
            'updated_by'                => Yii::t('skeeks/cms', 'Updated By'),
            'created_at'                => Yii::t('skeeks/cms', 'Created At'),
            'updated_at'                => Yii::t('skeeks/cms', 'Updated At'),
            'name'                      => Yii::t('skeeks/cms', 'Name'),
            'code'                      => Yii::t('skeeks/cms', 'Code'),
            'is_active'                 => Yii::t('skeeks/cms', 'Active'),
            'priority'                  => Yii::t('skeeks/cms', 'Priority'),
            'description'               => Yii::t('skeeks/cms', 'Description'),
            'content_type'              => Yii::t('skeeks/cms', 'Меню'),
            'index_for_search'          => Yii::t('skeeks/cms', 'To index for search module'),
            'tree_chooser'              => Yii::t('skeeks/cms', 'The Interface Binding Element to Sections'),
            'list_mode'                 => Yii::t('skeeks/cms', 'View Mode Sections And Elements'),
            'name_meny'                 => Yii::t('skeeks/cms', 'The Name Of The Elements (Plural)'),
            'name_one'                  => Yii::t('skeeks/cms', 'The Name One Element'),
            'is_tree_only_max_level'    => Yii::t('skeeks/cms', 'Привязывать элементы только к разделам максимального уровня?'),
            'is_tree_only_no_redirect'    => Yii::t('skeeks/cms', 'Разрешено привязывать только к разделам - не редирректам'),
            'is_tree_required'    => Yii::t('skeeks/cms', 'Раздел выбирать обязательно'),
            'is_tree_allow_change'    => Yii::t('skeeks/cms', 'Разраешено менять раздел при редактировании'),

            'base_role' => Yii::t('skeeks/cms', 'Базовый сценарий'),

            'default_tree_id'      => Yii::t('skeeks/cms', 'Default Section'),
            'is_allow_change_tree' => Yii::t('skeeks/cms', 'Is Allow Change Default Section'),
            'is_count_views'       => Yii::t('skeeks/cms', 'Считать количество просмотров?'),
            'root_tree_id'         => Yii::t('skeeks/cms', 'Root Section'),
            'view_file'            => Yii::t('skeeks/cms', 'Template'),

            'meta_title_template'       => Yii::t('skeeks/cms', 'Шаблон META TITLE'),
            'meta_description_template' => Yii::t('skeeks/cms', 'Шаблон META KEYWORDS'),
            'meta_keywords_template'    => Yii::t('skeeks/cms', 'Шаблон META DESCRIPTION'),

            'is_access_check_element' => Yii::t('skeeks/cms', 'Включить управление доступом к элементам'),
            'parent_content_id'       => Yii::t('skeeks/cms', 'Parent content'),

            'is_visible'                 => Yii::t('skeeks/cms', 'Show in menu'),
            'parent_content_on_delete'   => Yii::t('skeeks/cms', 'At the time of removal of the parent element'),
            'is_parent_content_required' => Yii::t('skeeks/cms', 'Parent element is required to be filled'),

            'is_have_page' => Yii::t('skeeks/cms', 'У элементов есть страница на сайте.'),

            'editable_fields'      => Yii::t('skeeks/cms', 'Редактируемые поля'),
            'is_show_on_all_sites' => Yii::t('skeeks/cms', 'Показывать элементы на всех сайтах?'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                ['created_by', 'updated_by', 'created_at', 'updated_at', 'priority', 'default_tree_id', 'root_tree_id', 'is_count_views', 'is_allow_change_tree', 'is_active'],
                'integer',
            ],
            [['is_visible'], 'integer'],
            [['cms_tree_type_id'], 'integer'],
            [['saved_filter_tree_type_id'], 'integer'],
            [['is_parent_content_required'], 'integer'],
            [['is_have_page'], 'integer'],
            [['is_show_on_all_sites'], 'integer'],
            [['name'], 'required'],
            [['description'], 'string'],
            [['meta_title_template'], 'string'],
            [['meta_description_template'], 'string'],
            [['meta_keywords_template'], 'string'],
            [['name', 'view_file'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 50],
            [['code'], 'unique'],

            [['base_role'], 'unique'],
            [['base_role'], 'string'],
            [['base_role'], 'default', 'value' => null],

            [['is_access_check_element'], 'integer'],
            [['code'], 'validateCode'],
            [['index_for_search', 'tree_chooser', 'list_mode'], 'string', 'max' => 1],
            [['content_type'], 'string', 'max' => 32],
            [['name_meny', 'name_one'], 'string', 'max' => 100],
            ['priority', 'default', 'value' => 500],
            ['is_active', 'default', 'value' => 1],
            ['is_allow_change_tree', 'default', 'value' => 1],
            ['is_access_check_element', 'default', 'value' => 0],
            ['name_meny', 'default', 'value' => function () {
                return $this->name;
            }],
            ['name_one', 'default', 'value' => function () {
                return $this->name;
            }],


            [
                [
                    'is_tree_only_max_level',
                    'is_tree_only_no_redirect',
                    'is_tree_required',
                    'is_tree_allow_change'
                ]
                , 'integer'],
            ['is_visible', 'default', 'value' => 1],
            ['is_have_page', 'default', 'value' => 1],
            ['is_parent_content_required', 'default', 'value' => 0],

            [[
                'is_tree_only_no_redirect',
                'is_tree_only_max_level',
                'is_tree_allow_change',
            ], 'default', 'value' => 1],
            [[
                'is_tree_required',
            ], 'default', 'value' => 0],

            ['parent_content_on_delete', 'default', 'value' => self::CASCADE],

            ['parent_content_id', 'integer'],

            [
                'code',
                'default',
                'value' => function ($model, $attribute) {
                    return "sxauto".md5(rand(1, 10).time());
                },
            ],

            [['editable_fields'], 'safe'],
            [['content_type'], 'default', 'value' => null],
            //[['editable_fields'], 'default', 'value' => null],


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

        $query = CmsContent::find()->andWhere(['code' => null]);
        if ($contentQueryCallback && is_callable($contentQueryCallback)) {
            $contentQueryCallback($query);
        }

        static::$_selectData["Прочее"] = ArrayHelper::map($query->all(), 'id', 'name');
        
        $otherContents = CmsContent::find()->andWhere(['content_type' => null])->all();
        if ($otherContents) {
            static::$_selectData = ArrayHelper::merge(static::$_selectData, ArrayHelper::map($otherContents, 'id', 'name'));
        }

        return static::$_selectData;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRootTree()
    {
        return $this->hasOne(CmsTree::class, ['id' => 'root_tree_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDefaultTree()
    {
        return $this->hasOne(CmsTree::class, ['id' => 'default_tree_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getContentType()
    {
        return $this->hasOne(CmsContentType::class, ['code' => 'content_type']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSavedFilterTreeType()
    {
        return $this->hasOne(CmsTreeType::class, ['id' => 'saved_filter_tree_type_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeType()
    {
        return $this->hasOne(CmsTreeType::class, ['id' => 'cms_tree_type_id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::class, ['content_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    /*public function getCmsContentProperties()
    {
        return $this->hasMany(CmsContentProperty::class, ['content_id' => 'id'])->orderBy(['priority' => SORT_ASC]);
    }*/


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty2contents()
    {
        return $this->hasMany(CmsContentProperty2content::class, ['cms_content_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperties()
    {
        $q = CmsContentProperty::find();

        $q->innerJoinWith("cmsContentProperty2contents as cmsContentProperty2contents");
        $q->andWhere(["cmsContentProperty2contents.cms_content_id" => $this->id]);
        $q->orderBy("priority");

        $q->multiple = true;

        return $q;

        /*return $this
            ->hasMany(CmsContentProperty::class, ['id' => 'cms_content_property_id'])
                ->via('cmsContentProperty2contents')
            ->orderBy('priority');*/
    }


    /**
     * @return string
     */
    public function getAdminPermissionName()
    {
        return 'cms/admin-cms-content-element__'.$this->id;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParentContent()
    {
        return $this->hasOne(CmsContent::class, ['id' => 'parent_content_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildrenContents()
    {
        return $this->hasMany(CmsContent::class, ['parent_content_id' => 'id']);
    }

    /**
     * @return CmsContentElement
     */
    public function createElement()
    {
        return new CmsContentElement([
            'content_id'  => $this->id,
            'cms_site_id' => \Yii::$app->skeeks->site->id,
        ]);
    }


    /**
     * Разрешено редактировать поле?
     *
     * @param $code
     * @return bool
     */
    public function isAllowEdit($code)
    {
        if (!$this->editable_fields) {
            return true;
        }

        return (bool)in_array((string)$code, (array)$this->editable_fields);
    }

    /**
     * @return CmsContentQuery
     */
    public static function find()
    {
        return (new CmsContentQuery(get_called_class()));
    }
}