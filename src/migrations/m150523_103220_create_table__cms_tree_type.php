<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 22.05.2015
 */
use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m150523_103220_create_table__cms_tree_type
 */
class m150523_103220_create_table__cms_tree_type extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_tree_type}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_tree_type}}", [
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

            'index_for_search'      => "CHAR(1) NOT NULL DEFAULT 'Y'", //Индексировать элементы для модуля поиска

            'name_meny'         => Schema::TYPE_STRING. '(100) NULL',
            'name_one'          => Schema::TYPE_STRING. '(100) NULL',

        ], $tableOptions);
        
        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(updated_at);");
        

        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(active);");

        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(index_for_search);");

        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(name_meny);");
        $this->execute("ALTER TABLE {{%cms_tree_type}} ADD INDEX(name_one);");


        $this->execute("ALTER TABLE {{%cms_tree_type}} COMMENT = 'Тип раздела';");
        
        $this->addForeignKey(
            'cms_tree_type_created_by', "{{%cms_tree_type}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree_type_updated_by', "{{%cms_tree_type}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_tree_type_created_by", "{{%cms_tree_type}}");
        $this->dropForeignKey("cms_tree_type_updated_by", "{{%cms_tree_type}}");

        $this->dropTable("{{%cms_tree_type}}");
    }
}
