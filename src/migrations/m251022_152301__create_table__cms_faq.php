<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m251022_152301__create_table__cms_faq extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_faq';
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

            'created_at' => $this->integer()->null(),
            'created_by' => $this->integer()->null(),

            'name' => $this->string(255)->notNull(),
            'response' => $this->text()->notNull(),

            'is_active' => $this->integer(1)->notNull()->defaultValue(1),
            'priority' => $this->integer()->notNull()->defaultValue(500),

        ], $tableOptions);

        $this->createIndex($tableName.'__is_active', $tableName, 'is_active');
        $this->createIndex($tableName.'__priority', $tableName, 'priority');
        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');

        $this->createIndex($tableName.'__name', $tableName, 'name');

        $this->addCommentOnTable($tableName, 'Вопрос/Ответ');


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

