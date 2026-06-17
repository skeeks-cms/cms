<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\models\behaviors\HasJsonFieldsBehavior;
use skeeks\cms\models\behaviors\HasStorageFile;
use skeeks\cms\models\queries\CmsTelephonyCallQuery;
use skeeks\cms\validators\PhoneValidator;
use yii\helpers\ArrayHelper;

/**
 * @property int                  $id
 *
 * @property int                  $cms_company_id
 * @property int                  $cms_telephony_provider_id
 *
 * @property int|null             $cms_worker_user_id
 * @property int|null             $cms_user_id
 *
 * @property string               $provider_call_id
 *
 * @property string               $direction
 * @property string               $status
 * @property string               $failed_reason
 * @property string               $provider_user_num
 * @property string               $provider_user_id
 *
 * @property string               $client_phone
 * @property string|null          $povider_phone
 *
 * @property string|null          $provider_phone_from
 * @property string|null          $provider_phone_to
 *
 * @property int|null             $started_at
 * @property int|null             $ended_at
 * @property int|null             $duration
 *
 * @property array|null           $provider_data
 *
 * @property int|null             $created_at
 * @property int|null             $updated_at
 * @property int|null             $created_by
 * @property int|null             $updated_by
 * @property string|null          $record_url
 * @property int|null          $cms_record_file_id
 *
 * @property bool               $isFinished
 * @property CmsStorageFile               $cmsRecordFile
 * @property string               $statusAsText
 * @property CmsCompany           $company
 * @property CmsTelephonyProvider $provider
 * @property CmsUser|null         $workerUser
 * @property CmsUser|null         $user
 */
class CmsTelephonyCall extends \skeeks\cms\models\Core
{
    /** ===================== DIRECTION ===================== */
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /** ===================== STATUS ===================== */
    public const STATUS_NEW = 'new';        // Создан в CRM (мы нажали "Позвонить")
    public const STATUS_RINGING = 'ringing';    // Идёт набор
    public const STATUS_CONVERSATION = 'conversation';     // Соединено (идет разговор)
    public const STATUS_ANSWERED = 'answered';     // Разговор состоялся
    public const STATUS_FAILED = 'failed';      // Завершён без соединения


    /** ===================== FIELD REASON ===================== */

    public const FAILED_BUSY = 'busy';
    public const FAILED_NOANSWER = 'noanswer';
    public const FAILED_CANCEL = 'cancel';
    public const FAILED_CONGESTION = 'congestion';
    public const FAILED_CHANUNAVAIL = 'chanunavail';


    public static function tableName()
    {
        return 'cms_telephony_call';
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            HasJsonFieldsBehavior::class => [
                'class'  => HasJsonFieldsBehavior::class,
                'fields' => ['provider_data'],
            ],

            HasStorageFile::className() =>
            [
                'class' => HasStorageFile::className(),
                'fields' => ['cms_record_file_id']
            ],
        ]);
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            // required
            [
                [
                    'cms_telephony_provider_id',
                    'provider_call_id',
                    'direction',
                    'status',
                    'client_phone',
                ],
                'required',
            ],

            // integer
            [
                [
                    'cms_company_id',
                    'cms_telephony_provider_id',
                    'cms_worker_user_id',
                    'cms_user_id',
                    'started_at',
                    'ended_at',
                    'duration',

                ],
                'integer',
            ],
            // integer
            [
                [
                    'cms_record_file_id',
                ],
                'safe',
            ],

            // strings
            [['provider_call_id'], 'string', 'max' => 128],
            [['direction', 'status', 'failed_reason'], 'string', 'max' => 16],
            [['provider_phone_from', 'provider_phone_to'], 'string', 'max' => 64],
            [['record_url'], 'string'],
            [['provider_user_num'], 'string'],
            [['provider_user_id'], 'string'],
            [['client_phone'], 'string'],

            [['client_phone'], PhoneValidator::class],

            [['provider_data'], 'safe'],

            // enums
            ['direction', 'in', 'range' => self::directions()],
            ['status', 'in', 'range' => array_keys(self::statuses())],

            // unique
            [
                ['cms_telephony_provider_id', 'provider_call_id'],
                'unique',
                'targetAttribute' => ['cms_telephony_provider_id', 'provider_call_id'],
                'message'         => 'Вызов с таким ID уже существует у данного провайдера.',
            ],
        ]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsRecordFile()
    {
        return $this->hasOne(CmsStorageFile::className(), ['id' => 'cms_record_file_id']);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_company_id' => 'Компания',

            'cms_telephony_provider_id' => 'Провайдер телефонии',

            'cms_worker_user_id' => 'Сотрудник (worker)',
            'cms_user_id'        => 'Пользователь CRM',

            'provider_call_id' => 'ID вызова у провайдера',

            'direction'     => 'Направление',
            'status'        => 'Статус',
            'failed_reason' => 'Причина завершения',

            'client_phone'   => 'Телефон клиента',
            'provider_phone' => 'Телефон провайдера',

            'provider_phone_from' => 'Откуда (данные провайдера)',
            'provider_phone_to'   => 'Куда (данные провайдера)',

            'started_at' => 'Начало звонка',
            'ended_at'   => 'Окончание звонка',
            'duration'   => 'Длительность (сек)',

            'provider_data' => 'Данные провайдера',
        ]);
    }

    /* ========================= RELATIONS ========================= */

    public function getProvider()
    {
        return $this->hasOne(
            CmsTelephonyProvider::class,
            ['id' => 'cms_telephony_provider_id']
        );
    }
    public function getCmsTelephonyProvider()
    {
        return $this->hasOne(
            CmsTelephonyProvider::class,
            ['id' => 'cms_telephony_provider_id']
        );
    }

    public function getCompany()
    {
        return $this->hasOne(
            CmsCompany::class,
            ['id' => 'cms_company_id']
        );
    }


    public function getUser()
    {
        return $this->hasOne(
            CmsUser::class,
            ['id' => 'cms_user_id']
        );
    }



    public function getWorkerUser()
    {
        return $this->hasOne(
            CmsUser::class,
            ['id' => 'cms_worker_user_id']
        );
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsCompany()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_company_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsUser()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_user_id']);
    }
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCmsWorkerUser()
    {
        return $this->hasOne(CmsCompany::class, ['id' => 'cms_worker_user_id']);
    }

    /* ========================= HELPERS ========================= */

    public function isIncoming(): bool
    {
        return $this->direction === 'in';
    }

    public function isOutgoing(): bool
    {
        return $this->direction === 'out';
    }

    public function getDurationFormatted(): string
    {
        $seconds = (int)$this->duration;
        return gmdate('H:i:s', $seconds);
    }

    public static function directions(): array
    {
        return [
            self::DIRECTION_IN,
            self::DIRECTION_OUT,
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW          => "Новый",
            self::STATUS_RINGING      => "Набор номера",
            self::STATUS_CONVERSATION => "Идет разговор",
            self::STATUS_ANSWERED     => "Завершен",
            self::STATUS_FAILED       => "Отменен",
        ];
    }

    public function getDuration(): int
    {
        if ($this->duration != 0) {
            return $this->duration;
        }

        if ($this->started_at && $this->ended_at) {
            return max(0, $this->ended_at - $this->started_at);
        }

        return 0;
    }

    public function getIsFinished()
    {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_ANSWERED,
        ]) ? true : false;
    }

    public function getStatusAsText(): string
    {
        return ArrayHelper::getValue(self::statuses(), $this->status, "Неизвестный");
    }

    public function asText()
    {
        $data = [];

        $data[] = $this->direction == self::DIRECTION_IN ? "Входщий звонок с номера: ": "Исходящий звонок на номер: ";
        $data[] = $this->client_phone;
        $data[] = \Yii::$app->formatter->asDatetime($this->created_at);

        return implode(" ", $data);
    }

    /**
     * @return CmsTaskQuery
     */
    public static function find()
    {
        return new CmsTelephonyCallQuery(get_called_class());
    }
}