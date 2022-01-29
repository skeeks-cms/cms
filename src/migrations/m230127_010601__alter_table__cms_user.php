<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m230127_010601__alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $tableName = "{{%cms_user}}";

        $this->renameColumn($tableName, "email", '__to_del__email');
        $this->renameColumn($tableName, "phone", '__to_del__phone');

        $this->renameColumn($tableName, "email_is_approved", '__to_del__email_is_approved');
        $this->renameColumn($tableName, "phone_is_approved", '__to_del__phone_is_approved');
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}