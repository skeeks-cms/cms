<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 19.05.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\components\urlRules\UrlRuleContentElement;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\traits\HasUrlTrait;
use skeeks\yii2\yaslug\YaSlugBehavior;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Application;

/**
 *
 * @property integer                $id
 * @property integer                $created_by
 * @property integer                $updated_by
 * @property integer                $created_at
 * @property integer                $updated_at
 * @property integer                $priority
 * @property string|null            $short_name
 * @property string                 $code
 * @property string                 $description_short
 * @property string                 $description_full
 * @property integer|null           $cms_image_id
 * @property integer                $cms_tree_id
 * @property string                 $meta_title
 * @property string                 $meta_description
 * @property string                 $meta_keywords
 * @property string                 $seo_h1
 * @property integer|null           $cms_site_id
 * @property integer|null           $value_content_element_id
 * @property integer|null           $value_content_property_enum_id
 * @property integer|null           $cms_content_property_id
 * @property string                 $description_short_type
 * @property string                 $description_full_type
 *
 * ***
 *
 * @property string                 $absoluteUrl
 * @property string                 $url
 *
 * @property CmsTree                $cmsTree
 * @property CmsSite                $cmsSite
 * @property CmsStorageFile         $cmsImage
 * @property CmsContentElement      $valueContentElement
 * @property CmsContentPropertyEnum $valueContentPropertyEnum
 * @property CmsContentProperty     $cmsContentProperty
 *
 * @property string                 $seoName
 * @property string                 $propertyValueName
 * @property string                 $name
 * @property CmsStorageFile|null    $image
 *
 */
class CmsSavedFilter extends ActiveRecord
{
    use HasUrlTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_saved_filter}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [

            HasStorageFile::className() => [
                'class'  => HasStorageFile::className(),
                'fields' => ['cms_image_id'],
            ],

            YaSlugBehavior::class => [
                'class'         => YaSlugBehavior::class,
                'attribute'     => 'seoName',
                'slugAttribute' => 'code',
                'ensureUnique'  => false,
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
            'priority'               => Yii::t('skeeks/cms', 'Priority'),
            'short_name'             => Yii::t('skeeks/cms', 'Name'),
            'code'                   => Yii::t('skeeks/cms', 'Code'),
            'description_short'      => Yii::t('skeeks/cms', 'Description Short'),
            'description_full'       => Yii::t('skeeks/cms', 'Description Full'),
            'cms_tree_id'            => Yii::t('skeeks/cms', 'The main section'),
            'meta_title'             => Yii::t('skeeks/cms', 'Meta Title'),
            'meta_keywords'          => Yii::t('skeeks/cms', 'Meta Keywords'),
            'meta_description'       => Yii::t('skeeks/cms', 'Meta Description'),
            'description_short_type' => Yii::t('skeeks/cms', 'Description Short Type'),
            'description_full_type'  => Yii::t('skeeks/cms', 'Description Full Type'),
            'cms_image_id'           => Yii::t('skeeks/cms', 'Main Image'),
            'seo_h1'                 => Yii::t('skeeks/cms', 'SEO заголовок h1'),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'seo_h1' => 'Заголовок будет показан на детальной странице, в случае если его использование задано в шаблоне.',
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
                    'priority',
                    'cms_tree_id',
                    'cms_site_id',
                    'cms_content_property_id',
                    'value_content_element_id',
                    'value_content_property_enum_id',
                ],
                'integer',
            ],

            [['short_name'], 'trim'],

            [['description_short', 'description_full'], 'string'],

            [['short_name', 'code'], 'string', 'max' => 255],
            [['seo_h1'], 'string', 'max' => 255],

            ['priority', 'default', 'value' => 500],
            [['meta_title', 'meta_description', 'meta_keywords'], 'string'],
            [['meta_title'], 'string', 'max' => 500],

            ['description_short_type', 'string'],
            ['description_full_type', 'string'],
            ['description_short_type', 'default', 'value' => "text"],
            ['description_full_type', 'default', 'value' => "text"],

            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [['cms_image_id'], 'safe'],
            [
                ['cms_image_id'],
                \skeeks\cms\validators\FileValidator::class,
                'skipOnEmpty' => false,
                'extensions'  => ['jpg', 'jpeg', 'gif', 'png', 'webp'],
                'maxFiles'    => 1,
                'maxSize'     => 1024 * 1024 * 10,
                'minSize'     => 256,
            ],


            [
                ['cms_tree_id', 'cms_site_id'],
                function ($attribute) {
                    if ($this->cmsTree && $this->cmsSite) {
                        if ($this->cmsSite->id != $this->cmsTree->cms_site_id) {
                            $this->addError($attribute, "Раздел к которому привязывается элемент должен относится к тому же сайту");
                        }
                    }
                },
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTree()
    {
        return $this->hasOne(CmsTree::className(), ['id' => 'cms_tree_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        $class = \Yii::$app->skeeks->siteClass;
        return $this->hasOne($class, ['id' => 'cms_site_id']);
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
        //Это можно использовать только в коротких сценариях, иначе произойдет переполнение памяти
        if (\Yii::$app instanceof Application) {
            UrlRuleContentElement::$models[$this->id] = $this;
        }

        if ($params) {
            $params = ArrayHelper::merge(['/cms/saved-filter/view', 'id' => $this->id], $params);
        } else {
            $params = ['/cms/saved-filter/view', 'id' => $this->id];
        }

        return Url::to($params, $scheme);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsImage()
    {
        return $this->hasOne(StorageFile::className(), ['id' => 'cms_image_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getValueContentElement()
    {
        return $this->hasOne(CmsContentElement::className(), ['id' => 'value_content_element_id']);
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
    public function getValueContentPropertyEnum()
    {
        return $this->hasOne(CmsContentPropertyEnum::className(), ['id' => 'value_content_property_enum_id']);
    }

    /**
     * @return CmsStorageFile|null
     */
    public function getImage()
    {
        if ($this->cmsImage) {
            return $this->cmsImage;
        }

        if ($this->cmsTree && $this->cmsTree->image) {
            return $this->cmsTree->image;
        }

        return null;
    }

    public function getPropertyValueName()
    {
        $last = '';

        if ($this->value_content_element_id) {
            if ($this->isNewRecord) {
                $element = CmsContentElement::findOne($this->value_content_element_id);
                if ($element) {
                    $last = $element->name;
                }
            } else {
                if ($this->valueContentElement) {
                    $last = $this->valueContentElement->name;
                }
            }

        } elseif ($this->value_content_property_enum_id) {

            if ($this->isNewRecord) {
                $element = CmsContentPropertyEnum::findOne($this->value_content_property_enum_id);
                if ($element) {
                    $last = $element->value;
                }
            } else {
                if ($this->valueContentPropertyEnum) {
                    $last = $this->valueContentPropertyEnum->value;
                }
            }
        }

        return $last;
    }
    /**
     * @return string|null
     */
    public function getName()
    {
        if ($this->_name === null) {
            if ($this->short_name) {
                $this->_name = $this->short_name;
            } else {
                $this->_name = $this->propertyValueName;
            }
        }

        return $this->_name;
    }

    protected $_name = null;
    protected $_seoName = null;

    /**
     * @param $value
     * @return $this
     */
    public function setSeoName($value)
    {
        $this->_seoName = $value;
        return $this;
    }
    /**
     * Полное название
     *
     * @return string
     */
    public function getSeoName()
    {
        $result = "";

        if ($this->_seoName === null) {
            if ($this->seo_h1) {
                $this->_seoName = $this->seo_h1;
            } elseif ($this->short_name) {
                $this->_seoName = $this->name;
            } else {
                $last = '';

                if ($this->value_content_element_id) {
                    if ($this->isNewRecord) {
                        $element = CmsContentElement::findOne($this->value_content_element_id);
                        if ($element) {
                            $last = $element->name;
                        }
                    } else {
                        if ($this->valueContentElement) {
                            $last = $this->valueContentElement->name;
                        }
                    }

                } elseif ($this->value_content_property_enum_id) {

                    //преобразуем вторую часть в нижний регистр
                    
                    if ($this->isNewRecord) {
                        $element = CmsContentPropertyEnum::findOne($this->value_content_property_enum_id);
                        if ($element) {
                            $last = StringHelper::lcfirst($element->value);
                        }
                    } else {
                        if ($this->valueContentPropertyEnum) {
                            $last = StringHelper::lcfirst($this->valueContentPropertyEnum->value);
                        }
                    }
                }

                $this->_seoName = $this->cmsTree->seoName." ". $last;
            }
        }


        return $this->_seoName;
    }

    public function asText()
    {
        $result = [];
        $result[] = "#".$this->id;
        $result[] = $this->seoName;

        return implode("", $result);
    }
}



