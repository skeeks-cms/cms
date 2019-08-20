<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190820_015515__alter_table__cms_user_email extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_user_email}}", "is_approved", $this->integer(1)->unsigned()->comment("Email подтвержден?")->notNull()->defaultValue(0));
        $this->addColumn("{{%cms_user_email}}", "is_main", $this->integer(1)->unsigned()->comment("Email главный у пользователя?")->notNull()->defaultValue(0));

        $this->dropColumn("{{%cms_user_email}}", "def");
        $this->dropColumn("{{%cms_user_email}}", "approved");

        $this->createIndex("is_approved", "{{%cms_user_email}}", "is_approved");
        $this->createIndex("is_main", "{{%cms_user_email}}", "is_main");

        $this->addColumn("{{%cms_user_email}}", "approved_key_at", $this->integer()->comment("Время генерации и отправки ключа?"));
        $this->createIndex("approved_key_at", "{{%cms_user_email}}", "approved_key_at");
    }

    public function safeDown()
    {
        echo "m190621_015515__alter_table__cms_site_domain cannot be reverted.\n";
        return false;
    }
}