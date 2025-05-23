<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240729_132301__create_table__cms_department extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_department';
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

            'name' => $this->string(255)->notNull()->comment("Название"),
            'pid' => $this->integer()->null()->comment("Родительский отдел"),

            'worker_id' => $this->integer()->null()->comment("Руководитель отдела"),

            'sort' => $this->integer()->notNull()->defaultValue(100),

        ], $tableOptions);

        $this->createIndex($tableName.'__worker_id', $tableName, 'worker_id');
        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__sort', $tableName, 'sort');
        $this->createIndex($tableName.'__pid', $tableName, 'pid');

        $this->addCommentOnTable($tableName, 'Отделы компании');

        $this->addForeignKey(
            "{$tableName}__worker_id", $tableName,
            'worker_id', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            "{$tableName}__pid", $tableName,
            'pid', '{{%cms_department}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

