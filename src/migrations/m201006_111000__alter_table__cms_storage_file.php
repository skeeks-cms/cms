<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m201006_111000__alter_table__cms_storage_file extends Migration
{

    public function safeUp()
    {
        $tableName = 'cms_storage_file';
        $this->addColumn($tableName, "external_id", $this->string(255)->null());
        $this->createIndex("external_id", $tableName, ["external_id", "cms_site_id"], true);
    }

    public function safeDown()
    {
        echo "m191227_015615__alter_table__cms_tree cannot be reverted.\n";
        return false;
    }
}