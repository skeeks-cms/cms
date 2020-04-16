<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200416_091000__alter_table__cms_content_element extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content_element";
        
        $this->dropIndex("tree_id_2", $tableName);
        $this->dropIndex("content_id_2", $tableName);
    }

    public function safeDown()
    {
        echo "m200410_121000__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}