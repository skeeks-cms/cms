<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_153310_cms_alter_table__cms_site extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_site}}", true);
        if ($tableExist)
        {
            $this->execute("DROP TABLE {{%cms_site%}}");
        }


        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_site}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'def'                   => "CHAR(1) NOT NULL DEFAULT 'N'",
            'priority'              => Schema::TYPE_INTEGER. "(11) NOT NULL DEFAULT '500'",

            'code'                  => "CHAR(5) NOT NULL",
            'lang_code'             => "CHAR(5) NOT NULL",

            'name'                  => Schema::TYPE_STRING . '(255) NOT NULL',
            'server_name'           => Schema::TYPE_STRING . '(255) NULL',
            'description'           => Schema::TYPE_STRING . '(255) NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(active);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(server_name);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(def);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(priority);");
        $this->execute("ALTER TABLE {{%cms_site}} ADD INDEX(lang_code);");

        $this->execute("ALTER TABLE {{%cms_site}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_site}} COMMENT = 'Сайты';");

        $this->addForeignKey(
            'cms_site_created_by', "{{%cms_site}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_site_updated_by', "{{%cms_site}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );


        $this->addForeignKey(
            'cms_site_lang_code', "{{%cms_site}}",
            'lang_code', '{{%cms_lang}}', 'code', 'RESTRICT', 'RESTRICT'
        );
    }

    public function down()
    {
        echo "m150520_153310_cms_alter_table__cms_site cannot be reverted.\n";
        return false;
    }
}
