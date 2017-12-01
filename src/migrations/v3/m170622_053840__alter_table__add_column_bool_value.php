<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170622_053840__alter_table__add_column_bool_value extends Migration
{
    public function safeUp()
    {
        $this->addColumn("{{%cms_content_element_property}}", "value_bool", $this->boolean());
        $this->addColumn("{{%cms_tree_property}}", "value_bool", $this->boolean());
        $this->addColumn("{{%cms_user_property}}", "value_bool", $this->boolean());
    }

    public function safeDown()
    {
        echo "m170622_053840__alter_table__add_bool_value cannot be reverted.\n";
        return false;
    }
}