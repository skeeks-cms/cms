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
 * This is the model class for table "cms_callcheck_message".
 *
 * @property int                  $id
 * @property int|null             $created_by
 * @property int|null             $updated_by
 * @property int|null             $created_at
 * @property int|null             $updated_at
 * @property string|null          $user_ip
 * @property int                  $cms_site_id
 * @property int|null             $cms_callcheck_provider_id
 * @property string               $phone
 * @property string               $status
 * @property string|null          $code
 * @property string|null          $error_message
 * @property string|null          $provider_status
 * @property string|null          $provider_call_id
 * @property string|null          $provider_response_data
 *
 * @property CmsCallcheckProvider $cmsCallcheckProvider
 * @property CmsSite              $cmsSite
 */
class CmsCallcheckMessage extends \skeeks\cms\base\ActiveRecord
{
    const STATUS_ERROR = "error";
    const STATUS_NEW = "new";
    const STATUS_DELIVERED = "delivered";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cms_callcheck_message';
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
            [['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_site_id', 'cms_callcheck_provider_id'], 'integer'],
            [['cms_site_id', 'phone', 'status'], 'required'],
            [['provider_response_data'], 'string'],
            [['user_ip'], 'string', 'max' => 20],
            [['phone', 'status', 'code', 'error_message', 'provider_status', 'provider_call_id'], 'string', 'max' => 255],
            [['cms_callcheck_provider_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsCallcheckProvider::className(), 'targetAttribute' => ['cms_callcheck_provider_id' => 'id']],
            [['cms_site_id'], 'exist', 'skipOnError' => true, 'targetClass' => CmsSite::className(), 'targetAttribute' => ['cms_site_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => CmsUser::className(), 'targetAttribute' => ['updated_by' => 'id']],

            [
                ['error_message', 'provider_status', 'provider_call_id'],
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
     * Gets query for [[CmsCallcheckProvider]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCallcheckProvider()
    {
        return $this->hasOne(CmsCallcheckProvider::className(), ['id' => 'cms_callcheck_provider_id']);
    }
}