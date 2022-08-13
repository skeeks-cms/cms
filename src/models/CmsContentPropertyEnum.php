<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (�����)
 * @date 29.07.2016
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\relatedProperties\models\RelatedPropertyEnumModel;

/**
 * This is the model class for table "{{%cms_content_property_enum}}".
 *
 * @property string|null        $value_for_saved_filter Название (для сохраненных фильтров)
 * @property string|null        $description Описание
 * @property int|null           $cms_image_id Фото/Изображение
 *
 * @property CmsStorageFile     $cmsImage
 *
 * @property CmsContentProperty $property
 */
class CmsContentPropertyEnum extends RelatedPropertyEnumModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_content_property_enum}}';
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
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'value_for_saved_filter' => 'Название (для сохраненных фильтров)',
            'description'            => 'Описание',
            'cms_image_id'           => 'Фото/Изображение',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'value_for_saved_filter' => 'Как будет склонятся или изменяться название в сохраненном фильтре.<br />Например:<br /> Если опция "зеленый", то фильтр "товары {зеленого цвета}"',
        ]);
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['value_for_saved_filter'], 'string'],
            [['description'], 'string'],

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
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsImage()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'cms_image_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProperty()
    {
        return $this->hasOne(CmsContentProperty::className(), ['id' => 'property_id']);
    }
}