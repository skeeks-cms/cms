<?php

use yii\db\Migration;

class m260629_140000__alter_table__cms_task__parent_cms_task_id extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_task';
        $tableSchema = $this->db->getTableSchema($tableName, true);

        if (!$tableSchema) {
            return true;
        }

        if (!isset($tableSchema->columns['parent_cms_task_id'])) {
            $this->addColumn($tableName, 'parent_cms_task_id', $this->integer()->null()->after('cms_user_id')->comment('Связанная задача'));
            $this->createIndex($tableName.'__parent_cms_task_id', $tableName, 'parent_cms_task_id');
            $this->addForeignKey(
                $tableName.'__parent_cms_task_id',
                $tableName,
                'parent_cms_task_id',
                $tableName,
                'id',
                'SET NULL',
                'SET NULL'
            );
        }

        return true;
    }

    public function safeDown()
    {
        echo "m260629_140000__alter_table__cms_task__parent_cms_task_id cannot be reverted.\n";
        return false;
    }
}
