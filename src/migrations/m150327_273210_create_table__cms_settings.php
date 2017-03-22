<?php
/**
 * m150121_273200_create_table__cms_settings
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
 * Class m150121_273200_create_table__cms_settings
 */
class m150327_273210_create_table__cms_settings extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_settings}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql')
        {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_settings}}", [
            'id'                    => Schema::TYPE_PK,
            
            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',
            
            'component'             => Schema::TYPE_STRING. '(255)',
            'value'                 => Schema::TYPE_TEXT. ' NULL',
        ], $tableOptions);
        
        $this->execute("ALTER TABLE {{%cms_settings}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_settings}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_settings}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_settings}} ADD INDEX(updated_at);");
        

        $this->execute("ALTER TABLE {{%cms_settings}} ADD INDEX(component);");
        $this->execute("ALTER TABLE {{%cms_settings}} ADD UNIQUE(component);");
        
        
        $this->addForeignKey(
            'cms_settings_created_by', "{{%cms_settings}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_settings_updated_by', "{{%cms_settings}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        $this->dropForeignKey("cms_settings_created_by", "{{%cms_settings}}");
        $this->dropForeignKey("cms_settings_updated_by", "{{%cms_settings}}");

        $this->dropTable("{{%cms_settings}}");
    }
}
