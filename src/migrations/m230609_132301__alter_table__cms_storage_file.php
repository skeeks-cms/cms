<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m230609_132301__alter_table__cms_storage_file extends Migration
{
    public function safeUp()
    {
        $tableName = "cms_storage_file";

        $this->alterColumn($tableName, "mime_type", $this->string(255)->null());
    }

    public function safeDown()
    {
        echo "m200717_132301__alter_table__shop_site cannot be reverted.\n";
        return false;
    }
}