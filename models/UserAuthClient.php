<?php
/**
 * UserAuthClient
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use Yii;
use \yii\db\ActiveRecord;

/**
 * Class UserAuthclient
 * @package skeeks\module\cms\user\model
 */
class UserAuthclient extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_authclient}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'created_at', 'updated_at'], 'integer'],
            [['provider_data'], 'string'],
            [['provider'], 'string', 'max' => 50],
            [['provider_identifier'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'provider' => Yii::t('app', 'Provider'),
            'provider_identifier' => Yii::t('app', 'Provider Identifier'),
            'provider_data' => Yii::t('app', 'Provider Data'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
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
