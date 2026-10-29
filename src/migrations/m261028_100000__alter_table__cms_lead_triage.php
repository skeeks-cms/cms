<?php

use skeeks\cms\db\RepeatableMigrationTrait;
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
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        $options = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB' : null;

        $this->addColumnIfMissing('{{%cms_lead}}', 'triage_result', $this->string(16)->null()->comment('Итог разбора: merged, support, spam'));
        $this->addColumnIfMissing('{{%cms_lead}}', 'merged_into_id', $this->integer()->null()->comment('Добавлено в сделку'));
        $this->addColumnIfMissing('{{%cms_lead}}', 'triaged_by', $this->integer()->null()->comment('Кто разобрал'));
        $this->addColumnIfMissing('{{%cms_lead}}', 'triaged_at', $this->integer()->null()->comment('Когда разобрана'));
        $this->addForeignKeyIfMissing('cms_lead__merged_into_id', '{{%cms_lead}}', 'merged_into_id', '{{%cms_lead}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKeyIfMissing('cms_lead__triaged_by', '{{%cms_lead}}', 'triaged_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->createIndexIfMissing('cms_lead__triage_queue', '{{%cms_lead}}', ['cms_site_id', 'status', 'executor_id']);

        $this->createTableIfMissing('{{%cms_crm_access_request}}', [
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
        $this->createIndexIfMissing('cms_crm_access_request__record', '{{%cms_crm_access_request}}', ['record_code', 'record_id', 'status']);
        $this->createIndexIfMissing('cms_crm_access_request__requester', '{{%cms_crm_access_request}}', ['requester_id', 'status']);
        $this->addForeignKeyIfMissing('cms_crm_access_request__requester_id', '{{%cms_crm_access_request}}', 'requester_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKeyIfMissing('cms_crm_access_request__owner_id', '{{%cms_crm_access_request}}', 'owner_id', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKeyIfMissing('cms_crm_access_request__decided_by', '{{%cms_crm_access_request}}', 'decided_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKeyIfMissing('cms_crm_access_request__cms_lead_id', '{{%cms_crm_access_request}}', 'cms_lead_id', '{{%cms_lead}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTableIfMissing('{{%cms_crm_duplicate_mark}}', [
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
        $this->createIndexIfMissing('cms_crm_duplicate_mark__record', '{{%cms_crm_duplicate_mark}}', ['record_code', 'record_id']);
        $this->createIndexIfMissing('cms_crm_duplicate_mark__other', '{{%cms_crm_duplicate_mark}}', ['other_code', 'other_id']);
        $this->addForeignKeyIfMissing('cms_crm_duplicate_mark__created_by', '{{%cms_crm_duplicate_mark}}', 'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKeyIfMissing('cms_crm_duplicate_mark__resolved_by', '{{%cms_crm_duplicate_mark}}', 'resolved_by', '{{%cms_user}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->refuseRollbackWithData([
            'отметки «Это другая компания»' => $this->hasTable('{{%cms_crm_duplicate_mark}}') ? (new \yii\db\Query())->from('{{%cms_crm_duplicate_mark}}') : null,
            'запросы доступа' => $this->hasTable('{{%cms_crm_access_request}}') ? (new \yii\db\Query())->from('{{%cms_crm_access_request}}') : null,
            'разобранные сделки' => $this->hasColumn('{{%cms_lead}}', 'triaged_at') && $this->hasColumn('{{%cms_lead}}', 'merged_into_id') && $this->hasColumn('{{%cms_lead}}', 'triage_result')
                ? (new \yii\db\Query())->from('{{%cms_lead}}')->andWhere(['or', ['not', ['triage_result' => null]], ['not', ['merged_into_id' => null]], ['not', ['triaged_at' => null]]])
                : null,
        ]);
        $this->dropTableIfExists('{{%cms_crm_duplicate_mark}}');
        $this->dropTableIfExists('{{%cms_crm_access_request}}');
        $this->dropIndexIfExists('cms_lead__triage_queue', '{{%cms_lead}}');
        $this->dropForeignKeyIfExists('cms_lead__triaged_by', '{{%cms_lead}}');
        $this->dropForeignKeyIfExists('cms_lead__merged_into_id', '{{%cms_lead}}');
        $this->dropColumnIfExists('{{%cms_lead}}', 'triaged_at');
        $this->dropColumnIfExists('{{%cms_lead}}', 'triaged_by');
        $this->dropColumnIfExists('{{%cms_lead}}', 'merged_into_id');
        $this->dropColumnIfExists('{{%cms_lead}}', 'triage_result');
    }
}
