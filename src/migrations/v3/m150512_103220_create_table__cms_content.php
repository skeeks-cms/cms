<?php
/**
 * m150121_273200_create_table__cms_content
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 27.01.2015
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m150512_103210_create_table__cms_content
 */
class m150512_103220_create_table__cms_content extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content}}", [
            'id'                    => Schema::TYPE_PK,
            
            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING. '(255) NOT NULL',
            'code'                  => Schema::TYPE_STRING. '(50) NOT NULL',

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",

            'priority'              => Schema::TYPE_INTEGER. "(11) NOT NULL DEFAULT '500'",

            'description'           => Schema::TYPE_TEXT. " NULL",

            'files'                 => Schema::TYPE_TEXT. ' NULL',

            'index_for_search'         => "CHAR(1) NOT NULL DEFAULT 'Y'", //Индексировать элементы для модуля поиска

            'name_meny'         => Schema::TYPE_STRING. '(100) NULL',
            'name_one'          => Schema::TYPE_STRING. '(100) NULL',


            'tree_chooser'          => "CHAR(1) NULL", //Интерфейс привязки элемента к разделам
            'list_mode'             => "CHAR(1) NULL", //Режим просмотра разделов и элементов

            'content_type'          => Schema::TYPE_STRING. '(32) NOT NULL',

        ], $tableOptions);
        
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(updated_at);");
        

        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(active);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(index_for_search);");



        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(name_meny);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(name_one);");

        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(tree_chooser);");
        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(list_mode);");

        $this->execute("ALTER TABLE {{%cms_content}} ADD INDEX(content_type);");

        
        $this->addForeignKey(
            'cms_content_created_by', "{{%cms_content}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_updated_by', "{{%cms_content}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_cms_content_type', "{{%cms_content}}",
            'content_type', '{{%cms_content_type}}', 'code', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_content_created_by", "{{%cms_content}}");
        $this->dropForeignKey("cms_content_updated_by", "{{%cms_content}}");
        $this->dropForeignKey("cms_content_cms_content_type", "{{%cms_content}}");

        $this->dropTable("{{%cms_content}}");
    }
}
