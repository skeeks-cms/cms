<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m250701_152301__create_table__cms_compare_element extends Migration
{
    public function safeUp()
    {
        $tableName = 'cms_compare_element';
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

            'cms_content_element_id' => $this->integer()->notNull(),
            'shop_user_id' => $this->integer()->notNull(),

        ], $tableOptions);

        $this->createIndex($tableName.'__created_at', $tableName, 'created_at');
        $this->createIndex($tableName.'__unique', $tableName, ['shop_user_id', 'cms_content_element_id'], true);

        $this->addCommentOnTable($tableName, 'Списки элементов для сравнения');

        $this->addForeignKey(
            "{$tableName}__shop_user_id", $tableName,
            'shop_user_id', '{{%shop_user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            "{$tableName}__cms_content_element_id", $tableName,
            'cms_content_element_id', '{{%cms_content_element}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        echo "m240530_132301__create_table__cms_company cannot be reverted.\n";
        return false;
    }
}

