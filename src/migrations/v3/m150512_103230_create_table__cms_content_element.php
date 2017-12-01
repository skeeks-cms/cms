<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 15.05.2015
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m150512_103230_create_table__cms_content_element
 */
class m150512_103230_create_table__cms_content_element extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_element}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_element}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'published_at'          => Schema::TYPE_INTEGER . ' NULL',
            'published_to'          => Schema::TYPE_INTEGER . ' NULL',

            'priority'              => Schema::TYPE_INTEGER. "(11) NOT NULL DEFAULT '500'",

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",

            'name'                  => Schema::TYPE_STRING. '(255) NOT NULL',
            'code'                  => Schema::TYPE_STRING. '(255) NULL',

            'description_short'     => Schema::TYPE_TEXT. ' NULL',
            'description_full'      => Schema::TYPE_TEXT. ' NULL',

            'files'                 => Schema::TYPE_TEXT. ' NULL',

            'content_id'            => Schema::TYPE_INTEGER . ' NULL',
            'tree_id'               => Schema::TYPE_INTEGER . ' NULL',

            'show_counter'          => Schema::TYPE_INTEGER . ' NULL',
            'show_counter_start'    => Schema::TYPE_INTEGER . ' NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(published_at);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(published_to);");


        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(code);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(active);");

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(content_id);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(tree_id);");

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD UNIQUE(content_id, code);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD UNIQUE(tree_id, code);");

        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(show_counter);");
        $this->execute("ALTER TABLE {{%cms_content_element}} ADD INDEX(show_counter_start);");


        $this->addForeignKey(
            'cms_content_element_created_by', "{{%cms_content_element}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element_updated_by', "{{%cms_content_element}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element_tree_id', "{{%cms_content_element}}",
            'tree_id', '{{%cms_tree}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_element_content_id', "{{%cms_content_element}}",
            'content_id', '{{%cms_content}}', 'id', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_content_element_created_by", "{{%cms_content_element}}");
        $this->dropForeignKey("cms_content_element_updated_by", "{{%cms_content_element}}");
        $this->dropForeignKey("cms_content_element_tree_id", "{{%cms_content_element}}");
        $this->dropForeignKey("cms_content_element_content_id", "{{%cms_content_element}}");

        $this->dropTable("{{%cms_content_element}}");
    }
}
