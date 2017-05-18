<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170515_093837__create_table__cms_tree_type_property2type extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_tree_type_property2type}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_tree_type_property2type}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'cms_tree_type_property_id'   => $this->integer()->notNull(),
            'cms_tree_type_id'            => $this->integer()->notNull(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_tree_type_property2type}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_tree_type_property2type}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_tree_type_property2type}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_tree_type_property2type}}', 'updated_at');
        $this->createIndex('cms_tree_type_property_id', '{{%cms_tree_type_property2type}}', 'cms_tree_type_property_id');
        $this->createIndex('cms_tree_type_id', '{{%cms_tree_type_property2type}}', 'cms_tree_type_id');

        $this->createIndex('property2content', '{{%cms_tree_type_property2type}}', ['cms_tree_type_property_id', 'cms_tree_type_id'], true);

        $this->addForeignKey(
            'cms_tree_type_property2type__created_by', "{{%cms_tree_type_property2type}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree_type_property2type__updated_by', "{{%cms_tree_type_property2type}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree_type_property2type__type_id', "{{%cms_tree_type_property2type}}",
            'cms_tree_type_id', '{{%cms_tree_type}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_tree_type_property2type__property_id', "{{%cms_tree_type_property2type}}",
            'cms_tree_type_property_id', '{{%cms_tree_type_property}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_tree_type_property2type__created_by", "{{%cms_tree_type_property2type}}");
        $this->dropForeignKey("cms_tree_type_property2type__updated_by", "{{%cms_tree_type_property2type}}");
        $this->dropForeignKey("cms_tree_type_property2type__property_id", "{{%cms_tree_type_property2type}}");
        $this->dropForeignKey("cms_tree_type_property2type__type_id", "{{%cms_tree_type_property2type}}");

        $this->dropTable("{{%cms_tree_type_property2type}}");
    }
}