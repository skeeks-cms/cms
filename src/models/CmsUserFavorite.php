<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @property int      $id
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int      $cms_user_id
 * @property string   $entity_type
 * @property int      $entity_id
 * @property int      $priority
 */
class CmsUserFavorite extends ActiveRecord
{
    const TYPE_COMPANY = 'companies';
    const TYPE_PROJECT = 'projects';
    const TYPE_CLIENT = 'clients';

    public static function tableName()
    {
        return '{{%cms_user_favorite}}';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_at', 'updated_at', 'cms_user_id', 'entity_id', 'priority'], 'integer'],
            [['entity_type'], 'string', 'max' => 64],
            [['cms_user_id', 'entity_type', 'entity_id'], 'required'],
            [['priority'], 'default', 'value' => 100],
            [['created_at', 'updated_at'], 'default', 'value' => null],
            [['entity_type'], 'in', 'range' => array_keys(static::typeLabels())],
            [['cms_user_id', 'entity_type', 'entity_id'], 'unique', 'targetAttribute' => ['cms_user_id', 'entity_type', 'entity_id']],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id' => 'Пользователь',
            'entity_type' => 'Тип объекта',
            'entity_id' => 'ID объекта',
            'priority' => 'Сортировка',
        ]);
    }

    public function beforeSave($insert)
    {
        if ($insert && !$this->created_at) {
            $this->created_at = time();
        }

        $this->updated_at = time();

        return parent::beforeSave($insert);
    }

    public static function typeLabels()
    {
        return [
            static::TYPE_COMPANY => 'компания',
            static::TYPE_PROJECT => 'проект',
            static::TYPE_CLIENT => 'клиент',
        ];
    }

    public static function normalizeType($type)
    {
        $aliases = [
            'company' => static::TYPE_COMPANY,
            'project' => static::TYPE_PROJECT,
            'client' => static::TYPE_CLIENT,
            'user' => static::TYPE_CLIENT,
        ];

        return ArrayHelper::getValue($aliases, $type, $type);
    }

    public function getEntityKey()
    {
        return $this->entity_type . ':' . $this->entity_id;
    }
}
