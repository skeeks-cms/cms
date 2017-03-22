<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160315_093837__create_table__cms_user2cms_content_elements extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_element2cms_user}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_element2cms_user}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'cms_user_id'           => $this->integer()->notNull(),
            'cms_content_element_id'=> $this->integer()->notNull(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_content_element2cms_user}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_content_element2cms_user}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_content_element2cms_user}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_content_element2cms_user}}', 'updated_at');
        $this->createIndex('user2elements', '{{%cms_content_element2cms_user}}', ['cms_user_id', 'cms_content_element_id'], true);

        $this->execute("ALTER TABLE {{%cms_content_element2cms_user}} COMMENT = 'Favorites content items';");

        $this->addForeignKey(
            'cms_content_element2cms_user__created_by', "{{%cms_content_element2cms_user}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element2cms_user__updated_by', "{{%cms_content_element2cms_user}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element2cms_user__cms_user_id', "{{%cms_content_element2cms_user}}",
            'cms_user_id', '{{%cms_user}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_content_element2cms_user__cms_content_element_id', "{{%cms_content_element2cms_user}}",
            'cms_content_element_id', '{{%cms_content_element}}', 'id', 'CASCADE', 'CASCADE'
        );


    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_content_element2cms_user_updated_by", "{{%cms_content_element2cms_user}}");
        $this->dropForeignKey("cms_content_element2cms_user_updated_by", "{{%cms_content_element2cms_user}}");
        $this->dropForeignKey("cms_content_element2cms_user__cms_user_id", "{{%cms_content_element2cms_user}}");
        $this->dropForeignKey("cms_content_element2cms_user__cms_content_element_id", "{{%cms_content_element2cms_user}}");

        $this->dropTable("{{%cms_content_element2cms_user}}");
    }
}