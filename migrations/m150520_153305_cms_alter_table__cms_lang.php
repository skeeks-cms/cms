<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 20.05.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150520_153305_cms_alter_table__cms_lang extends Migration
{
    public function safeUp()
    {
        $tableExist = $this->db->getTableSchema("{{%cms_lang}}", true);
        if ($tableExist)
        {
            return true;
        }

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable("{{%cms_lang}}", [
            'id'                    => Schema::TYPE_PK,

            'created_by'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_by'            => Schema::TYPE_INTEGER . ' NULL',

            'created_at'            => Schema::TYPE_INTEGER . ' NULL',
            'updated_at'            => Schema::TYPE_INTEGER . ' NULL',

            'active'                => "CHAR(1) NOT NULL DEFAULT 'Y'",
            'def'                   => "CHAR(1) NOT NULL DEFAULT 'N'",
            'priority'              => Schema::TYPE_INTEGER. "(11) NOT NULL DEFAULT '500'",

            'code'                  => "CHAR(5) NOT NULL",
            'name'                  => Schema::TYPE_STRING . '(255) NOT NULL',
            'description'           => Schema::TYPE_STRING . '(255) NULL',

        ], $tableOptions);

        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(created_by);");

        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(created_at);");
        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(updated_at);");

        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(def);");
        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(code);");
        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(priority);");

        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(name);");
        $this->execute("ALTER TABLE {{%cms_lang}} ADD INDEX(description);");

        $this->execute("ALTER TABLE {{%cms_lang}} ADD UNIQUE(code);");

        $this->execute("ALTER TABLE {{%cms_lang}} COMMENT = 'Доступные языки';");

        $this->addForeignKey(
            'cms_lang_created_by', "{{%cms_lang}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_lang_updated_by', "{{%cms_lang}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        echo "m150520_153305_cms_alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}