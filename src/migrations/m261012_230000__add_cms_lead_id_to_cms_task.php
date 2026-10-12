<?php

use yii\db\Migration;

class m261012_230000__add_cms_lead_id_to_cms_task extends Migration
{
    public function safeUp()
    {
        $this->addColumn('cms_task', 'cms_lead_id', $this->integer()->null()->after('cms_user_id')->comment('Лид'));
        $this->createIndex('cms_task__cms_lead_id', 'cms_task', 'cms_lead_id');
        $this->addForeignKey(
            'cms_task__cms_lead_id',
            'cms_task',
            'cms_lead_id',
            'cms_lead',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('cms_task__cms_lead_id', 'cms_task');
        $this->dropIndex('cms_task__cms_lead_id', 'cms_task');
        $this->dropColumn('cms_task', 'cms_lead_id');
    }
}
