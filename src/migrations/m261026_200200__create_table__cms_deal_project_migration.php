<?php

use yii\db\Migration;

/**
 * "Сделка как услуга", step У2: journal of moving projects into deals. One row per
 * project is written in the same transaction as the move, so a failure on a later
 * project or of an export file never leaves moved data without its undo record.
 */
class m261026_200200__create_table__cms_deal_project_migration extends Migration
{
    public function safeUp()
    {
        $tableOptions = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%cms_deal_project_migration}}', [
            'id'             => $this->primaryKey(),
            'created_at'     => $this->integer()->notNull(),
            'run_key'        => $this->string(32)->notNull()->comment('Запуск переноса'),
            'cms_project_id' => $this->integer()->notNull(),
            'cms_deal_id'    => $this->integer()->notNull(),
            'task_ids'       => $this->text()->null()->comment('JSON: задачи, получившие сделку'),
            'grant_ids'      => $this->text()->null()->comment('JSON: созданные доступы'),
            'reverted_at'    => $this->integer()->null(),
            'revert_note'    => $this->text()->null(),
        ], $tableOptions);
        $this->createIndex('cms_deal_project_migration__run_key', '{{%cms_deal_project_migration}}', 'run_key');
        $this->addCommentOnTable('{{%cms_deal_project_migration}}', 'Журнал переноса проектов в сделки');
    }

    public function safeDown()
    {
        $this->dropTable('{{%cms_deal_project_migration}}');
    }
}
