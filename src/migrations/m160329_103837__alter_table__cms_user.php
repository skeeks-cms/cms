<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m160329_103837__alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_user}}", "email", $this->string(255)->unique());
        $this->addColumn("{{%cms_user}}", "phone", $this->string(64)->unique());

        $this->addColumn("{{%cms_user}}", "email_is_approved", $this->integer(1)->unsigned()->notNull()->defaultValue(0));
        $this->addColumn("{{%cms_user}}", "phone_is_approved", $this->integer(1)->unsigned()->notNull()->defaultValue(0));

        $this->createIndex("phone_is_approved", "{{%cms_user}}", "phone_is_approved");
        $this->createIndex("email_is_approved", "{{%cms_user}}", "email_is_approved");
    }

    public function safeDown()
    {
        echo "m160329_103837__alter_table__cms_user cannot be reverted.\n";
        return false;
    }
}