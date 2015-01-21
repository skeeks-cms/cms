
<?php
/**
 * UserEmail
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 21.01.2015
 * @since 1.0.0
 */
namespace skeeks\cms\models;

use skeeks\cms\base\db\ActiveRecord;
use Yii;

/**
 * Class UserEmail
 * @package skeeks\cms\models
 */
class UserEmail extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_email}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['value'], 'required'],
            [['user_id', 'created_at', 'updated_at', 'approved', 'main'], 'integer'],
            [['value', 'approved_key'], 'string'],
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
            'value' => Yii::t('app', 'Value'),
            'approved_key' => Yii::t('app', 'Ключ подтверждения'),
            'approved' => Yii::t('app', 'Подтвержденный?'),
            'main' => Yii::t('app', 'Главный'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function fetchUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}
