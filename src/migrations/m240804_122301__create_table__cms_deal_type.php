<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m240804_122301__create_table__cms_deal_type extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_deal_type';
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

            'name' => $this->string()->notNull(),
            'description' => $this->text()->null(),

            'is_periodic' => $this->integer(1)->notNull()->defaultValue(0),
            'period' => $this->string(10)->null(),


        ], $tableOptions);

        $this->createIndex($tableName.'__created_by', $tableName, 'created_by');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__name', $tableName, 'name');
        $this->createIndex($tableName.'__is_periodic', $tableName, 'is_periodic');
        $this->createIndex($tableName.'__period', $tableName, 'period');

        $this->addCommentOnTable($tableName, 'Типы сделок');

        $this->addForeignKey(
            "{$tableName}__created_by", $tableName,
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

