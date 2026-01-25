<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 09.11.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models;

use skeeks\cms\query\CmsContentPropertyActiveQuery;
use skeeks\cms\relatedProperties\models\RelatedPropertyModel;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%cms_content_property}}".
 * @property integer|null                 $cms_site_id
 *
 * @property integer                      $is_offer_property
 * @property integer                      $is_img_offer_property
 * @property integer                      $is_vendor
 * @property integer                      $is_vendor_code
 * @property integer                      $is_country
 * @property integer                      $is_sticker
 * @property integer|null                 $sx_id
 *
 * @property CmsContent[]                 $cmsContents
 * @property CmsContentProperty2content[] $cmsContentProperty2contents
 *
 * @property CmsContentPropertyEnum[]     $enums
 * @property CmsContentElementProperty[]  $elementProperties
 *
 * @property CmsContentProperty2tree[]    $cmsContentProperty2trees
 * @property CmsTree[]                    $cmsTrees
 * @property CmsSite                      $cmsSite
 * @property ShopCmsContentProperty       $shopProperty
 */
class CmsContentProperty extends RelatedPropertyModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_property}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            \skeeks\cms\behaviors\RelationalBehavior::class,
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getElementProperties()
    {
        return $this->hasMany(CmsContentElementProperty::className(), ['property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEnums()
    {
        return $this->hasMany(CmsContentPropertyEnum::className(), ['property_id' => 'id']);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cmsContents' => Yii::t('skeeks/cms', 'Linked to content'),
            'cmsTrees'    => Yii::t('skeeks/cms', 'Linked to sections'),
            'cms_site_id' => Yii::t('skeeks/cms', 'Сайт'),

            'is_offer_property'     => \Yii::t('skeeks/cms', 'Свойство предложения?'),
            'is_img_offer_property' => \Yii::t('skeeks/cms', 'Отображать свойство картинкой?'),
            'is_vendor'             => \Yii::t('skeeks/cms', 'Производитель?'),
            'is_vendor_code'        => \Yii::t('skeeks/cms', 'Код производителя?'),
            'is_country'            => \Yii::t('skeeks/cms', 'Страна'),
            'is_sticker'            => \Yii::t('skeeks/cms', 'Стикер'),

            'sx_id'               => Yii::t('skeeks/cms', 'SkeekS Suppliers ID'),
        ]);
    }
    /**
     * @return array
     */
    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [
            'is_img_offer_property' => \Yii::t('skeeks/cms', 'В карточке будет отображатсья картинка при выборе этого варианта.'),
            'is_offer_property'     => \Yii::t('skeeks/cms', 'Если это свойство является свойством предложения, то оно будет показываться в сложных карточках.'),
            'cms_site_id'           => Yii::t('skeeks/cms', 'Если сайт не будет выбран, то свойство будет показываться на всех сайтах.'),
            'is_sticker'           => Yii::t('skeeks/cms', 'Эта характеристика является стикером на товар.'),
            'cmsContents'           => Yii::t('skeeks/cms', 'Необходимо выбрать в каком контенте будет показываться это свойство.'),
            'cmsTrees'              => Yii::t('skeeks/cms',
                'Так же есть возможность ограничить отображение поля только для определенных разделов. Если будут выбраны разделы, то добавляя элемент в соответствующий раздел будет показываться это поле.'),
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = ArrayHelper::merge(parent::rules(), [
            [
                ['is_vendor', 'is_vendor_code', 'is_sticker', 'is_country', 'is_offer_property', 'is_img_offer_property', 'sx_id'],
                'integer',
            ],

            /*[
                ['is_vendor', 'is_vendor_code', 'is_country', 'is_offer_property'],
                'default',
                'value' => null,
            ],*/
            /*[
                ['is_vendor', 'is_vendor_code', 'is_country', 'is_offer_property'],
                function ($attr) {
                    if ($this->{$attr} != 1) {
                        $this->{$attr} = null;
                    }
                },

            ],*/
            [
                ['is_img_offer_property', 'is_offer_property'],
                'default',
                'value' => 0,
            ],
            [
                ['sx_id'],
                'default',
                'value' => null,
            ],
            [
                ['is_sticker'],
                'default',
                'value' => null,
            ],
            [
                ['is_sticker'],
                function($attribute) {
                    if ($this->{$attribute} == 0) {
                        $this->{$attribute} = null;
                    }
                }
            ],

            [['cmsContents'], 'safe'],
            [['cmsTrees'], 'safe'],
            [['code', 'cms_site_id'], 'unique', 'targetAttribute' => ['code', 'cms_site_id']],
            [['cms_site_id'], 'integer'],
            [
                ['cms_site_id'],
                'default',
                'value' => function () {

                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                    /*if (\Yii::$app->skeeks->site->is_default) {
                        return null;
                    } else {
                        return \Yii::$app->skeeks->site->id;
                    }*/
                },
            ],


            [['cmsContents'], 'required'],

            [
                ['cms_site_id'],
                'required',
                'when' => function () {
                    return $this->cmsTrees;
                },
            ]


            //[['code', 'content_id'], 'unique', 'targetAttribute' => ['content_id', 'code'], 'message' => \Yii::t('skeeks/cms','For the content of this code is already in use.')],
        ]);

        return $rules;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty2contents()
    {
        return $this->hasMany(CmsContentProperty2content::className(), ['cms_content_property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContents()
    {
        return $this->hasMany(CmsContent::className(),
            ['id' => 'cms_content_id'])->viaTable('cms_content_property2content', ['cms_content_property_id' => 'id']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsContentProperty2trees()
    {
        return $this->hasMany(CmsContentProperty2tree::className(), ['cms_content_property_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        return $this->hasOne(CmsSite::class, ['id' => 'cms_site_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsTrees()
    {
        return $this->hasMany(CmsTree::className(), ['id' => 'cms_tree_id'])->viaTable('cms_content_property2tree',
            ['cms_content_property_id' => 'id']);
    }


    public function asText()
    {
        return parent::asText();
        return $result." ($this->code)";
    }


    public function getNameWithTrees()
    {
        $treeName = '';

        if ($this->cmsTrees) {
            $treeName = implode(", ", ArrayHelper::map($this->cmsTrees, "id", "name"));
        }

        return parent::asText() . " [{$treeName}]";
    }


    /**
     * @return CmsContentPropertyActiveQuery
     */
    public static function find()
    {
        return (new CmsContentPropertyActiveQuery(get_called_class()));
    }
}