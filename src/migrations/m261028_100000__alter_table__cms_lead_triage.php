<?php

use yii\db\Migration;

/**
 * П2а: разбор входящих сделок («Неразобранное»), запросы доступа к чужому
 * клиенту и отметки «возможный дубль».
 *
 * - cms_lead.triage_result: чем закончился разбор, если это не продажа
 *   (merged — добавлено в другую сделку, support — передано в поддержку,
 *   spam). Такая сделка закрыта статусом rejected и не считается отказом.
 * - cms_lead.merged_into_id: сделка, в которую добавлено обращение; повтор
 *   события того же источника уходит туда.
 * - cms_lead.triaged_by / triaged_at: кто и когда разобрал.
 * - cms_crm_access_request: «поручить мне сделку» / «передать мне клиента».
 * - cms_crm_duplicate_mark: «это другая компания» с причиной.
 */
class m261028_100000__alter_table__cms_lead_triage extends Migration
{
    public function safeUp()
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB' : null;

        $this->addColumn('{{%cms_lead}}', 'triage_result', $this->string(16)->null()->comment('Итог разбора: merged, support, spam'));
        $this->addColumn('{{%cms_lead}}', 'merged_into_id', $this->integer()->null()->comment('Добавлено в сделку'));
        $this->addColumn('{{%cms_lead}}', 'triaged_by', $this->integer()->null()->comment('Кто разобрал'));
        $this->addColumn('{{%cms_lead}}', 'triaged_at', $this->integer()->null()->comment('Когда разобрана'));
        $this->addForeignKey('cms_lead__merged_into_id', '{{%cms_lead}}', 'merged_into_id', '{{%cms_lead}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('cms_lead__triaged_by', '{{%cms_lead}}', 'triaged_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->createIndex('cms_lead__triage_queue', '{{%cms_lead}}', ['cms_site_id', 'status', 'executor_id']);

        $this->createTable('{{%cms_crm_access_request}}', [
            'id' => $this->primaryKey(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'cms_site_id' => $this->integer()->null(),
            'requester_id' => $this->integer()->notNull()->comment('Кто просит'),
            'owner_id' => $this->integer()->null()->comment('Ответственный клиента на момент запроса'),
            'record_code' => $this->string(16)->notNull()->comment('company или client'),
            'record_id' => $this->integer()->notNull(),
            'cms_lead_id' => $this->integer()->null()->comment('Сделка, которую просят поручить'),
            'kind' => $this->string(16)->notNull()->comment('delegate или transfer'),
            'comment' => $this->text()->null(),
            'status' => $this->string(16)->notNull()->defaultValue('pending'),
            'decided_by' => $this->integer()->null(),
            'decided_at' => $this->integer()->null(),
            'decision_comment' => $this->text()->null(),
        ], $options);
        $this->createIndex('cms_crm_access_request__record', '{{%cms_crm_access_request}}', ['record_code', 'record_id', 'status']);
        $this->createIndex('cms_crm_access_request__requester', '{{%cms_crm_access_request}}', ['requester_id', 'status']);
        $this->addForeignKey('cms_crm_access_request__requester_id', '{{%cms_crm_access_request}}', 'requester_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('cms_crm_access_request__owner_id', '{{%cms_crm_access_request}}', 'owner_id', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('cms_crm_access_request__decided_by', '{{%cms_crm_access_request}}', 'decided_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('cms_crm_access_request__cms_lead_id', '{{%cms_crm_access_request}}', 'cms_lead_id', '{{%cms_lead}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%cms_crm_duplicate_mark}}', [
            'id' => $this->primaryKey(),
            'created_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),
            'record_code' => $this->string(16)->notNull()->comment('company или client'),
            'record_id' => $this->integer()->notNull(),
            'other_code' => $this->string(16)->notNull(),
            'other_id' => $this->integer()->notNull(),
            'reason' => $this->text()->notNull()->comment('Почему это другая запись'),
            'status' => $this->string(16)->notNull()->defaultValue('open'),
            'resolved_by' => $this->integer()->null(),
            'resolved_at' => $this->integer()->null(),
        ], $options);
        $this->createIndex('cms_crm_duplicate_mark__record', '{{%cms_crm_duplicate_mark}}', ['record_code', 'record_id']);
        $this->createIndex('cms_crm_duplicate_mark__other', '{{%cms_crm_duplicate_mark}}', ['other_code', 'other_id']);
        $this->addForeignKey('cms_crm_duplicate_mark__created_by', '{{%cms_crm_duplicate_mark}}', 'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('cms_crm_duplicate_mark__resolved_by', '{{%cms_crm_duplicate_mark}}', 'resolved_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_crm_duplicate_mark}}');
        $this->dropTable('{{%cms_crm_access_request}}');
        $this->dropIndex('cms_lead__triage_queue', '{{%cms_lead}}');
        $this->dropForeignKey('cms_lead__triaged_by', '{{%cms_lead}}');
        $this->dropForeignKey('cms_lead__merged_into_id', '{{%cms_lead}}');
        $this->dropColumn('{{%cms_lead}}', 'triaged_at');
        $this->dropColumn('{{%cms_lead}}', 'triaged_by');
        $this->dropColumn('{{%cms_lead}}', 'merged_into_id');
        $this->dropColumn('{{%cms_lead}}', 'triage_result');
    }
}
