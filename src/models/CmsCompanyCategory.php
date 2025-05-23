<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;
/**
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $created_at
 * @property string|null $name
 * @property int         $sort
 */
class CmsCompanyCategory extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_company_category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            [['created_by', 'created_at', 'sort'], 'integer'],
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],

            [
                ['name'],
                'unique',
                'targetAttribute' => ['name'],
                //'message' => 'Этот email уже занят'
            ],

            [['sort'], 'default', 'value' => 100],
        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'name' => 'Название',
            'sort' => 'Сортировка',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return array_merge(parent::attributeHints(), [
            'name' => 'Обязательное уникальное поле',
            'sort' => 'Чем ниже цифра тем выше в списке',
        ]);
    }
}