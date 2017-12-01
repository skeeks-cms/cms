<?php
/**
 * m150121_273200_create_table__cms_content_type
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
 * Class m150512_103210_create_table__cms_content_type
 */
class m150512_103210_create_table__cms_content_type extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_content_type}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_content_type}}", [

            'id'                    => Schema::TYPE_PK,
            
            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'files'                 => Schema::TYPE_TEXT. ' NULL',

            'priority'              => Schema::TYPE_INTEGER. "(11) NOT NULL DEFAULT '500'",

            'name'                  => Schema::TYPE_STRING. '(255) NOT NULL',
            'code'                  => Schema::TYPE_STRING. '(32) NOT NULL',

        ], $tableOptions);
        
        $this->execute("ALTER TABLE {{%cms_content_type}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_content_type}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_content_type}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_content_type}} ADD INDEX(updated_at);");
        

        $this->execute("ALTER TABLE {{%cms_content_type}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_content_type}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_content_type}} ADD UNIQUE(code);");

        
        $this->addForeignKey(
            'cms_content_type_created_by', "{{%cms_content_type}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_content_type_updated_by', "{{%cms_content_type}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_content_type_created_by", "{{%cms_content_type}}");
        $this->dropForeignKey("cms_content_type_updated_by", "{{%cms_content_type}}");

        $this->dropTable("{{%cms_content_type}}");
    }
}
