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

class m150122_273205_alter_table__cms_user__emails_adn_phones extends Migration
{
    public function up()
    {
        $this->execute("ALTER TABLE {{%cms_user_email}} CHANGE `user_id` `user_id` INT(11) NULL");
        $this->execute("ALTER TABLE {{%cms_user_phone}} CHANGE `user_id` `user_id` INT(11) NULL");
    }

    public function down()
    {
        echo "m150122_273205_alter_table__cms_user__emails_adn_phones cannot be reverted.\n";
        return false;
    }
}
