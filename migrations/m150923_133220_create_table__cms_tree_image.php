<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150923_133220_create_table__cms_tree_image extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_tree_image}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_tree_image}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'storage_file_id'       => $this->integer()->notNull(),
            'tree_id'               => $this->integer()->notNull(),

            'priority'              => $this->integer()->notNull()->defaultValue(100),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_tree_image}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_tree_image}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_tree_image}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_tree_image}}', 'updated_at');

        $this->createIndex('storage_file_id', '{{%cms_tree_image}}', 'storage_file_id');
        $this->createIndex('tree_id', '{{%cms_tree_image}}', 'tree_id');
        $this->createIndex('priority', '{{%cms_tree_image}}', 'priority');
        $this->createIndex('storage_file_id__tree_id', '{{%cms_tree_image}}', ['storage_file_id', 'tree_id'], true);

        $this->execute("ALTER TABLE {{%cms_tree_image}} COMMENT = 'Связь разделов и файлов изображений';");

        $this->addForeignKey(
            'cms_tree_image_created_by', "{{%cms_tree_image}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree_image_updated_by', "{{%cms_tree_image}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree_image__storage_file_id', "{{%cms_tree_image}}",
            'storage_file_id', '{{%cms_storage_file}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_tree_image__tree_id', "{{%cms_tree_image}}",
            'tree_id', '{{%cms_tree}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_tree_image_updated_by", "{{%cms_tree_image}}");
        $this->dropForeignKey("cms_tree_image_updated_by", "{{%cms_tree_image}}");
        $this->dropForeignKey("cms_tree_image__storage_file_id", "{{%cms_tree_image}}");
        $this->dropForeignKey("cms_tree_image__tree_id", "{{%cms_tree_image}}");

        $this->dropTable("{{%cms_tree_image}}");
    }
}