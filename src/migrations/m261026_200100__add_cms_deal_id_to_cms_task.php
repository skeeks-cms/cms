<?php

use yii\db\Migration;

/**
 * "Сделка как услуга", step У2: a task of a service lives in its deal and may
 * point at a resource of that deal; an internal task of a deal is hidden from
 * the client.
 */
class m261026_200100__add_cms_deal_id_to_cms_task extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_task}}', 'cms_deal_id', $this->integer()->null()->after('cms_lead_id')->comment('Сделка'));
        $this->addColumn('{{%cms_task}}', 'deal_resource_type', $this->string(64)->null()->after('cms_deal_id')->comment('Тип ресурса сделки'));
        $this->addColumn('{{%cms_task}}', 'deal_resource_id', $this->integer()->null()->after('deal_resource_type')->comment('Ресурс сделки'));
        $this->addColumn('{{%cms_task}}', 'is_internal', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('deal_resource_id')->comment('Внутренняя: клиент не видит'));

        $this->createIndex('cms_task__cms_deal_id', '{{%cms_task}}', 'cms_deal_id');
        $this->createIndex('cms_task__deal_resource', '{{%cms_task}}', ['deal_resource_type', 'deal_resource_id']);
        $this->addForeignKey('cms_task__cms_deal_id', '{{%cms_task}}', 'cms_deal_id', '{{%cms_deal}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('cms_task__cms_deal_id', '{{%cms_task}}');
        $this->dropIndex('cms_task__deal_resource', '{{%cms_task}}');
        $this->dropIndex('cms_task__cms_deal_id', '{{%cms_task}}');
        $this->dropColumn('{{%cms_task}}', 'is_internal');
        $this->dropColumn('{{%cms_task}}', 'deal_resource_id');
        $this->dropColumn('{{%cms_task}}', 'deal_resource_type');
        $this->dropColumn('{{%cms_task}}', 'cms_deal_id');
    }
}
