<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250312_132301__create_table__cms_task extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_task';
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

            'name' => $this->string(255)->notNull()->comment("Название"),
            'description' => $this->text()->comment("Описание проекта"),

            'executor_id' => $this->integer()->comment("Исполнитель"),

            'cms_project_id' => $this->integer()->null()->comment("Проект"),
            'cms_company_id' => $this->integer()->null()->comment("Компания"),
            'cms_user_id' => $this->integer()->null()->comment("Клиент"),

            'plan_start_at' => $this->integer()->null()->comment("Начало"),
            'plan_end_at' => $this->integer()->null()->comment("Завершение"),

            'plan_duration' => $this->integer()->null()->comment("Длительность по плану"),
            'fact_duration' => $this->integer()->null()->comment("Фактическая длидельность"),

            'status' => $this->string(255)->defaultValue("new")->comment("Статус"),

            'executor_sort' => $this->integer(11)->null()->comment("Сортировка у исполнителя"),
            'executor_end_at' => $this->integer()->comment("Ориентировочная дата исполнения задачи исполнителем"),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');

        $this->createIndex($tableName.'__name', $tableName, 'name');

        $this->createIndex($tableName.'__executor_id', $tableName, 'executor_id');

        $this->createIndex($tableName.'__cms_project_id', $tableName, 'cms_project_id');
        $this->createIndex($tableName.'__cms_company_id', $tableName, 'cms_company_id');
        $this->createIndex($tableName.'__cms_user_id', $tableName, 'cms_user_id');
        $this->createIndex($tableName.'__executor_end_at', $tableName, 'executor_end_at');
        $this->createIndex($tableName.'__executor_sort', $tableName, 'executor_sort');

        $this->addCommentOnTable($tableName, 'Задачи');


        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__executor_id", $tableName,
            'executor_id', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_user_id", $tableName,
            'cms_user_id', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_project_id", $tableName,
            'cms_project_id', '{{%cms_project}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__cms_company_id", $tableName,
            'cms_company_id', '{{%cms_company}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}