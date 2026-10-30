<?php

use skeeks\cms\crm\CrmProtection;
use skeeks\cms\db\RepeatableMigrationTrait;
use yii\db\Migration;

/**
 * П2д (owner's decision 28.10.2026, platform-plan crm/links-contract.md п. 13):
 * the protection of a company and a client person instead of the hand flag
 * «Доступ только по согласованию» — free | protected (a payment for the last N
 * months or an active service) | always (by hand). The records with the old
 * flag become «Всегда по согласованию»; the others are calculated from their
 * payments and services (crm\CrmProtection). The old flag column stays for a
 * rollback.
 */
class m261028_170000__alter_table__crm_protection extends Migration
{
    use RepeatableMigrationTrait;

    public function safeUp()
    {
        // The index is created last: with it the table is finished, and a repeated run does not
        // turn «always» on again for a record whose protection was changed since.
        foreach (['{{%cms_company}}', '{{%cms_user}}'] as $table) {
            $index = str_replace(['{{%', '}}'], '', $table).'__crm_protection';
            if ($this->hasIndex($table, $index)) {
                continue;
            }
            $this->addColumnIfMissing($table, 'crm_protection', $this->string(16)->notNull()->defaultValue('free')->comment('Защита клиента: free, protected, always'));
            $this->addColumnIfMissing($table, 'crm_protected_until', $this->integer()->null()->comment('Под защитой до'));
            $this->addColumnIfMissing($table, 'crm_protection_reason', $this->string(255)->null()->comment('Основание защиты'));
            $this->update($table, ['crm_protection' => 'always', 'crm_protection_reason' => 'Включено вручную'], ['crm_access_by_approval' => 1]);
            $this->createIndexIfMissing($index, $table, 'crm_protection');
        }
        $this->db->getSchema()->refresh();
        $count = (new CrmProtection())->recalcMany(true);
        echo "    > protection calculated for {$count} clients\n";
    }

    public function safeDown()
    {
        // No data is lost: the computed protection comes back from payments and services
        // (php yii cms/crm/protection --all), the manual "always" is kept in crm_access_by_approval below.
        foreach (['{{%cms_company}}', '{{%cms_user}}'] as $table) {
            if ($this->hasColumn($table, 'crm_protection')) {
                $this->update($table, ['crm_access_by_approval' => 1], ['crm_protection' => 'always']);
            }
            $this->dropIndexIfExists(str_replace(['{{%', '}}'], '', $table).'__crm_protection', $table);
            $this->dropColumnIfExists($table, 'crm_protection_reason');
            $this->dropColumnIfExists($table, 'crm_protected_until');
            $this->dropColumnIfExists($table, 'crm_protection');
        }
    }
}
