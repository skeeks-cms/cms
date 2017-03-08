<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 10.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150516_103230_create_table__cms_content_element_tree extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_element_tree}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_element_tree}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'element_id'            => Schema::TYPE_INTEGER . ' NOT NULL',
            'tree_id'               => Schema::TYPE_INTEGER . ' NOT NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD INDEX(tree_id);");
        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD INDEX(element_id);");

        $this->execute("ALTER TABLE {{%cms_content_element_tree}} ADD UNIQUE(element_id, tree_id);");

        $this->execute("ALTER TABLE {{%cms_content_element_tree}} COMMENT = 'Связь контента и разделов';");

        $this->addForeignKey(
            'cms_content_element_tree_created_by', "{{%cms_content_element_tree}}",
            'created_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_content_element_tree_updated_by', "{{%cms_content_element_tree}}",
            'updated_by', '{{%cms_user}}', 'id', 'RESTRICT', 'RESTRICT'
        );

        $this->addForeignKey(
            'cms_content_element_tree_tree_id', "{{%cms_content_element_tree}}",
            'tree_id', '{{%cms_tree}}', 'id', 'CASCADE', 'CASCADE'
        );

        $this->addForeignKey(
            'cms_content_element_tree_element_id', "{{%cms_content_element_tree}}",
            'element_id', '{{%cms_content_element}}', 'id', 'CASCADE', 'CASCADE'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_content_element_tree_created_by", "{{%cms_content_element_tree}}");
        $this->dropForeignKey("cms_content_element_tree_updated_by", "{{%cms_content_element_tree}}");
        $this->dropForeignKey("cms_content_element_tree_tree_id", "{{%cms_content_element_tree}}");
        $this->dropForeignKey("cms_content_element_tree_element_id", "{{%cms_content_element_tree}}");

        $this->dropTable("{{%cms_content_element_tree}}");
    }
}