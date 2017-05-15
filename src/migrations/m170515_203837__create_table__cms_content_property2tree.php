<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170515_203837__create_table__cms_content_property2tree extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_property2tree}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_property2tree}}", [
            'id'                        => $this->primaryKey(),

            'created_by'                => $this->integer(),
            'updated_by'                => $this->integer(),

            'created_at'                => $this->integer(),
            'updated_at'                => $this->integer(),

            'cms_content_property_id'   => $this->integer()->notNull(),
            'cms_tree_id'               => $this->integer()->notNull(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_content_property2tree}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_content_property2tree}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_content_property2tree}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_content_property2tree}}', 'updated_at');
        $this->createIndex('cms_content_property_id', '{{%cms_content_property2tree}}', 'cms_content_property_id');
        $this->createIndex('cms_tree_id', '{{%cms_content_property2tree}}', 'cms_tree_id');

        $this->createIndex('property2tree', '{{%cms_content_property2tree}}', ['cms_content_property_id', 'cms_tree_id'], true);

        $this->addForeignKey(
            'cms_content_property2tree__created_by', "{{%cms_content_property2tree}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_property2tree__updated_by', "{{%cms_content_property2tree}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_property2tree__cms_tree_id', "{{%cms_content_property2tree}}",
            'cms_tree_id', '{{%cms_tree}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_content_property2tree__property_id', "{{%cms_content_property2tree}}",
            'cms_content_property_id', '{{%cms_content_property}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_content_property2tree__created_by", "{{%cms_content_property2tree}}");
        $this->dropForeignKey("cms_content_property2tree__updated_by", "{{%cms_content_property2tree}}");
        $this->dropForeignKey("cms_content_property2tree__property_id", "{{%cms_content_property2tree}}");
        $this->dropForeignKey("cms_content_property2tree__content_id", "{{%cms_content_property2tree}}");

        $this->dropTable("{{%cms_content_property2tree}}");
    }
}