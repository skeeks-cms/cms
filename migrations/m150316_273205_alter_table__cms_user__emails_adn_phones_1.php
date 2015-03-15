<?php
/**
 * m150122_273205_alter_table__cms_user__emails_adn_phones
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 24.02.2015
 * @since 1.0.0
 */
use yii\db\Schema;
use yii\db\Migration;

class m150316_273205_alter_table__cms_user__emails_adn_phones_1 extends Migration
{
    public function up()
    {
        $this->execute("ALTER TABLE {{%cms_user%}} DROP FOREIGN KEY `cms_user_tree_cms_user_email`;");
        $this->execute("ALTER TABLE {{%cms_user%}} DROP FOREIGN KEY `cms_user_tree_cms_user_phone`;");
        $this->execute("ALTER TABLE {{%cms_user%}} DROP FOREIGN KEY `user_user_group_updated_by`;");

        $this->addForeignKey(
            'cms_user_tree_cms_user_email', "{{%cms_user}}",
            'email', '{{%cms_user_email}}', 'value', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_user_tree_cms_user_phone', "{{%cms_user}}",
            'phone', '{{%cms_user_phone}}', 'value', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'user_user_group_updated_by', "{{%cms_user}}",
            'group_id', '{{%cms_user_group}}', 'id', 'SET NULL', 'SET NULL'
        );

    }

    public function down()
    {
        echo "m150316_273205_alter_table__cms_user__emails_adn_phones_1 cannot be reverted.\n";
        return false;
    }
}
