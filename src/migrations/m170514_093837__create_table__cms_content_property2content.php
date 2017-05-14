<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170514_093837__create_table__cms_content_property2content extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_property2content}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_property2content}}", [
            'id'                    => $this->primaryKey(),

            'created_by'            => $this->integer(),
            'updated_by'            => $this->integer(),

            'created_at'            => $this->integer(),
            'updated_at'            => $this->integer(),

            'cms_content_property_id'   => $this->integer()->notNull(),
            'cms_content_id'            => $this->integer()->notNull(),

        ], $tableOptions);

        $this->createIndex('updated_by', '{{%cms_content_property2content}}', 'updated_by');
        $this->createIndex('created_by', '{{%cms_content_property2content}}', 'created_by');
        $this->createIndex('created_at', '{{%cms_content_property2content}}', 'created_at');
        $this->createIndex('updated_at', '{{%cms_content_property2content}}', 'updated_at');
        $this->createIndex('cms_content_property_id', '{{%cms_content_property2content}}', 'cms_content_property_id');
        $this->createIndex('cms_content_id', '{{%cms_content_property2content}}', 'cms_content_id');

        $this->createIndex('property2content', '{{%cms_content_property2content}}', ['cms_content_property_id', 'cms_content_id'], true);

        $this->addForeignKey(
            'cms_content_property2content__created_by', "{{%cms_content_property2content}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_property2content__updated_by', "{{%cms_content_property2content}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_property2content__content_id', "{{%cms_content_property2content}}",
            'cms_content_id', '{{%cms_content}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_content_property2content__property_id', "{{%cms_content_property2content}}",
            'cms_content_property_id', '{{%cms_content_property}}', 'id', 'CASCADE', 'CASCADE'
        );

    }

    public function safeDown()
    {
        $this->dropForeignKey("cms_content_property2content__created_by", "{{%cms_content_property2content}}");
        $this->dropForeignKey("cms_content_property2content__updated_by", "{{%cms_content_property2content}}");
        $this->dropForeignKey("cms_content_property2content__property_id", "{{%cms_content_property2content}}");
        $this->dropForeignKey("cms_content_property2content__content_id", "{{%cms_content_property2content}}");

        $this->dropTable("{{%cms_content_property2content}}");
    }
}