<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.02.2015
 */

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\StringHelper;
use skeeks\cms\validators\PhoneValidator;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_user_phone".
 *
 * @property int         $id
 * @property int|null    $created_by
 * @property int|null    $updated_by
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int         $cms_site_id
 * @property int         $cms_user_id
 * @property string      $value Телефон
 * @property string|null $name Примечание к телефону
 * @property int         $sort
 * @property int         $is_approved Телефон подтвержден?
 * @property string|null $approved_key Ключ для подтверждения телефона
 * @property int|null    $approved_key_at Время генерация ключа
 *
 * @property CmsSite     $cmsSite
 * @property CmsUser     $cmsUser
 */
class CmsUserPhone extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cms_user_phone}}';
    }

    /**
     * @return \skeeks\cms\query\CmsActiveQuery
     */
    /*public static function find()
    {
        return parent::find()->cmsSite();
    }*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],

            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'cms_user_id', 'sort', 'is_approved', 'approved_key_at'], 'integer'],
            [['cms_user_id', 'value'], 'required'],
            [['value', 'name', 'approved_key'], 'string', 'max' => 255],
            [['cms_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['cms_user_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['updated_by' => 'id']],

            [['cms_site_id', 'value'], 'unique', 'targetAttribute' => ['cms_site_id', 'value'], 'message' => 'Этот телефон уже занят'],
            [['cms_user_id', 'value'], 'unique', 'targetAttribute' => ['cms_user_id', 'value'], 'message' => 'Этот телефон уже занят'],

            [['is_approved'], 'default', 'value' => 0],
            [['name', 'approved_key_at', 'approved_key'], 'default', 'value' => null],

            [['value'], "filter", 'filter' => 'trim'],
            [['value'], "filter", 'filter' => function($value) {
                return StringHelper::strtolower($value);
            }],
            [['value'], PhoneValidator::class],

        ]);
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_user_id' => Yii::t('skeeks/cms', 'User'),
            'name'        => "Описание",
            'value'       => "Телефон",
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        $userClass = isset(\Yii::$app->user) ? \Yii::$app->user->identityClass : CmsUser::class;
        return $this->hasOne($userClass, ['id' => 'cms_user_id']);
    }
}
