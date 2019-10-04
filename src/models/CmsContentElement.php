<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\components\Cms;
use skeeks\cms\components\urlRules\UrlRuleContentElement;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\behaviors\HasTrees;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\behaviors\traits\HasTreesTrait;
use skeeks\cms\models\behaviors\traits\HasUrlTrait;
use skeeks\cms\relatedProperties\models\RelatedElementModel;
use skeeks\yii2\yaslug\YaSlugBehavior;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%cms_content_element}}".
 *
 * @property integer                     $id
 * @property integer                     $created_by
 * @property integer                     $updated_by
 * @property integer                     $created_at
 * @property integer                     $updated_at
 * @property integer                     $published_at
 * @property integer                     $published_to
 * @property integer                     $priority
 * @property string                      $active
 * @property string                      $name
 * @property string                      $code
 * @property string                      $description_short
 * @property string                      $description_full
 * @property integer                     $content_id
 * @property integer                     $image_id
 * @property integer                     $image_full_id
 * @property integer                     $tree_id
 * @property integer                     $show_counter
 * @property integer                     $show_counter_start
 * @property string                      $meta_title
 * @property string                      $meta_description
 * @property string                      $meta_keywords
 * @property string                      $seo_h1
 *
 * @property integer                     $parent_content_element_id version > 2.4.8
 *
 *
 * @property string                      $permissionName
 *
 * @property string                      $description_short_type
 * @property string                      $description_full_type
 *
 * @property string                      $absoluteUrl
 * @property string                      $url
 *
 * @property CmsContent                  $cmsContent
 * @property Tree                        $cmsTree
 * @property CmsContentElementProperty[] $relatedElementProperties
 * @property CmsContentProperty[]        $relatedProperties
 * @property CmsContentElementTree[]     $cmsContentElementTrees
 * @property CmsContentElementProperty[] $cmsContentElementProperties
 * @property CmsContentProperty[]        $cmsContentProperties
 *
 * @property CmsStorageFile              $image
 * @property CmsStorageFile              $fullImage
 *
 * @property CmsContentElementFile[]     $cmsContentElementFiles
 * @property CmsContentElementImage[]    $cmsContentElementImages
 *
 * @property CmsStorageFile[]            $files
 * @property CmsStorageFile[]            $images
 *
 * @version > 2.4.8
 * @property CmsContentElement           $parentContentElement
 * @property CmsContentElement[]         $childrenContentElements
 *
 * @property CmsContentElement2cmsUser[] $cmsContentElement2cmsUsers
 * @property CmsUser[]                   $usersToFavorites
 * @property string                      $seoName
 *
 */
class CmsContentElement extends RelatedElementModel
{
    use HasRelatedPropertiesTrait;
    use HasTreesTrait;
    use HasUrlTrait;

    protected $_image_ids = null;
    protected $_file_ids = null;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_element}}';
    }
    public function init()
    {
        parent::init();

        $this->on(self::EVENT_BEFORE_DELETE, [$this, '_beforeDeleteE']);
        $this->on(self::EVENT_AFTER_DELETE, [$this, '_afterDeleteE']);
    }

    public function _beforeDeleteE($e)
    {
        //Если есть дочерние элементы
        if ($this->childrenContentElements) {
            //Удалить все дочерние элементы
            if ($this->cmsContent->parent_content_on_delete == CmsContent::CASCADE) {
                foreach ($this->childrenContentElements as $childrenElement) {
                    $childrenElement->delete();
                }
            }

            if ($this->cmsContent->parent_content_on_delete == CmsContent::RESTRICT) {
                throw new Exception("Для начала необходимо удалить вложенные элементы");
            }

            if ($this->cmsContent->parent_content_on_delete == CmsContent::SET_NULL) {
                foreach ($this->childrenContentElements as $childrenElement) {
                    $childrenElement->parent_content_element_id = null;
                    $childrenElement->save();
                }
            }

        }
    }
    public function _afterDeleteE($e)
    {
        if ($permission = \Yii::$app->authManager->getPermission($this->permissionName)) {
            \Yii::$app->authManager->remove($permission);
        }
    }
    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampPublishedBehavior::className() => TimestampPublishedBehavior::className(),

            HasStorageFile::className()      => [
                'class'  => HasStorageFile::className(),
                'fields' => ['image_id', 'image_full_id'],
            ],
            HasStorageFileMulti::className() => [
                'class'     => HasStorageFileMulti::className(),
                'relations' => [
                    [
                        'relation' => 'images',
                        'property' => 'imageIds',
                    ],
                    [
                        'relation' => 'files',
                        'property' => 'fileIds',
                    ],
                ],
            ],

            HasRelatedProperties::className() => [
                'class'                           => HasRelatedProperties::className(),
                'relatedElementPropertyClassName' => CmsContentElementProperty::className(),
                'relatedPropertyClassName'        => CmsContentProperty::className(),
            ],

            HasTrees::className() => [
                'class' => HasTrees::className(),
            ],

            YaSlugBehavior::class => [
                'class'         => YaSlugBehavior::class,
                'attribute'     => 'name',
                'slugAttribute' => 'code',
                'maxLength'     => \Yii::$app->cms->element_max_code_length,
            ],
        ]);
    }
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id'                     => Yii::t('skeeks/cms', 'ID'),
            'created_by'             => Yii::t('skeeks/cms', 'Created By'),
            'updated_by'             => Yii::t('skeeks/cms', 'Updated By'),
            'created_at'             => Yii::t('skeeks/cms', 'Created At'),
            'updated_at'             => Yii::t('skeeks/cms', 'Updated At'),
            'published_at'           => Yii::t('skeeks/cms', 'Published At'),
            'published_to'           => Yii::t('skeeks/cms', 'Published To'),
            'priority'               => Yii::t('skeeks/cms', 'Priority'),
            'active'                 => Yii::t('skeeks/cms', 'Active'),
            'name'                   => Yii::t('skeeks/cms', 'Name'),
            'code'                   => Yii::t('skeeks/cms', 'Code'),
            'description_short'      => Yii::t('skeeks/cms', 'Description Short'),
            'description_full'       => Yii::t('skeeks/cms', 'Description Full'),
            'content_id'             => Yii::t('skeeks/cms', 'Content'),
            'tree_id'                => Yii::t('skeeks/cms', 'The main section'),
            'show_counter'           => Yii::t('skeeks/cms', 'Show Counter'),
            'show_counter_start'     => Yii::t('skeeks/cms', 'Show Counter Start'),
            'meta_title'             => Yii::t('skeeks/cms', 'Meta Title'),
            'meta_keywords'          => Yii::t('skeeks/cms', 'Meta Keywords'),
            'meta_description'       => Yii::t('skeeks/cms', 'Meta Description'),
            'description_short_type' => Yii::t('skeeks/cms', 'Description Short Type'),
            'description_full_type'  => Yii::t('skeeks/cms', 'Description Full Type'),
            'image_id'               => Yii::t('skeeks/cms', 'Main Image (announcement)'),
            'image_full_id'          => Yii::t('skeeks/cms', 'Main Image'),

            'imageIds'                  => Yii::t('skeeks/cms', 'Images'),
            'fileIds'                   => Yii::t('skeeks/cms', 'Files'),
            'images'                    => Yii::t('skeeks/cms', 'Images'),
            'files'                     => Yii::t('skeeks/cms', 'Files'),
            'treeIds'                   => Yii::t('skeeks/cms', 'Additional sections'),
            'parent_content_element_id' => Yii::t('skeeks/cms', 'Parent element'),
            'show_counter'              => Yii::t('skeeks/cms', 'Number of views'),
            'seo_h1' => Yii::t('skeeks/cms', 'SEO заголовок h1'),
        ]);
    }
    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'treeIds' => Yii::t('skeeks/cms', 'You can specify some additional sections that will show your records.'),
            'seo_h1' => 'Заголовок будет показан на детальной странице, в случае если его использование задано в шаблоне.'
        ]);
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [
                [
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at',
                    'published_at',
                    'published_to',
                    'priority',
                    'content_id',
                    'tree_id',
                    'show_counter',
                    'show_counter_start',
                ],
                'integer',
            ],
            [['name'], 'required'],
            [['description_short', 'description_full'], 'string'],
            [['active'], 'string', 'max' => 1],
            [['name', 'code'], 'string', 'max' => 255],
            [['seo_h1'], 'string', 'max' => 255],
            [
                ['content_id', 'code'],
                'unique',
                'targetAttribute' => ['content_id', 'code'],
                'message'         => \Yii::t('skeeks/cms', 'For the content of this code is already in use.'),
            ],
            [
                ['tree_id', 'code'],
                'unique',
                'targetAttribute' => ['tree_id', 'code'],
                'message'         => \Yii::t('skeeks/cms', 'For this section of the code is already in use.'),
            ],
            [['treeIds'], 'safe'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],

            ['description_short_type', 'string'],
            ['description_full_type', 'string'],
            ['description_short_type', 'default', 'value' => "text"],
            ['description_full_type', 'default', 'value' => "text"],
            [
                'tree_id',
                'default',
                'value' => function () {
                    if ($this->cmsContent->defaultTree) {
                        return $this->cmsContent->defaultTree->id;
                    }
                },
            ],

            [['image_id', 'image_full_id'], 'safe'],
            [
                ['image_id', 'image_full_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 10,
                'minSize'     => 1024,
            ],
            [['imageIds', 'fileIds'], 'safe'],
            [
                ['imageIds'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png'],
                'maxFiles'    => 40,
                'maxSize'     => 1024 * 1024 * 10,
                'minSize'     => 1024,
            ],
            [
                ['fileIds'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                //'extensions'    => [''],
                'maxFiles'    => 40,
                'maxSize'     => 1024 * 1024 * 50,
                'minSize'     => 1024,
            ],


            ['parent_content_element_id', 'integer'],
            ['parent_content_element_id', 'validateParentContentElement'],
            [
                'parent_content_element_id',
                'required',
                'when'       => function (CmsContentElement $model) {

                    if ($model->cmsContent && $model->cmsContent->parentContent) {
                        return (bool)($model->cmsContent->parent_content_is_required == "Y");
                    }

                    return false;
                },
                'whenClient' => "function (attribute, value) {
                return $('#cmscontent-parent_content_is_required').val() == 'Y';
            }",
            ],

        ]);
    }
    /**
     * Валидация родительского элемента
     *
     * @param $attribute
     * @return bool
     */
    public function validateParentContentElement($attribute)
    {
        if (!$this->cmsContent) {
            return false;
        }

        if (!$this->cmsContent->parentContent) {
            return false;
        }

        if ($this->$attribute) {
            $contentElement = static::findOne($this->$attribute);
            if ($contentElement->cmsContent->id != $this->cmsContent->parentContent->id) {
                $this->addError($attribute,
                    \Yii::t('skeeks/cms', 'The parent must be a content element: «{contentName}».',
                        ['contentName' => $this->cmsContent->parentContent->name]));
            }
        }
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContent()
    {
        return $this->hasOne(CmsContent::className(), ['id' => 'content_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTree()
    {
        return $this->hasOne(Tree::className(), ['id' => 'tree_id']);
    }

    static public $_contents = [];
    /**
     * Все возможные свойства связанные с моделью
     * @return \yii\db\ActiveQuery
     */
    public function getRelatedProperties()
    {

        //return $this->treeType->getCmsTreeTypeProperties();
        if (isset(self::$_contents[$this->content_id])) {
            $cmsContent = self::$_contents[$this->content_id];
        } else {
            self::$_contents[$this->content_id] = $this->cmsContent;
            $cmsContent = self::$_contents[$this->content_id];
        }
        return $cmsContent->getCmsContentProperties();
        //return $this->cmsContent->getCmsContentProperties();

        //return $this->cmsContent->getCmsContentProperties();

        /*$query = $this->cmsContent->getCmsContentProperties();
        $query->joinWith('cmsContentProperty2trees as map2trees')
            ->andWhere(['map2trees.cms_tree_id' => $this->treeIds])
        ;

        $query->groupBy(CmsContentProperty::tableName() . ".id");
        return $query;

        $query = CmsContentProperty::find()
            ->from(CmsContentProperty::tableName() . ' AS property')
            ->joinWith('cmsContentProperty2contents as map2contents')
            ->joinWith('cmsContentProperty2trees as map2trees')
            ->andWhere(['map2contents.cms_content_id' => $this->content_id])
            ->all()
        ;*/
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementTrees()
    {
        return $this->hasMany(CmsContentElementTree::className(), ['element_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementProperties()
    {
        return $this->hasMany(CmsContentElementProperty::className(), ['element_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperties()
    {
        return $this->hasMany(CmsContentProperty::className(), ['id' => 'property_id'])
            ->via('cmsContentElementProperties');
    }
    /**
     * @return string
     */
    public function getAbsoluteUrl($scheme = false, $params = [])
    {
        return $this->getUrl(true, $params);
    }
    /**
     * @return string
     */
    public function getUrl($scheme = false, $params = [])
    {
        UrlRuleContentElement::$models[$this->id] = $this;
        if ($params) {
            $params = ArrayHelper::merge(['/cms/content-element/view', 'id' => $this->id], $params);
        } else {
            $params = ['/cms/content-element/view', 'id' => $this->id];
        }

        return Url::to($params, $scheme);
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
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementFiles()
    {
        return $this->hasMany(CmsContentElementFile::className(), ['content_element_id' => 'id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElementImages()
    {
        return $this->hasMany(CmsContentElementImage::className(), ['content_element_id' => 'id']);
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
    public function setFileIds($ids)
    {
        $this->_file_ids = $ids;
        return $this;
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsContentElementImages')
            ->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsContentElementFiles')
            ->orderBy(['priority' => SORT_ASC]);
    }

    /**
     * @return string
     */
    public function getPermissionName()
    {
        return 'cms/cms-content-element__'.$this->id;
    }


    /**
     * version > 2.4.8
     * @return \yii\db\ActiveQuery
     */
    public function getParentContentElement()
    {
        return $this->hasOne(static::className(), ['id' => 'parent_content_element_id']);
    }

    /**
     * version > 2.4.8
     * @return \yii\db\ActiveQuery
     */
    public function getChildrenContentElements()
    {
        return $this->hasMany(static::className(), ['parent_content_element_id' => 'id']);
    }


    /**
     * version > 2.6.1
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentElement2cmsUsers()
    {
        return $this->hasMany(CmsContentElement2cmsUser::className(), ['cms_content_element_id' => 'id']);
    }

    /**
     * version > 2.6.1
     * @return \yii\db\ActiveQuery
     */
    public function getUsersToFavorites()
    {
        return $this->hasMany(CmsUser::className(), ['id' => 'cms_user_id'])
            ->via('cmsContentElement2cmsUsers');
    }


    /**
     * @return CmsContentElement|static
     */
    public function copy()
    {
        $newImage = null;
        $newImage2 = null;

        try {
            $transaction = \Yii::$app->db->beginTransaction();

            $data = $this->toArray();

            ArrayHelper::remove($data, 'id');
            ArrayHelper::remove($data, 'created_at');
            ArrayHelper::remove($data, 'created_by');
            ArrayHelper::remove($data, 'updated_at');
            ArrayHelper::remove($data, 'updated_by');
            ArrayHelper::remove($data, 'image_id');
            ArrayHelper::remove($data, 'image_full_id');
            ArrayHelper::remove($data, 'code');

            $newModel = new static($data);
            $newModel->name = $newModel->name;
            if ($newModel->save()) {

                /**
                 * @var $newModel CmsContentElement
                 */
                if ($this->image) {
                    $newImage = $this->image->copy();
                    $newModel->link('image', $newImage);
                }

                if ($this->fullImage) {
                    $newImage2 = $this->fullImage->copy();
                    $newModel->link('fullImage', $newImage2);
                }
            }

            if ($rp = $this->relatedPropertiesModel) {
                $this->relatedPropertiesModel->initAllProperties();
                $rp->relatedElementModel = $newModel;
                $rp->save();
            }

            $transaction->commit();

            return $newModel;

        } catch (\Exception $e) {

            if ($newImage) {
                $newImage->delete();
            }
            if ($newImage2) {
                $newImage2->delete();
            }
            $transaction->rollBack();
            throw $e;
        }
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



