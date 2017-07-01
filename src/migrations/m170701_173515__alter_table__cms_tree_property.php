<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170701_173515__alter_table__cms_tree_property extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_tree_property}}", "value_num2", $this->decimal(18, 4));
        $this->createIndex("value_num2", "{{%cms_tree_property}}", "value_num2");

        $this->addColumn("{{%cms_tree_property}}", "value_int2", $this->integer());
        $this->createIndex("value_int2", "{{%cms_tree_property}}", "value_int2");

        $this->addColumn("{{%cms_tree_property}}", "value_string", $this->string(255));
        $this->createIndex("value_string", "{{%cms_tree_property}}", "value_string");

    }

    public function safeDown()
    {
        echo "m170701_173515__alter_table__cms_tree_property cannot be reverted.\n";
        return false;
    }
}