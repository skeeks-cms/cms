<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\models;

use Imagine\Image\ManipulatorInterface;
use skeeks\cms\base\Widget;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasRelatedProperties;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\HasStorageFileMulti;
use skeeks\cms\models\behaviors\HasTrees;
use skeeks\cms\models\behaviors\SeoPageName;
use skeeks\cms\models\behaviors\TimestampPublishedBehavior;
use skeeks\cms\models\behaviors\traits\HasRelatedPropertiesTrait;
use skeeks\cms\models\behaviors\traits\HasTreesTrait;
use skeeks\cms\models\behaviors\traits\HasUrlTrait;
use skeeks\cms\relatedProperties\models\RelatedElementModel;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\ErrorHandler;

/**
 * This is the model class for table "{{%cms_content_element}}".
 *
 * @property integer $id
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $published_at
 * @property integer $published_to
 * @property integer $priority
 * @property string $active
 * @property string $name
 * @property string $code
 * @property string $description_short
 * @property string $description_full
 * @property integer $content_id
 * @property integer $image_id
 * @property integer $image_full_id
 * @property integer $tree_id
 * @property integer $show_counter
 * @property integer $show_counter_start
 * @property string $meta_title
 * @property string $meta_description
 * @property string $meta_keywords
 *
 * @property integer $parent_content_element_id version > 2.4.8
 *
 *
 * @property string $permissionName
 *
 * @property string $description_short_type
 * @property string $description_full_type
 *
 * @property string $absoluteUrl
 * @property string $url
 *
 * @property CmsContent $cmsContent
 * @property Tree $cmsTree

 * @property CmsContentElementProperty[]    $relatedElementProperties
 * @property CmsContentProperty[]           $relatedProperties
 * @property CmsContentElementTree[]        $cmsContentElementTrees
 * @property CmsContentElementProperty[]    $cmsContentElementProperties
 * @property CmsContentProperty[]           $cmsContentProperties
 *
 * @property CmsStorageFile $image
 * @property CmsStorageFile $fullImage
 *
 * @property CmsContentElementFile[] $cmsContentElementFiles
 * @property CmsContentElementImage[] $cmsContentElementImages
 *
 * @property CmsStorageFile[] $files
 * @property CmsStorageFile[] $images
 *
 * version > 2.4.8
 * @property CmsContentElement $parentContentElement
 * @property CmsContentElement[] $childrenContentElements
 *
 */
class CmsContentElement extends RelatedElementModel
{
    use HasRelatedPropertiesTrait;
    use HasTreesTrait;
    use HasUrlTrait;

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
        //TODO: Upgrade this
        if ($this->childrenContentElements)
        {
            foreach ($this->childrenContentElements as $childrenElement)
            {
                $childrenElement->delete();
            }
        }
    }

    public function _afterDeleteE($e)
    {
        if ($permission = \Yii::$app->authManager->getPermission($this->permissionName))
        {
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

            HasStorageFile::className() =>
            [
                'class'     => HasStorageFile::className(),
                'fields'    => ['image_id', 'image_full_id']
            ],
            HasStorageFileMulti::className() =>
            [
                'class'     => HasStorageFileMulti::className(),
                'relations'    => ['images', 'files']
            ],

            HasRelatedProperties::className() =>
            [
                'class'                             => HasRelatedProperties::className(),
                'relatedElementPropertyClassName'   => CmsContentElementProperty::className(),
                'relatedPropertyClassName'          => CmsContentProperty::className(),
            ],

            HasTrees::className() =>
            [
                'class'                             => HasTrees::className(),
                'elementTreesClassName'             => CmsContentElementTree::className(),
            ],

            SeoPageName::className() =>
            [
                'class'                             => SeoPageName::className(),
                'generatedAttribute'                => 'code',
            ]
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
            'published_at' => Yii::t('app', 'Published At'),
            'published_to' => Yii::t('app', 'Published To'),
            'priority' => Yii::t('app', 'Priority'),
            'active' => Yii::t('app', 'Active'),
            'name' => Yii::t('app', 'Name'),
            'code' => Yii::t('app', 'Code'),
            'description_short' => Yii::t('app', 'Description Short'),
            'description_full' => Yii::t('app', 'Description Full'),
            'content_id' => Yii::t('app', 'Content'),
            'tree_id' => Yii::t('app', 'The Main Section'),
            'show_counter' => Yii::t('app', 'Show Counter'),
            'show_counter_start' => Yii::t('app', 'Show Counter Start'),
            'meta_title' => Yii::t('app', 'Meta Title'),
            'meta_keywords' => Yii::t('app', 'Meta Keywords'),
            'meta_description' => Yii::t('app', 'Meta Description'),
            'description_short_type' => Yii::t('app', 'Description Short Type'),
            'description_full_type' => Yii::t('app', 'Description Full Type'),
            'image_id' => Yii::t('app', 'Main Image (announcement)'),
            'image_full_id' => Yii::t('app', 'Main Image'),

            'images' => Yii::t('app', 'Images'),
            'files' => Yii::t('app', 'Files'),
            'treeIds' => Yii::t('app', 'Sections'),
            'parent_content_element_id' => Yii::t('app', 'Parent element'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'published_at', 'published_to', 'priority', 'content_id', 'tree_id', 'show_counter', 'show_counter_start', 'image_id', 'image_full_id'], 'integer'],
            [['name'], 'required'],
            [['description_short', 'description_full'], 'string'],
            [['active'], 'string', 'max' => 1],
            [['name', 'code'], 'string', 'max' => 255],
            [['content_id', 'code'], 'unique', 'targetAttribute' => ['content_id', 'code'], 'message' => \Yii::t('app','For the content of this code is already in use.')],
            [['tree_id', 'code'], 'unique', 'targetAttribute' => ['tree_id', 'code'], 'message' => \Yii::t('app','For this section of the code is already in use.')],
            [['treeIds'], 'safe'],
            ['priority', 'default', 'value' => 500],
            ['active', 'default', 'value' => Cms::BOOL_Y],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],

            ['description_short_type', 'string'],
            ['description_full_type', 'string'],
            ['description_short_type', 'default', 'value' => "text"],
            ['description_full_type', 'default', 'value' => "text"],
            ['tree_id', 'default', 'value' => function()
            {
                if ($this->cmsContent->defaultTree)
                {
                    return $this->cmsContent->defaultTree->id;
                }
            }],

            ['parent_content_element_id', 'integer'],
            ['parent_content_element_id', 'validateParentContentElement'],
            ['parent_content_element_id', 'required', 'when' => function(CmsContentElement $model) {

                if ($model->cmsContent && $model->cmsContent->parentContent)
                {
                    return (bool) ($model->cmsContent->parent_content_is_required == "Y");
                }

                return false;
            }, 'whenClient' => "function (attribute, value) {
                return $('#cmscontent-parent_content_is_required').val() == 'Y';
            }"]

        ]);
    }

    /*public function fields()
    {
        return array_merge(parent::fields(), [
            'url'           => 'url',
            'absoluteUrl'   => 'absoluteUrl',
        ]);
    }*/

    /*public function extraFields()
    {
        return array_merge(parent::extraFields(), [
            'relatedPropertiesModel' => 'relatedPropertiesModel',
        ]);
    }*/

    /**
     * Валидация родительского элемента
     *
     * @param $attribute
     * @return bool
     */
    public function validateParentContentElement($attribute)
    {
        if (!$this->cmsContent)
        {
            return false;
        }

        if (!$this->cmsContent->parentContent)
        {
            return false;
        }

        if ($this->$attribute)
        {
            $contentElement = static::findOne($this->$attribute);
            if ($contentElement->cmsContent->id != $this->cmsContent->parentContent->id)
            {
                $this->addError($attribute, \Yii::t('app', 'The parent must be a content element: «{contentName}».',['contentName' => $this->cmsContent->parentContent->name]));
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

    /**
     *
     * Все возможные свойства связанные с моделью
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getRelatedProperties()
    {
        return $this->hasMany(CmsContentProperty::className(), ['content_id' => 'id'])
                    ->via('cmsContent')->orderBy(['priority' => SORT_ASC]);
        //return $this->cmsContent->cmsContentProperties;
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
    public function getUrl()
    {
        return Url::to(['/cms/content-element/view', 'model' => $this]);
        /*return UrlHelper::construct('cms/content-element/view', [
            'id'    => $this->id,
            'code'  => $this->code,
        ])->toString();*/
    }

    /**
     * @return string
     */
    public function getAbsoluteUrl()
    {
        if ($this->cmsTree)
        {
            return $this->cmsTree->site->url . $this->url;
        }

        return $this->url;
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
     * @return \yii\db\ActiveQuery
     */
    public function getImages()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsContentElementImages');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFiles()
    {
        return $this->hasMany(StorageFile::className(), ['id' => 'storage_file_id'])
            ->via('cmsContentElementFiles');
    }

    /**
     * @return string
     */
    public function getPermissionName()
    {
        return 'cms/cms-content-element__' . $this->id;
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

}
