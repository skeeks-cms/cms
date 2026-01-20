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
use skeeks\cms\eavqueryfilter\CmsEavQueryFilterHandler;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\HasStatus;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\behaviors\traits\HasUrlTrait;
use skeeks\cms\models\queries\CmsSavedFilterQuery;
use skeeks\cms\shop\models\ShopBrand;
use skeeks\cms\shop\queryFilter\ShopDataFiltersHandler;
use skeeks\yii2\yaslug\YaSlugBehavior;
use Yii;
use yii\db\ActiveQuery;
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
 * @property string|null            $country_alpha2
 * @property integer|null           $shop_brand_id
 * @property string                 $description_short_type
 * @property string                 $description_full_type
 *
 * ***
 *
 * @property string                 $absoluteUrl
 * @property string                 $url
 * @property bool                 $isAllowIndex
 *
 * @property string                $propertyIdForFilter
 * @property string                $propertyValueForFilter
 * @property CmsTree                $cmsTree
 * @property CmsSite                $cmsSite
 * @property CmsStorageFile         $cmsImage
 * @property CmsContentElement      $valueContentElement
 * @property CmsContentPropertyEnum $valueContentPropertyEnum
 * @property CmsContentProperty     $cmsContentProperty
 * @property ShopBrand              $brand
 * @property CmsCountry             $country
 *
 * @property string                 $seoName Полное seo название фильтра. Seo название раздела + склоненное название опции. Например (Строительные краски зеленого цвета).
 * @property string                 $shortSeoName Короткое название фильтра. Название раздела + склоненное название опции. Например (Краски зеленого цвета).
 * @property string                 $propertyValueName Название выбранной опции фильтра. Например цвет (Зеленый)
 * @property string                 $propertyValueNameInflected Склоненное название выбранной опции фильтра. Например цвет (Зеленого цвета)
 *
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
            'country_alpha2'         => Yii::t('skeeks/cms', 'Страна'),
            'shop_brand_id'          => Yii::t('skeeks/cms', 'Бренд'),
            'cms_content_property_id'          => Yii::t('skeeks/cms', 'Характеристика'),
            'value_content_element_id'          => Yii::t('skeeks/cms', 'Значение'),
            'value_content_property_enum_id'          => Yii::t('skeeks/cms', 'Значение'),
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
                    'shop_brand_id',
                ],
                'integer',
            ],
            [
                [
                    'cms_content_property_id',
                    'value_content_element_id',
                    'value_content_property_enum_id',
                    'country_alpha2',
                    'shop_brand_id',
                ],
                'default',
                'value' => null,
            ],

            [['short_name'], 'trim'],

            [['country_alpha2'], 'string'],
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
            $params = ArrayHelper::merge(['/cms/saved-filter/view', 'model' => $this], $params);
        } else {
            $params = ['/cms/saved-filter/view', 'model' => $this];
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
     * @return ShopBrand
     */
    public function getBrand()
    {
        return $this->hasOne(ShopBrand::class, ['id' => 'shop_brand_id'])->from(['shopBrand' => ShopBrand::tableName()]);

    }
    /**
     * @return CmsCountry|null
     */
    public function getCountry()
    {
        return $this->hasOne(CmsCountry::class, ['alpha2' => 'country_alpha2']);
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
    protected $_shortSeoName = null;

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
     * @var CmsCountry
     */
    protected $_country = null;
    /**
     * @var null|ShopBrand
     */
    protected $_brand = null;
    /**
     * @var null|CmsContentPropertyEnum
     */
    protected $_enum = null;

    /**
     * @var null|CmsContentElement
     */
    protected $_element = null;

    /**
     * @var null подгтовлены все названия?
     */
    protected $_initNames = null;

    protected function _initNames()
    {
        $last = '';

        if ($this->_initNames === null) {
            if ($this->value_content_element_id) {
                if ($this->isNewRecord) {
                    $this->_element = CmsContentElement::findOne($this->value_content_element_id);
                    if ($this->_element) {
                        $last = $this->_element->name;
                    }
                } else {
                    $this->_element = $this->valueContentElement;
                    if ($this->_element) {
                        $last = $this->_element->name;
                    }
                }

            } elseif ($this->value_content_property_enum_id) {

                //преобразуем вторую часть в нижний регистр

                if ($this->isNewRecord) {
                    $this->_enum = CmsContentPropertyEnum::findOne($this->value_content_property_enum_id);
                    if ($this->_enum) {
                        $last = StringHelper::lcfirst($this->_enum->value_for_saved_filter ? $this->_enum->value_for_saved_filter : $this->_enum->value);
                    }
                } else {
                    $this->_enum = $this->valueContentPropertyEnum;
                    if ($this->_enum) {
                        $last = StringHelper::lcfirst($this->_enum->value_for_saved_filter ? $this->_enum->value_for_saved_filter : $this->_enum->value);
                    }
                }
            } elseif ($this->shop_brand_id) {

                //преобразуем вторую часть в нижний регистр

                if ($this->isNewRecord) {
                    $this->_brand = ShopBrand::findOne($this->shop_brand_id);
                    if ($this->_brand) {
                        $last = $this->_brand->name;
                    }
                } else {
                    $this->_brand = $this->brand;
                    if ($this->_brand) {
                        $last = $this->_brand->name;
                    }
                }
            } elseif ($this->country_alpha2) {

                //преобразуем вторую часть в нижний регистр

                if ($this->isNewRecord) {
                    $this->_country = CmsCountry::find()->alpha2($this->country_alpha2)->one();
                    if ($this->_country) {
                        $last = $this->_country->name;
                    }
                } else {
                    $this->_country = $this->country;
                    if ($this->_country) {
                        $last = $this->_country->name;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Название выбранной опции фильтра
     *
     * @return string
     */
    public function getPropertyValueName()
    {
        $this->_initNames();

        if ($this->_enum) {
            return StringHelper::ucfirst($this->_enum->value);
        } elseif ($this->_element) {
            return StringHelper::ucfirst($this->_element->name);
        } elseif ($this->_brand) {
            return StringHelper::ucfirst($this->_brand->name);
        } elseif ($this->_country) {
            return StringHelper::ucfirst($this->_country->name);
        }

        return '';
    }

    /**
     * Название выбранной опции фильтра
     *
     * @return string
     */
    public function getPropertyValueNameInflected()
    {
        $this->_initNames();

        if ($this->_enum) {
            return StringHelper::ucfirst($this->_enum->value_for_saved_filter ? $this->_enum->value_for_saved_filter : $this->_enum->value);
        } elseif ($this->_element) {
            //todo: доработать склонение тут
            return StringHelper::ucfirst($this->_element->name);
        } elseif ($this->_country) {
            //todo: доработать склонение тут
            return $this->_country->name;
        } elseif ($this->_brand) {
            //todo: доработать склонение тут
            return $this->_brand->name;
        }

        return '';
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
                $this->_initNames();
                if ($this->_brand || $this->_country) {
                    $this->_seoName = $this->cmsTree->seoName." ".$this->propertyValueNameInflected;
                } else {
                   $this->_seoName = $this->cmsTree->seoName." ".StringHelper::lcfirst($this->propertyValueNameInflected);
                }

            }
        }


        return $this->_seoName;
    }
    /**
     * Полное название
     *
     * @return string
     */
    public function getShortSeoName()
    {
        $result = "";

        $this->_initNames();

        if ($this->_brand || $this->_country) {
            $this->_shortSeoName = $this->cmsTree->name." ".$this->propertyValueNameInflected;
        } else {
           $this->_shortSeoName = $this->cmsTree->name." ".StringHelper::lcfirst($this->propertyValueNameInflected);
        }



        return $this->_shortSeoName;
    }


    public function asText()
    {
        $result = [];
        $result[] = "#".$this->id;
        $result[] = $this->seoName;

        return implode("", $result);
    }

    /**
     * @param array $savedFilters
     * @return array
     */
    static public function formatFilters(array $savedFilters)
    {
        $savedFiltersData = [];

        foreach ($savedFilters as $sf) {
            /**
             * @var $sf \skeeks\cms\models\CmsSavedFilter
             */
            $pr_id = "";
            $n = "";

            if ($sf->cms_content_property_id) {
                $pr_id = $sf->cms_content_property_id;
                $n = $sf->cmsContentProperty->name;
            } elseif ($sf->shop_brand_id) {
                $pr_id = "shop_brand_id";
                $n = "Бренд";
            } elseif ($sf->country_alpha2) {
                $pr_id = "country";
                $n = "Страна";
            }

            $savedFiltersData[$pr_id]['savedFilters'][$sf->id] = $sf;
            $savedFiltersData[$pr_id]['name'] = $n;
        }

        return $savedFiltersData;
    }




    /**
     * @return CmsContentQuery
     */
    public static function find()
    {
        return (new CmsSavedFilterQuery(get_called_class()));
    }

    /**
     * @return bool
     */
    public function getIsAllowIndex()
    {
        //Если страница 18+, и не разрешено индексировать такой контент, то не индексируем!
        if ($this->cmsTree && $this->cmsTree->is_adult && !\Yii::$app->seo->is_allow_index_adult_content) {
            return false;
        }

        return true;
    }

    public function getPropertyIdForFilter()
    {
        $baseQuery = CmsCompareElement::find();
        
        if ($this->shop_brand_id) {
            $f = new ShopDataFiltersHandler([
                'baseQuery' => $baseQuery,
            ]);
            return "field-{$f->formName()}-brand_id";
        }
        
        if ($this->cms_content_property_id) {
            $f = new CmsEavQueryFilterHandler([
                'baseQuery' => $baseQuery,
            ]);
            return "field-{$f->formName()}-f" . $this->cms_content_property_id;
        }
        
        
        
        return "";
    }
    public function getPropertyValueForFilter()
    {
        
        if ($this->value_content_element_id) {
            return $this->value_content_element_id;
        }
        
        if ($this->value_content_property_enum_id) {
            return $this->value_content_property_enum_id;
        }
        
        if ($this->shop_brand_id) {
            return $this->shop_brand_id;
        }

        return "";
    }

}



