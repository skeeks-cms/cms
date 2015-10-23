<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */
use yii\db\Schema;
use yii\db\Migration;
use yii\helpers\Json;

class m151023_173220_alter_table__cms_tree_type extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_tree_type}}', 'viewFile', $this->string(255));
        $this->addColumn('{{%cms_tree_type}}', 'default_children_tree_type', $this->integer()); //При создании дочерние разделы будут этого типа.


        $this->createIndex('viewFile', '{{%cms_tree_type}}', 'viewFile');
        $this->createIndex('default_children_tree_type', '{{%cms_tree_type}}', 'default_children_tree_type');

        $this->addForeignKey(
            'cms_tree_type__default_children_tree_type', "{{%cms_tree_type}}",
            'default_children_tree_type', '{{%cms_tree_type}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m151023_153220_alter_table__cms_content cannot be reverted.\n";
        return false;
    }
}