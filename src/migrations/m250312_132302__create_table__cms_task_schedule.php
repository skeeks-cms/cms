<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250312_132302__create_table__cms_task_schedule extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_task_schedule';
        $tableExist = $this->db->getTableSchema($tableName, true);

        if ($tableExist) {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable($tableName, [

            'id' => $this->primaryKey()->notNull(),

            'created_by' => $this->integer(11),
            'created_at' => $this->integer(11),

            'cms_task_id' => $this->integer(11)->comment("Задача"),
            'cms_user_id' => $this->integer(11)->comment("Сотрудник"),

            'start_at' => $this->integer()->comment("Начало работы"),
            'end_at' => $this->integer()->comment("Завершение работы"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');

        $this->createIndex($tableName.'__start_at', $tableName, 'start_at');
        $this->createIndex($tableName.'__end_at', $tableName, 'end_at');

        $this->createIndex($tableName.'__cms_task_id', $tableName, 'cms_task_id');
        $this->createIndex($tableName.'__cms_user_id', $tableName, 'cms_user_id');

        $this->addCommentOnTable($tableName, 'Работа по задаче');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_task_id", $tableName,
            'cms_task_id', '{{%cms_task}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}