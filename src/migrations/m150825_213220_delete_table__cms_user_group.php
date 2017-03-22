<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150825_213220_delete_table__cms_user_group extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE cms_user DROP FOREIGN KEY user_user_group_updated_by;");
        $this->execute("ALTER TABLE `cms_user` DROP `group_id`;");
        //$this->execute("ALTER TABLE cms_user_group DROP FOREIGN KEY user_group_created_by;");
        //$this->execute("ALTER TABLE cms_user_group DROP FOREIGN KEY user_group_updated_by;");
        $this->execute("DROP TABLE cms_user_group;");
    }

    public function down()
    {
        echo "m150825_213220_delete_table__cms_user_group cannot be reverted.\n";
        return false;
    }
}
