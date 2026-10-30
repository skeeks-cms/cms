<?php

use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * The journal of the access to clients (owner's decision 29.10.2026): every
 * event — a request and its decision, «Работать с этим клиентом», «Взять в
 * работу», an access given, changed or removed, a transfer, an automatic main
 * responsible, a return to the pool — with who, to whom, the client, the level
 * before and after and the ground (a deal, a payment, a request). The requests
 * made before it are copied in (a request and its decision as two events);
 * a repeated run copies only the requests the journal does not have yet.
 * crm\CrmAccessLog, the tab «Доступы» of «Компании».
 */
class m261029_130000__create_table__cms_crm_access_log extends Migration
{
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        $this->createTableIfMissing('{{%cms_crm_access_log}}', [
            'id' => $this->primaryKey(),
            'created_at' => $this->integer()->notNull(),
            'cms_site_id' => $this->integer()->null(),
            'event' => $this->string(32)->notNull()->comment('Событие: request, request_approved, work, take, grant, level, remove, transfer, main_auto, pool…'),
            'record_code' => $this->string(16)->notNull()->comment('company | client'),
            'record_id' => $this->integer()->notNull(),
            'actor_id' => $this->integer()->null()->comment('Кто сделал'),
            'user_id' => $this->integer()->null()->comment('Кому'),
            'level_from' => $this->string(16)->null(),
            'level_to' => $this->string(16)->null(),
            'cms_lead_id' => $this->integer()->null(),
            'shop_payment_id' => $this->integer()->null(),
            'request_id' => $this->integer()->null(),
            'comment' => $this->string(500)->null(),
        ]);
        $this->createIndexIfMissing('cms_crm_access_log__record', '{{%cms_crm_access_log}}', ['record_code', 'record_id', 'created_at']);
        $this->createIndexIfMissing('cms_crm_access_log__created_at', '{{%cms_crm_access_log}}', 'created_at');
        $this->createIndexIfMissing('cms_crm_access_log__actor', '{{%cms_crm_access_log}}', 'actor_id');
        $this->createIndexIfMissing('cms_crm_access_log__user', '{{%cms_crm_access_log}}', 'user_id');
        $this->createIndexIfMissing('cms_crm_access_log__event', '{{%cms_crm_access_log}}', 'event');
        $this->createIndexIfMissing('cms_crm_access_log__request', '{{%cms_crm_access_log}}', 'request_id');

        // The requests made before the journal: the request and, when decided, its decision.
        $this->execute("INSERT INTO {{%cms_crm_access_log}} (created_at, cms_site_id, event, record_code, record_id, actor_id, user_id, level_to, cms_lead_id, request_id, comment)
            SELECT created_at, cms_site_id, 'request', record_code, record_id, requester_id, requester_id, level, cms_lead_id, id, LEFT(comment, 500)
            FROM {{%cms_crm_access_request}} r
            WHERE NOT EXISTS (SELECT 1 FROM {{%cms_crm_access_log}} l WHERE l.request_id = r.id AND l.event = 'request')");
        $this->execute("INSERT INTO {{%cms_crm_access_log}} (created_at, cms_site_id, event, record_code, record_id, actor_id, user_id, level_to, cms_lead_id, request_id, comment)
            SELECT COALESCE(decided_at, updated_at, created_at), cms_site_id,
                CASE status WHEN 'approved' THEN 'request_approved' WHEN 'declined' THEN 'request_declined'
                    WHEN 'cancelled' THEN 'request_cancelled' ELSE 'request_obsolete' END,
                record_code, record_id, COALESCE(decided_by, requester_id), requester_id,
                CASE status WHEN 'approved' THEN level ELSE NULL END, cms_lead_id, id, LEFT(decision_comment, 500)
            FROM {{%cms_crm_access_request}} r WHERE status <> 'pending'
                AND NOT EXISTS (SELECT 1 FROM {{%cms_crm_access_log}} l WHERE l.request_id = r.id AND l.event LIKE 'request\_%')");
    }

    public function safeDown()
    {
        // The journal of the access is kept for good (platform-plan crm/links-contract.md п. 13).
        $this->refuseRollbackWithData([
            'события журнала доступа' => $this->hasTable('{{%cms_crm_access_log}}') ? (new \yii\db\Query())->from('{{%cms_crm_access_log}}') : null,
        ]);
        $this->dropTableIfExists('{{%cms_crm_access_log}}');
    }
}
