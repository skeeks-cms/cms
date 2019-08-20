<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190920_015515__update_data_table__cms_user_email extends Migration
{

    public function safeUp()
    {
        $this->delete("{{%cms_user_email}}", ['user_id' => null]);
    }

    public function safeDown()
    {
        echo "m190920_015515__update_data_table__cms_user_email cannot be reverted.\n";
        return false;
    }
}