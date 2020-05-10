<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200510_101000__alter_table__cms_tree extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_tree";
        $this->addColumn($tableName, "main_cms_tree_id", $this->integer()->null());

        $this->addForeignKey(
            "{$tableName}__main_cms_tree_id", $tableName,
            'main_cms_tree_id', "cms_tree", 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m200410_121000__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}