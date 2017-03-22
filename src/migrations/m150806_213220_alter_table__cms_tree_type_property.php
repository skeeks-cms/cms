<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150806_213220_alter_table__cms_tree_type_property extends Migration
{
    public function safeUp()
    {
        $this->execute("ALTER TABLE {{%cms_tree_type_property}} DROP INDEX `code`, ADD INDEX `code` (`code`) USING BTREE;");
        $this->execute("ALTER TABLE {{%cms_tree_type_property}} ADD UNIQUE (code,tree_type_id);");
    }

    public function down()
    {
        echo "m150806_213220_alter_table__cms_tree_type_property cannot be reverted.\n";
        return false;
    }
}
