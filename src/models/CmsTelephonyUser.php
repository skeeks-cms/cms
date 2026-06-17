<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\models;

use skeeks\cms\models\CmsUser;
use yii\helpers\ArrayHelper;

/**
 * @property int $id
 *
 * @property int|null $cms_worker_user_id
 *
 * @property int $cms_telephony_provider_id
 *
 * @property string $provider_user_num
 *
 * @property string|null $sip_uri
 * @property string|null $sip_password
 *
 * @property string|null $ws_url
 * @property string|null $display_name
 * @property string|null $ice_servers
 *
 * @property int $is_active
 *
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property CmsWorkerUser $workerUser
 * @property CmsTelephonyProvider $provider
 */
class CmsTelephonyUser extends \skeeks\cms\models\Core
{
    public static function tableName()
    {
        return 'cms_telephony_user';
    }

    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [

            // required
            [
                ['cms_telephony_provider_id',
                'provider_user_num',
            ], 'required'],

            // integer
            [
                [
                    'cms_worker_user_id',
                    'cms_telephony_provider_id',
                    'is_active',
                ],
                'integer'
            ],

            // strings
            [['provider_user_num'], 'string', 'max' => 32],
            [['sip_uri', 'sip_password', 'ws_url', 'display_name'], 'string', 'max' => 255],
            [['ice_servers'], 'string'],

            // defaults
            ['is_active', 'default', 'value' => 1],

            // unique (как в БД)
            [
                ['cms_worker_user_id', 'cms_telephony_provider_id'],
                'unique',
                'targetAttribute' => ['cms_worker_user_id', 'cms_telephony_provider_id'],
                'message' => 'Для данного пользователя уже задан SIP-аккаунт у этого провайдера.',
            ],

            [
                ['provider_user_num', 'cms_telephony_provider_id'],
                'unique',
                'targetAttribute' => ['provider_user_num', 'cms_telephony_provider_id'],
                'message' => 'Для данного провайдера уже есть другой аккаунт с таким внутренним номером.',
            ],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'cms_worker_user_id' => 'Сотрудник CRM',
            'cms_telephony_provider_id' => 'Провайдер телефонии',

            'provider_user_num' => 'Внутренний номер',
            'sip_uri' => 'SIP логин',
            'sip_password' => 'SIP пароль',

            'ws_url' => 'WebSocket URL',
            'display_name' => 'Отображаемое имя',
            'ice_servers' => 'ICE / STUN / TURN серверы',

            'is_active' => 'Активен',

            'created_at' => 'Создано',
            'updated_at' => 'Обновлено',
        ]);
    }

    /* ========================= RELATIONS ========================= */

    public function getWorkerUser()
    {
        return $this->hasOne(CmsUser::class, ['id' => 'cms_worker_user_id']);
    }

    public function getProvider()
    {
        return $this->hasOne(CmsTelephonyProvider::class, ['id' => 'cms_telephony_provider_id']);
    }

    /* ========================= HELPERS ========================= */

    public function asText()
    {
        return $this->provider->name . " / " . $this->provider_user_num;
    }

    public function getIceServers(): array
    {
        return $this->ice_servers
            ? (array) json_decode($this->ice_servers, true)
            : [];
    }

    public function setIceServers(array $servers)
    {
        $this->ice_servers = json_encode($servers);
    }
}
