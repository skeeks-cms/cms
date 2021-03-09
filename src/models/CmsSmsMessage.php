<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "cms_sms_message".
 *
 * @property int            $id
 * @property int|null       $created_by
 * @property int|null       $updated_by
 * @property int|null       $created_at
 * @property int|null       $updated_at
 * @property string|null    $user_ip
 * @property int            $cms_site_id
 * @property int|null       $cms_sms_provider_id
 * @property string         $phone
 * @property string|null    $message
 * @property string         $status
 * @property string|null    $error_message
 * @property string|null    $provider_status
 * @property string|null    $provider_message_id
 *
 * @property boolean        $isDelivered
 * @property boolean        $isError
 *
 * @property string         $statusAsText
 * @property CmsSite        $cmsSite
 * @property CmsSmsProvider $cmsSmsProvider
 */
class CmsSmsMessage extends \skeeks\cms\base\ActiveRecord
{
    const STATUS_ERROR = "error";
    const STATUS_NEW = "new";
    const STATUS_DELIVERED = "delivered";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_sms_message';
    }

    public function statuses()
    {
        return [
            self::STATUS_NEW       => "Новое",
            self::STATUS_DELIVERED => "Доставлено",
            self::STATUS_ERROR     => "Ошибка",
        ];
    }

    /**
     * @return bool
     */
    public function getIsDelivered()
    {
        return $this->status == self::STATUS_DELIVERED;
    }

    /**
     * @return bool
     */
    public function getIsError()
    {
        return $this->status == self::STATUS_ERROR;
    }

    /**
     * @return string
     */
    public function getStatusAsText()
    {
        return (string)ArrayHelper::getValue(self::statuses(), $this->status);
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'cms_sms_provider_id'], 'integer'],
            [['phone', 'message'], 'required'],
            [['error_message'], 'string'],
            [['message'], 'string'],
            [['user_ip'], 'string', 'max' => 20],
            [['phone', 'status', 'provider_status'], 'string', 'max' => 255],
            [['provider_message_id'], 'string', 'max' => 255],
            [['cms_sms_provider_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSmsProvider::class, 'targetAttribute' => ['cms_sms_provider_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::class, 'targetAttribute' => ['cms_site_id' => 'id']],

            [
                ['error_message', 'provider_status', 'provider_message_id'],
                'default',
                'value' => null,
            ],
            [
                'status',
                'default',
                'value' => self::STATUS_NEW,
            ],
            [
                'cms_site_id',
                'default',
                'value' => function () {
                    if (\Yii::$app->skeeks->site) {
                        return \Yii::$app->skeeks->site->id;
                    }
                },
            ],
            [
                'user_ip',
                'default',
                'value' => function () {
                    return \Yii::$app->request->userIP;
                },
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'user_ip'             => 'IP',
            'cms_sms_provider_id' => 'Sms провайдер',
            'phone'               => 'Телефон',
            'message'             => 'Сообщение',
            'status'              => 'Статус',
            'provider_status'     => 'Статус sms провайдера',
        ]);
    }

    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSite()
    {
        $className = \Yii::$app->skeeks->siteClass;
        return $this->hasOne($className::className(), ['id' => 'cms_site_id']);
    }
    /**
     * Gets query for [[CmsSite]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsSmsProvider()
    {
        return $this->hasOne(CmsSmsProvider::class, ['id' => 'cms_sms_provider_id']);
    }
}
