<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150923_163220_create_table__cms_content_element_file extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_element_file}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_element_file}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'storage_file_id'       => $this->integer()->notNull(),
            'content_element_id'    => $this->integer()->notNull(),

            'priority'              => $this->integer()->notNull()->defaultValue(100),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_content_element_file}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_content_element_file}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_content_element_file}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_content_element_file}}', 'updated_at');

        $this->createIndex('storage_file_id', '{{%cms_content_element_file}}', 'storage_file_id');
        $this->createIndex('content_element_id', '{{%cms_content_element_file}}', 'content_element_id');
        $this->createIndex('priority', '{{%cms_content_element_file}}', 'priority');
        $this->createIndex('storage_file_id__content_element_id', '{{%cms_content_element_file}}', ['storage_file_id', 'content_element_id'], true);

        $this->execute("ALTER TABLE {{%cms_content_element_file}} COMMENT = 'Связь элементов и файлов';");

        $this->addForeignKey(
            'cms_content_element_file_created_by', "{{%cms_content_element_file}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element_file_updated_by', "{{%cms_content_element_file}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element_file__storage_file_id', "{{%cms_content_element_file}}",
            'storage_file_id', '{{%cms_storage_file}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_content_element_file__content_element_id', "{{%cms_content_element_file}}",
            'content_element_id', '{{%cms_content_element}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_content_element_file_updated_by", "{{%cms_content_element_file}}");
        $this->dropForeignKey("cms_content_element_file_updated_by", "{{%cms_content_element_file}}");
        $this->dropForeignKey("cms_content_element_file__storage_file_id", "{{%cms_content_element_file}}");
        $this->dropForeignKey("cms_content_element_file__content_element_id", "{{%cms_content_element_file}}");

        $this->dropTable("{{%cms_content_element_file}}");
    }
}