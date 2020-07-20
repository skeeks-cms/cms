<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Schema;
use yii\db\Migration;

class m200717_101000__alter_table__cms_site extends Migration
{

    public function safeUp()
    {
        $tableName = "cms_site";

        $this->addColumn($tableName, "favicon_storage_file_id", $this->integer()->comment("Favicon"));
        $this->addColumn($tableName, "work_time", $this->text()->comment("Рабочее время"));

        $this->createIndex("favicon_storage_file_id", $tableName, ['favicon_storage_file_id']);

        $this->addForeignKey(
            "{$tableName}__favicon_storage_file_id", $tableName,
            'favicon_storage_file_id', '{{%cms_storage_file}}', 'id', 'SET NULL', 'SET NULL'
        );
    }

    public function safeDown()
    {
        echo "m190412_205515__alter_table__cms_lang cannot be reverted.\n";
        return false;
    }
}