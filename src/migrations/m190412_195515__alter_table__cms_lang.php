<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m190412_195515__alter_table__cms_lang extends Migration
{

    public function safeUp()
    {
        $this->dropColumn("{{%cms_lang}}", "def");
    }

    public function safeDown()
    {
        echo "m190412_195515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}