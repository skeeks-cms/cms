<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
namespace skeeks\cms\models;
use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
/**
 * Class CmsComponentSettings
 * @package skeeks\cms\models
 */
class CmsComponentSettings extends Core
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_component_settings}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            HasJsonFieldsBehavior::className() =>
            [
                'class'     => HasJsonFieldsBehavior::className(),
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
