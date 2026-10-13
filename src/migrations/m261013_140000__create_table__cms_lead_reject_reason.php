<?php

use yii\db\Migration;

class m261013_140000__create_table__cms_lead_reject_reason extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_lead_reject_reason}}';
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable($tableName, [
            'id'           => $this->primaryKey(),
            'created_by'   => $this->integer()->null(),
            'updated_by'   => $this->integer()->null(),
            'created_at'   => $this->integer()->null(),
            'updated_at'   => $this->integer()->null(),
            'cms_site_id'  => $this->integer()->notNull(),
            'name'         => $this->string(255)->notNull(),
            'sort'         => $this->integer()->notNull()->defaultValue(100),
            'is_no_answer' => $this->boolean()->notNull()->defaultValue(0)->comment('Предлагается кнопкой «Не дозвонились»'),
        ], $tableOptions);

        $this->createIndex('cms_lead_reject_reason__site_sort', $tableName, ['cms_site_id', 'sort']);
        $this->createIndex('cms_lead_reject_reason__site_name', $tableName, ['cms_site_id', 'name'], true);
        foreach (['created_by', 'updated_by'] as $column) {
            $this->addForeignKey('cms_lead_reject_reason__'.$column, $tableName, $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        }
        $this->addForeignKey('cms_lead_reject_reason__cms_site_id', $tableName, 'cms_site_id', '{{%cms_site}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable($tableName, 'Причины отказа по лидам');

        $this->addColumn('{{%cms_lead}}', 'cms_lead_reject_reason_id', $this->integer()->null()->after('reject_reason')->comment('Причина отказа'));
        $this->createIndex('cms_lead__cms_lead_reject_reason_id', '{{%cms_lead}}', 'cms_lead_reject_reason_id');
        // Deleting a used reason is refused by the model; SET NULL is the last safety net.
        $this->addForeignKey('cms_lead__cms_lead_reject_reason_id', '{{%cms_lead}}', 'cms_lead_reject_reason_id', $tableName, 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('cms_lead__cms_lead_reject_reason_id', '{{%cms_lead}}');
        $this->dropIndex('cms_lead__cms_lead_reject_reason_id', '{{%cms_lead}}');
        $this->dropColumn('{{%cms_lead}}', 'cms_lead_reject_reason_id');
        $this->dropTable('{{%cms_lead_reject_reason}}');
    }
}
