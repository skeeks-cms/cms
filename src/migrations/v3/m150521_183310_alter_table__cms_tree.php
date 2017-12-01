<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 21.05.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150521_183310_alter_table__cms_tree extends Migration
{
    public function safeUp()
    {

        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `main_root`;");
    }

    public function down()
    {
        echo "m150521_183310_alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}
