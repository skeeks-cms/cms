<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;

class m170416_103840__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $this->dropForeignKey('cms_tree_created_by', "{{%cms_tree}}");
        $this->dropForeignKey('cms_tree_updated_by', "{{%cms_tree}}");

        $this->addForeignKey(
            'cms_tree__created_by', "{{%cms_tree}}",
            'created_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );

        $this->addForeignKey(
            'cms_tree__updated_by', "{{%cms_tree}}",
            'updated_by', '{{%cms_user}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m170416_103837__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}