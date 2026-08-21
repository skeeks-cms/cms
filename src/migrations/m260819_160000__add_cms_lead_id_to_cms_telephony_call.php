<?php

use yii\db\Migration;

class m260819_160000__add_cms_lead_id_to_cms_telephony_call extends Migration
{
    public function safeUp()
    {
        $duplicate = $this->db->createCommand(
            'SELECT cms_telephony_provider_id, provider_call_id, COUNT(*) AS duplicate_count '
            .'FROM {{%cms_telephony_call}} '
            .'GROUP BY cms_telephony_provider_id, provider_call_id '
            .'HAVING COUNT(*) > 1 LIMIT 1'
        )->queryOne();
        if ($duplicate) {
            throw new RuntimeException(sprintf(
                'Cannot add unique provider-call index: provider %d, call %s has %d rows.',
                (int)$duplicate['cms_telephony_provider_id'],
                (string)$duplicate['provider_call_id'],
                (int)$duplicate['duplicate_count']
            ));
        }

        $this->addColumn('cms_telephony_call', 'cms_lead_id', $this->integer()->null()->after('cms_company_id')->comment('Лид'));
        $this->createIndex('cms_telephony_call__cms_lead_id', 'cms_telephony_call', 'cms_lead_id');
        $this->createIndex(
            'cms_telephony_call__provider_call_unique',
            'cms_telephony_call',
            ['cms_telephony_provider_id', 'provider_call_id'],
            true
        );
        $this->addForeignKey(
            'cms_telephony_call__cms_lead_id',
            'cms_telephony_call',
            'cms_lead_id',
            'cms_lead',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('cms_telephony_call__cms_lead_id', 'cms_telephony_call');
        $this->dropIndex('cms_telephony_call__provider_call_unique', 'cms_telephony_call');
        $this->dropIndex('cms_telephony_call__cms_lead_id', 'cms_telephony_call');
        $this->dropColumn('cms_telephony_call', 'cms_lead_id');
    }
}
