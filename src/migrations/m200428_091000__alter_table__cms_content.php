<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m200428_091000__alter_table__cms_content extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_content";
        $this->addColumn($tableName, "editable_fields", $this->text());
    }

    public function safeDown()
    {
        echo "m200410_121000__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}