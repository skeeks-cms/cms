<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
namespace skeeks\cms\models;
use skeeks\cms\models\behaviors\HasMultiLangAndSiteFields;
use skeeks\cms\models\behaviors\traits\HasMultiLangAndSiteFieldsTrait;

/**
 * Class Settings
 * @package skeeks\cms\models
 */
class Settings extends Core
{
    use HasMultiLangAndSiteFieldsTrait;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_settings}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasMultiLangAndSiteFields::className() =>
            [
                'class'     => HasMultiLangAndSiteFields::className(),
                'fields'    => ['value']
            ]
        ]);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['component'], 'required'],
            [['component'], 'unique'],
            [['value'], 'safe'],
            [['component'], 'string', 'max' => 255]
        ]);
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'id'            => Yii::t('app', 'ID'),
            'value'         => Yii::t('app', 'Значение'),
            'component'     => Yii::t('app', 'Компонент'),
        ]);
    }
}
