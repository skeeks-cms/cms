<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170416_103837__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->dropColumn("{{%cms_tree}}", "tree_menu_ids");
        $this->dropColumn("{{%cms_tree}}", "has_children");
    }

    public function safeDown()
    {
        echo "m170416_103837__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}