<?php

use yii\db\Migration;

class m260818_130000__create_table__cms_lead extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_lead}}';
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable($tableName, [
            'id'                => $this->primaryKey(),
            'created_by'        => $this->integer()->null(),
            'updated_by'        => $this->integer()->null(),
            'created_at'        => $this->integer()->null(),
            'updated_at'        => $this->integer()->null(),
            'cms_site_id'       => $this->integer()->null(),
            'submitted_by_id'   => $this->integer()->null(),
            'partner_id'        => $this->integer()->null(),
            'executor_id'       => $this->integer()->null(),
            'cms_company_id'    => $this->integer()->null(),
            'cms_user_id'       => $this->integer()->null(),
            'source_type'       => $this->string(32)->notNull()->defaultValue('manual'),
            'source_ref'        => $this->string(190)->null(),
            'source_name'       => $this->string(255)->null(),
            'source_url'        => $this->string(1000)->null(),
            'source_data'       => $this->text()->null(),
            'utm_source'        => $this->string(255)->null(),
            'utm_medium'        => $this->string(255)->null(),
            'utm_campaign'      => $this->string(255)->null(),
            'utm_content'       => $this->string(255)->null(),
            'utm_term'          => $this->string(255)->null(),
            'name'              => $this->string(255)->notNull(),
            'description'       => $this->text()->null(),
            'status'            => $this->string(32)->notNull()->defaultValue('new'),
            'reject_reason'     => $this->text()->null(),
            'result_comment'    => $this->text()->null(),
            'processed_at'      => $this->integer()->null(),
            'lock_version'      => $this->integer()->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->createIndex('cms_lead__source', $tableName, ['cms_site_id', 'source_type', 'source_ref'], true);
        $this->createIndex('cms_lead__status_executor', $tableName, ['status', 'executor_id']);
        $this->createIndex('cms_lead__partner', $tableName, 'partner_id');
        $this->createIndex('cms_lead__company', $tableName, 'cms_company_id');
        $this->createIndex('cms_lead__client', $tableName, 'cms_user_id');
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $column) {
            $this->createIndex('cms_lead__'.$column, $tableName, $column);
        }

        foreach (['created_by', 'updated_by', 'submitted_by_id', 'partner_id', 'executor_id', 'cms_user_id'] as $column) {
            $this->addForeignKey('cms_lead__'.$column, $tableName, $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        }
        $this->addForeignKey('cms_lead__cms_site_id', $tableName, 'cms_site_id', '{{%cms_site}}', 'id', 'SET NULL', 'SET NULL');
        $this->addForeignKey('cms_lead__cms_company_id', $tableName, 'cms_company_id', '{{%cms_company}}', 'id', 'SET NULL', 'SET NULL');
        $this->addCommentOnTable($tableName, 'Единый центр входящих лидов CRM');

        $this->createContactTable('cms_lead_phone', 'Телефоны лидов', $tableOptions);
        $this->createContactTable('cms_lead_email', 'Email-ы лидов', $tableOptions);

        return true;
    }

    public function safeDown()
    {
        $this->dropContactTable('cms_lead_email');
        $this->dropContactTable('cms_lead_phone');

        foreach (['created_by', 'updated_by', 'submitted_by_id', 'partner_id', 'executor_id', 'cms_user_id', 'cms_site_id', 'cms_company_id'] as $column) {
            $this->dropForeignKey('cms_lead__'.$column, '{{%cms_lead}}');
        }
        $this->dropTable('{{%cms_lead}}');

        return true;
    }

    private function createContactTable(string $tableName, string $comment, ?string $tableOptions): void
    {
        $this->createTable('{{%'.$tableName.'}}', [
            'id' => $this->primaryKey(),
            'created_by' => $this->integer()->null(),
            'updated_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),
            'cms_lead_id' => $this->integer()->notNull(),
            'value' => $this->string(255)->notNull(),
            'name' => $this->string(255)->null(),
            'sort' => $this->integer()->notNull()->defaultValue(500),
        ], $tableOptions);

        foreach (['created_by', 'updated_by', 'created_at', 'updated_at', 'cms_lead_id', 'sort'] as $column) {
            $this->createIndex($tableName.'__'.$column, '{{%'.$tableName.'}}', $column);
        }
        $this->createIndex($tableName.'__uniq2lead', '{{%'.$tableName.'}}', ['cms_lead_id', 'value'], true);
        foreach (['created_by', 'updated_by'] as $column) {
            $this->addForeignKey($tableName.'__'.$column, '{{%'.$tableName.'}}', $column, '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL');
        }
        $this->addForeignKey($tableName.'__cms_lead_id', '{{%'.$tableName.'}}', 'cms_lead_id', '{{%cms_lead}}', 'id', 'CASCADE', 'CASCADE');
        $this->addCommentOnTable('{{%'.$tableName.'}}', $comment);
    }

    private function dropContactTable(string $tableName): void
    {
        foreach (['created_by', 'updated_by', 'cms_lead_id'] as $column) {
            $this->dropForeignKey($tableName.'__'.$column, '{{%'.$tableName.'}}');
        }
        $this->dropTable('{{%'.$tableName.'}}');
    }
}
