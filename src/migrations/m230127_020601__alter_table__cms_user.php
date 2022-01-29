<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m230127_020601__alter_table__cms_user extends Migration
{
    public function safeUp()
    {
        $tableName = "{{%cms_user}}";
        $this->alterColumn($tableName, "username", $this->string(255)->null());
    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}