<?php
/**
 * Publication
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 31.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use paulzi\adjacencyList\AdjacencyListBehavior;
use paulzi\autotree\AutoTreeTrait;
use paulzi\materializedPath\MaterializedPathBehavior;
use skeeks\cms\components\Cms;
use skeeks\cms\components\urlRules\UrlRuleTree;
use skeeks\cms\models\behaviors\CanBeLinkedToTree;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\behaviors\traits\TreeBehaviorTrait;
use skeeks\cms\models\behaviors\TreeBehavior;
use skeeks\yii2\slug\SlugRuleProvider;
use skeeks\yii2\yaslug\YaSlugHelper;
use Yii;
use yii\base\Event;
use yii\base\Exception;
use yii\db\ActiveQuery;
use yii\db\AfterSaveEvent;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%cms_tree}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $name
 * @property string $description_short
 * @property string $description_full
 * @property string $code
 * @property integer $pid
 * @property string $pids
 * @property integer $level
 * @property string $dir
 * @property integer $priority
 * @property string $tree_type_id
 * @property integer $published_at
 * @property string $redirect
 * @property string $active
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 * @property string $cms_site_id
 * @property string $description_short_type
 * @property string $description_full_type
 * @property integer $image_full_id
 * @property integer $image_id
 * @property integer $redirect_tree_id
 * @property integer $redirect_code
 * @property string $name_hidden
 * @property string $view_file
 * @property string $seo_h1
 * @property string|null $external_id
 *
 * ***
 *
 * @property string $fullName
 *
 * @property string $absoluteUrl
 * @property string $url
 *
 * @property CmsStorageFile $image
 * @property CmsStorageFile $fullImage
 *
 * @property CmsTreeFile[] $cmsTreeFiles
 * @property CmsTreeImage[] $cmsTreeImages
 * @property CmsTree $redirectTree
 *
 * @property CmsStorageFile[] $files
 * @property CmsStorageFile[] $images
 *
 * @property CmsContentElement[] $cmsContentElements
 * @property CmsContentElementTree[] $cmsContentElementTrees
 * @property CmsSite $site
 * @property CmsSite $cmsSiteRelation
 * @property CmsTreeType $treeType
 * @property CmsTreeProperty[] $cmsTreeProperties
 *
 * @property CmsContentProperty2tree[] $cmsContentProperty2trees
 * @property CmsContentProperty[] $cmsContentProperties
 *
 * @property string                      $seoName
 * 
 * @property Tree $parent
 * @property Tree[] $parents
 * @property Tree[] $children
 * @property Tree[] $activeChildren
 * @property Tree $root
 * @property Tree $prev
 * @property Tree $next
 * @property Tree[] $descendants
 * 
 * @depricated
 */
class Tree extends Core
{
    use HasRelatedPropertiesTrait;
    use AutoTreeTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_tree}}';
    }

    const PRIORITY_STEP = 100; //Шаг приоритета


    public function behaviors()
    {
        $behaviors = parent::behaviors();

        return ArrayHelper::merge(parent::behaviors(), [

            HasStorageFile::className() =>
                [
                    'class' => HasStorageFile::className(),
                    'fields' => ['image_id', 'image_full_id']
                ],

            HasStorageFileMulti::className() =>
                [
                    'class' => HasStorageFileMulti::className(),
                    'relations' => [
                        [
                            'relation' => 'images',
                            'property' => 'imageIds'
                        ],
                        [
                            'relation' => 'files',
                            'property' => 'fileIds'
                        ],
                    ]
                ],

            HasRelatedProperties::className() =>
                [
                    'class' => HasRelatedProperties::className(),
                    'relatedElementPropertyClassName' => CmsTreeProperty::className(),
                    'relatedPropertyClassName' => CmsTreeTypeProperty::className(),
                ],

            [
                'class' => AdjacencyListBehavior::className(),
                'parentAttribute' => 'pid',
                'sortable' => [
                    'sortAttribute' => 'priority'
                ],
                /*'parentsJoinLevels'  => 0,
                'childrenJoinLevels' => 0,
                'sortable'           => false,*/
            ],

            [
                'class' => MaterializedPathBehavior::className(),
                'pathAttribute' => 'pids',
                'depthAttribute' => 'level',
                'sortable' => [
                    'sortAttribute' => 'priority'
                ],
            ],
        ]);
    }

    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_INSERT, [$this, '_updateCode']);
        $this->on(self::EVENT_BEFORE_UPDATE, [$this, '_updateCode']);

        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterUpdateTree']);
        $this->on(self::EVENT_BEFORE_DELETE, [$this, 'beforeDeleteTree']);
    }


    /**
     * Если есть дети для начала нужно удалить их всех
     * @param Event $event
     * @throws \Exception
     */
    public function beforeDeleteTree(Event $event)
    {
        if ($children = $this->getChildren()->all()) {
            foreach ($children as $childNode) {
                $childNode->delete();
            }
        }
    }

    public function _updateCode(Event $event)
    {
        $parent = $this->getParent()->one();
        //У корневой ноды всегда нет кода
        if ($this->isRoot()) {
            $this->code = null;
            $this->dir = null;
        } else {
            if (!$this->code) {
                $this->_generateCode();
            }

            $this->dir = $this->code;

            if ($this->level > 1) {
                $parent = $this->getParent()->one();
                $this->dir = $parent->dir . "/" . $this->code;
            }
        }

        //site code
        if ($parent) {
            $this->cms_site_id = $parent->cms_site_id;
        } elseif (!$this->cms_site_id) {
            if ($site = \Yii::$app->currentSite->site) {
                $this->cms_site_id = $site->code;
            }
        }
        //tree type
        if (!$this->tree_type_id) {
            if ($this->parent && $this->parent->treeType) {
                if ($this->parent->treeType->defaultChildrenTreeType) {
                    $this->tree_type_id = $this->parent->treeType->defaultChildrenTreeType->id;
                } else {
                    $this->tree_type_id = $this->parent->tree_type_id;
                }
            } else {

                if ($treeType = CmsTreeType::find()->orderBy(['priority' => SORT_ASC])->one()) {
                    $this->tree_type_id = $treeType->id;
                }
            }
        }
    }

    /**
     * Изменился код
     * @param AfterSaveEvent $event
     */
    public function afterUpdateTree(AfterSaveEvent $event)
    {
        if ($event->changedAttributes) {
            //Если изменилось название seo_page_name
            if (isset($event->changedAttributes['code'])) {

                $event->sender->processNormalize();
            }
            //Если изменилось название seo_page_name
            if (isset($event->changedAttributes['pid'])) {
                $event->sender->processNormalize();
            }
        }
    }


    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'seo_h1' => 'Заголовок будет показан на детальной странице, в случае если его использование задано в шаблоне.',
            'external_id' => 'Это поле чаще всего задействуют программисты, для интеграций со сторонними системами'
        ]);
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
            'published_at' => Yii::t('skeeks/cms', 'Published At'),
            'published_to' => Yii::t('skeeks/cms', 'Published To'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'name' => Yii::t('skeeks/cms', 'Name'),
            'tree_type_id' => Yii::t('skeeks/cms', 'Type'),
            'redirect' => Yii::t('skeeks/cms', 'Redirect'),
            'priority' => Yii::t('skeeks/cms', 'Priority'),
            'code' => Yii::t('skeeks/cms', 'Code'),
            'active' => Yii::t('skeeks/cms', 'Active'),
            'meta_title' => Yii::t('skeeks/cms', 'Meta Title'),
            'meta_keywords' => Yii::t('skeeks/cms', 'Meta Keywords'),
            'meta_description' => Yii::t('skeeks/cms', 'Meta Description'),
            'description_short' => Yii::t('skeeks/cms', 'Description Short'),
            'description_full' => Yii::t('skeeks/cms', 'Description Full'),
            'description_short_type' => Yii::t('skeeks/cms', 'Description Short Type'),
            'description_full_type' => Yii::t('skeeks/cms', 'Description Full Type'),
            'image_id' => Yii::t('skeeks/cms', 'Main Image (announcement)'),
            'image_full_id' => Yii::t('skeeks/cms', 'Main Image'),
            'images' => Yii::t('skeeks/cms', 'Images'),
            'imageIds' => Yii::t('skeeks/cms', 'Images'),
            'files' => Yii::t('skeeks/cms', 'Files'),
            'fileIds' => Yii::t('skeeks/cms', 'Files'),
            'redirect_tree_id' => Yii::t('skeeks/cms', 'Redirect Section'),
            'redirect_code' => Yii::t('skeeks/cms', 'Redirect Code'),
            'name_hidden' => Yii::t('skeeks/cms', 'Hidden Name'),
            'view_file' => Yii::t('skeeks/cms', 'Template'),
            'seo_h1' => Yii::t('skeeks/cms', 'SEO заголовок h1'),
            'external_id' => Yii::t('skeeks/cms', 'ID из внешней системы'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['description_short', 'description_full'], 'string'],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            [['redirect_code'], 'default', 'value' => 301],
            [['redirect_code'], 'in', 'range' => [301, 302]],
            [['redirect'], 'string'],
            [['name_hidden'], 'string'],
            [['priority', 'tree_type_id', 'redirect_tree_id', 'redirect_code'], 'integer'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 255],
            [['seo_h1'], 'string', 'max' => 255],
            [['external_id'], 'string', 'max' => 255],
            [['external_id'], 'default', 'value' => null],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],
            [['cms_site_id'], 'integer'],
            [
                ['pid', 'code'],
                'unique',
                'targetAttribute' => ['pid', 'code'],
                'message' => \Yii::t('skeeks/cms', 'For this subsection of the code is already in use.')
            ],
            [
                ['pid', 'code'],
                'unique',
                'targetAttribute' => ['pid', 'code'],
                'message' => \Yii::t('skeeks/cms', 'The combination of Code and Pid has already been taken.')
            ],

            ['description_short_type', 'string'],
            ['description_full_type', 'string'],
            ['description_short_type', 'default', 'value' => "text"],
            ['description_full_type', 'default', 'value' => "text"],
            ['view_file', 'string', 'max' => 128],

            [['image_id', 'image_full_id'], 'safe'],
            [
                ['image_id', 'image_full_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles' => 1,
                'maxSize' => 1024 * 1024 * 10,
                'minSize' => 1024,
            ],
            [['imageIds', 'fileIds'], 'safe'],
            [
                ['imageIds'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions' => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles' => 40,
                'maxSize' => 1024 * 1024 * 10,
                'minSize' => 1024,
            ],
            [
                ['fileIds'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                //'extensions'    => [''],
                'maxFiles' => 40,
                'maxSize' => 1024 * 1024 * 50,
                'minSize' => 1024,
            ],

            [
                ['name'],
                'default',
                'value' => function(self $model) {
                    $lastTree = static::find()->orderBy(["id" => SORT_DESC])->one();
                    if ($lastTree) {
                        return "pk-" . $lastTree->primaryKey;
                    }

                    return 'root';
                }
            ],

        ]);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRedirectTree()
    {
        return $this->hasOne(CmsTree::className(), ['id' => 'redirect_tree_id']);
    }

    /**
     *
     * Корневые разделы дерева.
     *
     * @return ActiveQuery
     */
    public static function findRoots()
    {
        return static::find()->where(['level' => 0])->orderBy(["priority" => SORT_ASC]);
    }


    /**
     * @return string
     */
    public function getUrl($scheme = false, $params = [])
    {
        UrlRuleTree::$models[$this->id] = $this;

        if ($params) {
            $params = ArrayHelper::merge(['/cms/tree/view', 'id' => $this->id], $params);
        } else {
            $params = ['/cms/tree/view', 'id' => $this->id];
        }

        return Url::to(['/cms/tree/view', 'id' => $this->id], $scheme);
    }

    /**
     * @return string
     */
    public function getAbsoluteUrl($params = [])
    {
        return $this->getUrl(true, $params);
    }

    /**
     * @return CmsSite
     */
    public function getSite()
    {
        //return $this->hasOne(CmsSite::className(), ['id' => 'cms_site_id']);
        return CmsSite::getById($this->cms_site_id);
    }

    /**
     * @return ActiveQuery
     */
    public function getCmsSiteRelation()
    {
        return $this->hasOne(CmsSite::className(), ['id' => 'cms_site_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElements()
    {
        return $this->hasMany(CmsContentElement::className(), ['tree_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementTrees()
    {
        return $this->hasMany(CmsContentElementTree::className(), ['tree_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeProperties()
    {
        return $this->hasMany(CmsTreeProperty::className(), ['element_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTreeType()
    {
        return $this->hasOne(CmsTreeType::className(), ['id' => 'tree_type_id']);
    }


    static protected $_treeTypes = [];
    /**
     * Все возможные свойства связанные с моделью
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRelatedProperties()
    {
        //return $this->treeType->getCmsTreeTypeProperties();
        if (isset(self::$_treeTypes[$this->tree_type_id])) {
            $treeType = self::$_treeTypes[$this->tree_type_id];
        } else {
            self::$_treeTypes[$this->tree_type_id] = $this->treeType;
            $treeType = self::$_treeTypes[$this->tree_type_id];
        }
        if (!$treeType) {
            return CmsTreeTypeProperty::find()->where(['id' => null]);
        }
        //return $this->treeType->getCmsTreeTypeProperties();
        return $treeType->getCmsTreeTypeProperties();
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(StorageFile::className(), ['id' => 'image_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFullImage()
    {
        return $this->hasOne(StorageFile::className(), ['id' => 'image_full_id']);
    }


    protected $_image_ids = null;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function setImageIds($ids)
    {
        $this->_image_ids = $ids;
        return $this;
    }

    /**
     * @return array
     */
    public function getImageIds()
    {
        if ($this->_image_ids !== null) {
            return $this->_image_ids;
        }

        if ($this->images) {
            return ArrayHelper::map($this->images, 'id', 'id');
        }

        return [];
    }

    protected $_file_ids = null;

    /**
     * @return \yii\db\ActiveQuery
     */
    public function setFileIds($ids)
    {
        $this->_file_ids = $ids;
        return $this;
    }

    /**
     * @return array
     */
    public function getFileIds()
    {
        if ($this->_file_ids !== null) {
            return $this->_file_ids;
        }

        if ($this->files) {
            return ArrayHelper::map($this->files, 'id', 'id');
        }

        return [];
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsTreeImages')
            ->orderBy(['priority' => SORT_ASC])
            ;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsTreeFiles')
            ->orderBy(['priority' => SORT_ASC])
            ;
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeFiles()
    {
        return $this->hasMany(CmsTreeFile::className(), ['tree_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTreeImages()
    {
        return $this->hasMany(CmsTreeImage::className(), ['tree_id' => 'id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty2trees()
    {
        return $this->hasMany(CmsContentProperty2tree::className(), ['cms_tree_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperties()
    {
        return $this->hasMany(CmsContentProperty::class,
            ['id' => 'cms_content_property_id'])->viaTable('cms_content_property2tree', ['cms_tree_id' => 'id'])
            ->orderBy([CmsContentProperty::tableName() . ".priority" => SORT_ASC]);
    }


    /**
     * @return $this
     */
    protected function _generateCode()
    {
        if ($this->isRoot()) {
            $this->code = null;
            return $this;
        }

        $this->code = YaSlugHelper::slugify($this->name);

        if (strlen($this->code) < 2) {
            $this->code = $this->code . "-" . md5(microtime());
        }

        if (strlen($this->code) > \Yii::$app->cms->tree_max_code_length) {
            $this->code = substr($this->code, 0, \Yii::$app->cms->tree_max_code_length);
        }

        $matches = [];
        //Роутинг элементов нужно исключить
        if (preg_match('/(?<id>\d+)\-(?<code>\S+)$/i', $this->code, $matches)) {
            $this->code = "s" . $this->code;
        }

        if (!$this->_isValidCode()) {
            $this->code = YaSlugHelper::slugify($this->code . "-" . substr(md5(uniqid() . time()), 0, 4));

            if (!$this->_isValidCode()) {
                return $this->_generateCode();
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function _isValidCode()
    {
        if (!$this->parent) {
            return true;
        }

        $find = $this->parent->getChildren()
            ->where([
                "code" => $this->code,
                'pid' => $this->pid
            ]);

        if (!$this->isNewRecord) {
            $find->andWhere([
                "!=",
                'id',
                $this->id
            ]);
        }

        if ($find->one()) {
            return false;
        }

        return true;
    }


    /**
     *
     * Обновление всего дерева ниже, и самого элемента.
     * Если найти всех рутов дерева и запустить этот метод, то дерево починиться в случае поломки
     * правильно переустановятся все dir, pids и т.д.
     *
     * @return $this
     */
    public function processNormalize()
    {
        if ($this->isRoot()) {
            $this->setAttribute("dir", null);
            $this->save(false);
        } else {
            $this->setAttribute('dir', $this->code);

            if ($this->level > 1) {
                $parent = $this->getParent()->one();
                $this->setAttribute('dir', $parent->dir . "/" . $this->code);
            }

            if (!$this->save()) {
                throw new Exception('Not update dir');
            }
        }


        //Берем детей на один уровень ниже
        $childrens = $this->getChildren()->all();
        if ($childrens) {
            foreach ($childrens as $childModel) {
                $childModel->processNormalize();
            }
        }

        return $this;
    }


    /**
     * @return bool
     * @deprecated
     */
    public function gethas_children()
    {
        return (bool)$this->children;
    }

    /**
     * @param string $glue
     *
     * @return string
     */
    public function getFullName($glue = " / ")
    {
        $paths = [];

        if ($this->parents) {
            foreach ($this->parents as $parent) {
                if ($parent->isRoot()) {
                    $paths[] = "[" . $parent->site->name . "] " . $parent->name;
                } else {
                    $paths[] = $parent->name;
                }
            }
        }

        $paths[] = $this->name;

        return implode($glue, $paths);
    }

    /**
     * @return ActiveQuery
     */
    public function getActiveChildren()
    {
        return $this->getChildren()->active();
    }
    
    /**
     * Полное название
     * 
     * @return string
     */
    public function getSeoName()
    {
        $result = "";
        if ($this->seo_h1) {
            return $this->seo_h1;
        } else {
            return $this->name;
        }
    }
    
}



