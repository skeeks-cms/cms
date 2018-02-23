<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m180223_175515__alter_table__cms_storage_file extends Migration
{

    public function safeUp()
    {
        $this->addColumn("{{%cms_storage_file}}", "priority", $this->integer()->notNull()->defaultValue(100));
        $this->createIndex("cms_storage_file_priority", "{{%cms_storage_file}}", "priority");
    }

    public function safeDown()
    {
        echo "m180223_175515__alter_table__cms_storage_file cannot be reverted.\n";
        return false;
    }
}