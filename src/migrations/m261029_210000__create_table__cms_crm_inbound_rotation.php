<?php

use yii\db\Migration;

/**
 * «Входящие заявки» by turns (п. 13б, crm\InboundQueue): the last employee of
 * the queue who got a request, one row per site. The row is locked
 * (SELECT … FOR UPDATE) in the transaction that creates the deal, so two
 * requests at the same moment never go to the same employee by mistake.
 */
class m261029_210000__create_table__cms_crm_inbound_rotation extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_crm_inbound_rotation}}';
        if ($this->db->getTableSchema($tableName, true) !== null) {
            return true;
        }
        $tableOptions = $this->db->driverName === 'mysql' ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB' : null;
        $this->createTable($tableName, [
            'id' => $this->primaryKey(),
            'cms_site_id' => $this->integer()->notNull(),
            'queue' => $this->string(64)->notNull()->defaultValue('default')->comment('Очередь: default — настройки CRM'),
            'last_user_id' => $this->integer()->null()->comment('Кому ушла последняя заявка'),
            'updated_at' => $this->integer()->null(),
        ], $tableOptions);
        $this->createIndex('cms_crm_inbound_rotation__site_queue', $tableName, ['cms_site_id', 'queue'], true);
        $this->addForeignKey('cms_crm_inbound_rotation__cms_site_id', $tableName, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable($tableName, 'Входящие заявки по кругу: кому ушла последняя');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_crm_inbound_rotation}}');
    }
}
