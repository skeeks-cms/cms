<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 24.03.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m150523_114025_alter_table__cms_tree extends Migration
{
    public function safeUp()
    {

        $this->execute("ALTER TABLE {{%cms_tree%}} DROP `type`;");

        $this->execute("ALTER TABLE {{%cms_tree%}} ADD `tree_type_id` INT(11) NULL ;");
        $this->execute("ALTER TABLE {{%cms_tree}} ADD INDEX(tree_type_id);");

        $this->addForeignKey(
            'cms_tree_tree_type_id', "{{%cms_tree}}",
            'tree_type_id', '{{%cms_tree_type}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function down()
    {
        echo "m150523_114025_alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}
