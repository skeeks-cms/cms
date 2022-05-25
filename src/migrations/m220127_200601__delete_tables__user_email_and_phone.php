<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220127_200601__delete_tables__user_email_and_phone extends Migration
{
    public function safeUp()
    {
        $this->dropTable("cms_user_email");
        $this->dropTable("cms_user_phone");

    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}