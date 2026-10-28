<?php

namespace skeeks\cms\models;

use skeeks\cms\base\ActiveRecord;

/**
 * An event of the access to a client (crm\CrmAccessLog): who, to whom, the
 * client, the level before and after, the ground. Written by the CRM only,
 * never by a form; the tab «Доступы» of «Компании» lists them.
 *
 * @property int         $id
 * @property int         $created_at
 * @property int|null    $cms_site_id
 * @property string      $event
 * @property string      $record_code company|client
 * @property int         $record_id
 * @property int|null    $actor_id
 * @property int|null    $user_id
 * @property string|null $level_from
 * @property string|null $level_to
 * @property int|null    $cms_lead_id
 * @property int|null    $shop_payment_id
 * @property int|null    $request_id
 * @property string|null $comment
 *
 * @property CmsUser|null $actor
 * @property CmsUser|null $user
 * @property CmsLead|null $lead
 * @property CmsCrmAccessRequest|null $request
 */
class CmsCrmAccessLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%cms_crm_access_log}}';
    }

    /** No timestamps or authors of the base: the journal sets them itself. */
    public function behaviors()
    {
        return [];
    }

    public function rules()
    {
        return [
            [['created_at', 'event', 'record_code', 'record_id'], 'required'],
            [['created_at', 'cms_site_id', 'record_id', 'actor_id', 'user_id', 'cms_lead_id', 'shop_payment_id', 'request_id'], 'integer'],
            [['event'], 'string', 'max' => 32],
            [['record_code', 'level_from', 'level_to'], 'string', 'max' => 16],
            [['comment'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels()
    {
        return [
            'created_at' => 'Когда',
            'event' => 'Событие',
            'record_id' => 'Клиент',
            'actor_id' => 'Кто',
            'user_id' => 'Кому',
            'level_from' => 'Было',
            'level_to' => 'Стало',
            'cms_lead_id' => 'Сделка',
            'comment' => 'Комментарий',
        ];
    }

    public function getActor() { return $this->hasOne(CmsUser::class, ['id' => 'actor_id']); }
    public function getUser() { return $this->hasOne(CmsUser::class, ['id' => 'user_id']); }
    public function getLead() { return $this->hasOne(CmsLead::class, ['id' => 'cms_lead_id']); }
    public function getRequest() { return $this->hasOne(CmsCrmAccessRequest::class, ['id' => 'request_id']); }

    /** The company or the client person, read without a visibility scope. */
    public function getRecord(): ?\yii\db\ActiveRecord
    {
        return $this->record_code === CmsCrmAccessRequest::RECORD_COMPANY ? CmsCompany::findOne((int)$this->record_id) : CmsUser::findOne((int)$this->record_id);
    }

    public function getEventName(): string
    {
        return \skeeks\cms\crm\CrmAccessLog::events()[$this->event] ?? $this->event;
    }

    public function asText()
    {
        return $this->eventName.' №'.(int)$this->id;
    }
}
