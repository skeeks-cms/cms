<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 10.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150826_113220_create_table__cms_user_universal_property extends Migration
{
    public function up()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_user_universal_property}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_user_universal_property}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'name'                  => Schema::TYPE_STRING . '(255) NOT NULL',
            'code'                  => Schema::TYPE_STRING . '(64) NULL',

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'priority'              => "INT NOT NULL DEFAULT '500'",
            'property_type'         => "CHAR(1) NOT NULL DEFAULT 'S'",
            'list_type'             => "CHAR(1) NOT NULL DEFAULT 'L'",
            'multiple'              => "CHAR(1) NOT NULL DEFAULT 'N'",
            'multiple_cnt'          => "INT NULL",
            'with_description'      => "CHAR(1) NULL",
            'searchable'            => "CHAR(1) NOT NULL DEFAULT 'N'",
            'filtrable'             => "CHAR(1) NOT NULL DEFAULT 'N'",
            'is_required'           => "CHAR(1) NULL",
            'version'               => "INT NOT NULL DEFAULT '1'",
            'component'             => "VARCHAR(255) NULL",
            'component_settings'    => "TEXT NULL",
            'hint'                  => "VARCHAR(255) NULL",
            'smart_filtrable'       => "CHAR(1) NOT NULL DEFAULT 'N'",

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(active);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(property_type);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(list_type);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(multiple);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(multiple_cnt);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(with_description);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(searchable);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(filtrable);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(is_required);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(version);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(component);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(hint);");
        $this->execute("ALTER TABLE {{%cms_user_universal_property}} ADD INDEX(smart_filtrable);");

        $this->execute("ALTER TABLE {{%cms_user_universal_property}} COMMENT = 'Свойства пользователей';");

        $this->addForeignKey(
            'cms_user_universal_property_created_by', "{{%cms_user_universal_property}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_user_universal_property_updated_by', "{{%cms_user_universal_property}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

    }

    public function down()
    {
        $this->dropForeignKey("cms_user_universal_property_created_by", "{{%cms_user_universal_property}}");
        $this->dropForeignKey("cms_user_universal_property_updated_by", "{{%cms_user_universal_property}}");

        $this->dropTable("{{%cms_user_universal_property}}");
    }
}