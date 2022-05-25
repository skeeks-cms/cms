<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m220127_152915__alter_table__cms_user extends Migration
{

    public function safeUp()
    {
        $this->dropColumn("{{%cms_user}}", "active");
        $this->dropColumn("{{%cms_user}}", "_to_del_name");
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}