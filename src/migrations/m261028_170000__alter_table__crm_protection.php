<?php

use skeeks\cms\crm\CrmProtection;
use yii\db\Migration;

/**
 * П2д (owner's decision 28.10.2026, cms-communication ARCHITECTURE.md п. 13):
 * the protection of a company and a client person instead of the hand flag
 * «Доступ только по согласованию» — free | protected (a payment for the last N
 * months or an active service) | always (by hand). The records with the old
 * flag become «Всегда по согласованию»; the others are calculated from their
 * payments and services (crm\CrmProtection). The old flag column stays for a
 * rollback.
 */
class m261028_170000__alter_table__crm_protection extends Migration
{
    public function safeUp()
    {
        foreach (['{{%cms_company}}', '{{%cms_user}}'] as $table) {
            $this->addColumn($table, 'crm_protection', $this->string(16)->notNull()->defaultValue('free')->comment('Защита клиента: free, protected, always'));
            $this->addColumn($table, 'crm_protected_until', $this->integer()->null()->comment('Под защитой до'));
            $this->addColumn($table, 'crm_protection_reason', $this->string(255)->null()->comment('Основание защиты'));
            $this->createIndex(str_replace(['{{%', '}}'], '', $table).'__crm_protection', $table, 'crm_protection');
            $this->update($table, ['crm_protection' => 'always', 'crm_protection_reason' => 'Включено вручную'], ['crm_access_by_approval' => 1]);
        }
        $this->db->getSchema()->refresh();
        $count = (new CrmProtection())->recalcMany(true);
        echo "    > protection calculated for {$count} clients\n";
    }

    public function safeDown()
    {
        foreach (['{{%cms_company}}', '{{%cms_user}}'] as $table) {
            $this->update($table, ['crm_access_by_approval' => 1], ['crm_protection' => 'always']);
            $this->dropIndex(str_replace(['{{%', '}}'], '', $table).'__crm_protection', $table);
            $this->dropColumn($table, 'crm_protection_reason');
            $this->dropColumn($table, 'crm_protected_until');
            $this->dropColumn($table, 'crm_protection');
        }
    }
}
