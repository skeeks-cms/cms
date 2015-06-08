<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150604_114030_alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_user}} ADD `updated_by` INT NULL ;");
        $this->execute("ALTER TABLE {{%cms_user}} ADD `created_by` INT NULL ;");

        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(updated_by);");
        $this->execute("ALTER TABLE {{%cms_user}} ADD INDEX(created_by);");

        $this->addForeignKey(
            'cms_user_created_by', "{{%cms_user}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_user_updated_by', "{{%cms_user}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

    }

    public function down()
    {
        echo "m150604_114030_alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}
