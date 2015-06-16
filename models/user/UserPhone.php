<?php
/**
 * UserPhone
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\models\user;

use skeeks\cms\components\Cms;
use Yii;
use yii\behaviors\TimestampBehavior;
use \yii\db\ActiveRecord;

/**
 * Class UserPhone
 * @package skeeks\cms\models\user
 */
class UserPhone extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_phone}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            TimestampBehavior::className()
        ]);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['value'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['value', 'approved_key'], 'string'],
            [['approved'], 'string'],
            [['approved'], 'default', 'value' => Cms::BOOL_N],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'Пользователь'),
            'provider' => Yii::t('app', 'Provider'),
            'provider_identifier' => Yii::t('app', 'Provider Identifier'),
            'provider_data' => Yii::t('app', 'Provider Data'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'approved' => "Подтвержден",
            'value' => "Телефон",
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
