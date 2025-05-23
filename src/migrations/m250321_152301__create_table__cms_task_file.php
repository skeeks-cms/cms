<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250321_152301__create_table__cms_task_file extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_task_file';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [

            'id' => $this->primaryKey(),

            'created_by' => $this->integer()->null(),
            'created_at' => $this->integer()->null(),

            'updated_by' => $this->integer()->null(),
            'updated_at' => $this->integer()->null(),

            'cms_task_id' => $this->integer()->notNull(),
            'storage_file_id' => $this->integer()->notNull(),

            'priority' => $this->integer()->notNull()->defaultValue(100),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');

        $this->createIndex($tableName.'__updated_by', $tableName, 'updated_by');
        $this->createIndex($tableName.'__updated_at', $tableName, 'updated_at');

        $this->createIndex($tableName.'__cms_task_id', $tableName, ['cms_task_id']);
        $this->createIndex($tableName.'__storage_file_id', $tableName, ['storage_file_id']);

        $this->createIndex($tableName.'__uniq', $tableName, ['storage_file_id', 'cms_task_id'], true);

        $this->addCommentOnTable($tableName, 'Файлы к задаче');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__updated_by", $tableName,
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_task_id", $tableName,
            'cms_task_id', '{{%cms_task}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__storage_file_id", $tableName,
            'storage_file_id', '{{%cms_storage_file}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

